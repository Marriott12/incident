<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Incident;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\IncidentValidator;
use App\Services\UploadService;
use App\Services\CsrfService;

class IncidentController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requirePermission('incident.list');

        $incidentModel = new Incident();

        $incidents = AuthorizationService::filterVisibleIncidents(
            $incidentModel->all(100),
            AuthorizationService::currentUser()
        );

        $this->render('incidents/list.php', [
            'incidents' => $incidents,
            'csrf_token' => CsrfService::generateToken(),
            'page_title' => 'Incident List'
        ]);
    }

    public function create(): void
    {
        AuthMiddleware::requirePermission('incident.create');

        $incidentModel = new Incident();

        $this->render('incidents/create.php', [
            'csrf_token' => CsrfService::generateToken(),
            'show_map' => true,
            'page_title' => 'New Incident',
            'old' => [
                'incident_number' => $incidentModel->generateIncidentNumber(),
                'type' => 'criminal',
                'reliability' => 'unreliable'
            ]
        ]);
    }

    public function store(): void
    {
        AuthMiddleware::requirePermission('incident.create');

        if (!CsrfService::validateToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            exit('Invalid CSRF token');
        }

        $data = $_POST;

        $data['report_completed_by'] = $_SESSION['user']['id'] ?? null;

        /*
         * Incident enters review queue after CPO submission
         */
        $data['status'] = 'pending_gstaff_review';

        $validator = new IncidentValidator();

        [$errors, $normalized] = $validator->validate($data);

        $incidentModel = new Incident();

        $currentUser = AuthorizationService::currentUser();
        if (isset($currentUser['role']) && $currentUser['role'] === 'cpo') {
            $userProvince = trim(strtolower((string)($currentUser['province'] ?? '')));
            $incidentProvince = trim(strtolower((string)($normalized['province'] ?? '')));
            if ($userProvince === '' || $userProvince !== $incidentProvince) {
                $errors['province'] = 'As a CPO you may only create incidents for your assigned province.';
            }
        }

        if (!empty($normalized['incident_number'])) {
            $existing = $incidentModel->findByIncidentNumber(
                $normalized['incident_number']
            );

            if ($existing) {
                $errors['incident_number'] =
                    'Incident number already exists.';
            }
        }

        $uploadService = new UploadService();

        $uploadErrors = $uploadService->validate(
            $_FILES['attachments'] ?? []
        );

        foreach ($uploadErrors as $key => $message) {
            $errors["attachments_$key"] = $message;
        }

        if (!empty($errors)) {
            $this->render('incidents/create.php', [
                'errors' => $errors,
                'old' => $data,
                'csrf_token' => CsrfService::generateToken(),
                'show_map' => true,
                'page_title' => 'Create Incident'
            ]);
            return;
        }

        $incidentId = $incidentModel->create($normalized);

        (new AuditService())->record(
            'incident.create',
            'incidents',
            $incidentId,
            null,
            $normalized
        );

        foreach (
            $uploadService->storeMany(
                $_FILES['attachments'] ?? [],
                $incidentId,
                (int)($_SESSION['user']['id'] ?? 1)
            ) as $attachment
        ) {
            (new AuditService())->record(
                'incident.attachment_upload',
                'incident_attachments',
                $incidentId,
                null,
                $attachment
            );
        }

        $this->redirect('/incidents/' . $incidentId);
    }

    public function show(int $id): void
    {
        AuthMiddleware::requirePermission('incident.view');

        $incident = (new Incident())->find($id);

        if (!$incident) {
            http_response_code(404);
            exit('Incident not found');
        }

        if (
            !AuthorizationService::canViewIncident(
                AuthorizationService::currentUser(),
                $incident
            )
        ) {
            http_response_code(403);
            exit('Forbidden');
        }

        $attachments = (new Attachment())->allForIncident($id);

        $auditTrail = (new AuditLog())->forIncident($id);

        $this->render('incidents/detail.php', [
            'incident' => $incident,
            'attachments' => $attachments,
            'auditTrail' => $auditTrail,
            'csrf_token' => CsrfService::generateToken(),
            'show_map' => true,
            'page_title' => 'Incident Details'
        ]);
    }

    /*
     * G STAFF REVIEW
     */
    public function gstaffReview(int $id): void
    {
        AuthMiddleware::requirePermission('incident.gstaff.review');

        $status = $_POST['status'] ?? '';
        $comment = trim($_POST['comment'] ?? '');

        $incidentModel = new Incident();

        if ($status === 'approve') {
            $incidentModel->update($id, [
                'status' => 'approved_by_gstaff',
                'gstaff_comment' => $comment,
                'gstaff_reviewed_by' => $_SESSION['user']['id'],
                'gstaff_reviewed_at' => date('Y-m-d H:i:s')
            ]);
        }

        if ($status === 'reject') {
            $incidentModel->update($id, [
                'status' => 'rejected_by_gstaff',
                'gstaff_comment' => $comment,
                'gstaff_reviewed_by' => $_SESSION['user']['id'],
                'gstaff_reviewed_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->redirect('/incidents/' . $id);
    }

    /*
     * CENTRAL COMMAND REVIEW
     */
    public function centralCommandReview(int $id): void
    {
        AuthMiddleware::requirePermission(
            'incident.central.review'
        );

        $status = $_POST['status'] ?? '';

        $comment = trim($_POST['comment'] ?? '');

        $incidentModel = new Incident();

        if ($status === 'approve') {
            $incidentModel->update($id, [
                'status' => 'published',
                'central_command_comment' => $comment,
                'central_command_reviewed_by' =>
                    $_SESSION['user']['id'],
                'central_command_reviewed_at' =>
                    date('Y-m-d H:i:s')
            ]);
        }

        if ($status === 'reject') {
            $incidentModel->update($id, [
                'status' => 'rejected_by_central_command',
                'central_command_comment' => $comment,
                'central_command_reviewed_by' =>
                    $_SESSION['user']['id'],
                'central_command_reviewed_at' =>
                    date('Y-m-d H:i:s')
            ]);
        }

        $this->redirect('/incidents/' . $id);
    }

    public function delete(int $id): void
    {
        AuthMiddleware::requirePermission('incident.delete');

        $incidentModel = new Incident();

        $incident = $incidentModel->find($id);

        if (!$incident) {
            http_response_code(404);
            exit('Incident not found');
        }

        $incidentModel->delete($id);

        (new AuditService())->record(
            'incident.delete',
            'incidents',
            $id,
            $incident,
            null
        );

        $this->redirect('/incidents');
    }
}
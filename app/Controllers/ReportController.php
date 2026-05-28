<?php
declare(strict_types=1);

namespace App\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Incident;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\AuthorizationService;

class ReportController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requirePermission('report.list');
        $m = new Incident();
        $incidents = AuthorizationService::filterVisibleIncidents($m->all(200), AuthorizationService::currentUser());
        $this->render('reports/index.php', [
            'incidents'  => $incidents,
            'page_title' => 'Reports — IncidentOps',
        ]);
    }

    public function exportPdf(int $id): void
    {
        AuthMiddleware::requirePermission('report.export');
        $m = new Incident();
        $incident = $m->find($id);
        if (!$incident) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        if (!AuthorizationService::canViewIncident(AuthorizationService::currentUser(), $incident)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        // Render HTML using view
        ob_start();
        $incidentData = $incident;
        require __DIR__ . '/../Views/reports/export.php';
        $html = ob_get_clean();

        try {
            $options = new Options();
            // use the generic setter for compatibility across dompdf versions
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdf = $dompdf->output();

            // Sanitize filename
            $safeNumber = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($incident['incident_number'] ?? 'report'));
            $filename = "incident-{$safeNumber}.pdf";

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf));
            (new AuditService())->record('report.export_pdf', 'incidents', $id, null, ['filename' => $filename], ['incident_number' => $incident['incident_number'] ?? null]);
            echo $pdf;
            exit(0);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'PDF generation failed: ' . htmlspecialchars($e->getMessage());
            return;
        }
    }
}

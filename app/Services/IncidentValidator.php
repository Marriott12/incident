<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Incident;
use DateTimeImmutable;

class IncidentValidator
{
    public function validate(array $data, bool $draft = false): array
    {
        $errors = [];
        $normalized = [];

        $normalized['incident_number'] = $this->normalizeIncidentNumber($data['incident_number'] ?? null, $errors);
        $normalized['reported_at'] = $this->normalizeDateTime($data['reported_at'] ?? null, 'reported_at', $errors, !$draft);
        $normalized['submitted_to_hq_at'] = $this->normalizeDateTime($data['submitted_to_hq_at'] ?? null, 'submitted_to_hq_at', $errors, false);

        $normalized['type'] = $this->normalizeEnum($data['type'] ?? null, Incident::TYPES, 'type', $errors, !$draft, 'criminal');
        $normalized['reliability'] = $this->normalizeEnum($data['reliability'] ?? null, Incident::RELIABILITY_LEVELS, 'reliability', $errors, false, 'unknown');
        $normalized['shift'] = $this->normalizeEnum($data['shift'] ?? null, Incident::SHIFTS, 'shift', $errors, false, 'day');
        $normalized['threat_level'] = $this->normalizeEnum($data['threat_level'] ?? null, Incident::THREAT_LEVELS, 'threat_level', $errors, false, 'low');
        $normalized['province'] = $this->normalizeProvince($data['province'] ?? null, $errors);
        $normalized['status'] = $this->normalizeEnum($data['status'] ?? null, Incident::STATUSES, 'status', $errors, false, 'open');
        $normalized['confidentiality_level'] = $this->normalizeEnum($data['confidentiality_level'] ?? null, Incident::CONFIDENTIALITY_LEVELS, 'confidentiality_level', $errors, false, 'restricted');

        $normalized['reporting_unit'] = $this->normalizeString($data['reporting_unit'] ?? null, 100);
        $normalized['commanding_officer'] = $this->normalizeString($data['commanding_officer'] ?? null, 100);
        $normalized['comms_channels'] = $this->normalizeText($data['comms_channels'] ?? null);
        $normalized['liaison_notes'] = $this->normalizeText($data['liaison_notes'] ?? null);
        $normalized['narrative'] = $this->normalizeText($data['narrative'] ?? null);
        $normalized['civilian_impact'] = $this->normalizeText($data['civilian_impact'] ?? null);
        $normalized['environmental_conditions'] = $this->normalizeText($data['environmental_conditions'] ?? null);
        $normalized['escalation_measures'] = $this->normalizeText($data['escalation_measures'] ?? null);
        $normalized['weapons_hazmat_present'] = !empty($data['weapons_hazmat_present']) ? 1 : 0;
        $normalized['weapons_hazmat_details'] = $this->normalizeText($data['weapons_hazmat_details'] ?? null);
        $normalized['patterns_forecast'] = $this->normalizeText($data['patterns_forecast'] ?? null);
        $normalized['military_actions'] = $this->normalizeText($data['military_actions'] ?? null);
        $normalized['support_actions'] = $this->normalizeText($data['support_actions'] ?? null);
        $normalized['intelligence_gathered'] = $this->normalizeText($data['intelligence_gathered'] ?? null);
        $normalized['resources_utilized'] = $this->normalizeText($data['resources_utilized'] ?? null);
        $normalized['immediate_outcome'] = $this->normalizeText($data['immediate_outcome'] ?? null);
        $normalized['damages_description'] = $this->normalizeText($data['damages_description'] ?? null);
        $normalized['followup_actions'] = $this->normalizeText($data['followup_actions'] ?? null);
        $normalized['followup_officer'] = $this->normalizeString($data['followup_officer'] ?? null, 100);
        $normalized['followup_unit'] = $this->normalizeString($data['followup_unit'] ?? null, 100);
        $normalized['grid_reference'] = $this->normalizeGridReference($data['grid_reference'] ?? null, $errors);
        $normalized['ao_sector'] = $this->normalizeString($data['ao_sector'] ?? null, 50);
        $normalized['roe_compliance_notes'] = $this->normalizeText($data['roe_compliance_notes'] ?? null);
        $normalized['human_rights_notes'] = $this->normalizeText($data['human_rights_notes'] ?? null);

        [$normalized['latitude'], $normalized['longitude']] = $this->normalizeCoordinates(
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $errors
        );

        $normalized['ao_polygon'] = $this->normalizeJson($data['ao_polygon'] ?? null, 'ao_polygon', $errors);

        return [$errors, $normalized];
    }

    private function normalizeIncidentNumber($value, array &$errors): ?string
    {
        $value = strtoupper(trim((string)$value));
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^INC-[0-9]{8}-[0-9]{4}$/', $value)) {
            $errors['incident_number'] = 'Incident number format is invalid.';
        }
        return $value;
    }

    private function normalizeDateTime($value, string $field, array &$errors, bool $required): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            if ($required) {
                $errors[$field] = 'This field is required.';
            }
            return null;
        }

        $value = str_replace('T', ' ', $value);
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt instanceof DateTimeImmutable) {
                if ($field === 'reported_at' && $dt > new DateTimeImmutable('now')) {
                    $errors[$field] = 'Reported date cannot be in the future.';
                    return null;
                }
                return $dt->format('Y-m-d H:i:s');
            }
        }

        $errors[$field] = 'Invalid date/time format.';
        return null;
    }

    private function normalizeEnum($value, array $allowed, string $field, array &$errors, bool $required, ?string $default = null): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            if ($required) {
                $errors[$field] = 'This field is required.';
            }
            return $default;
        }
        if (!in_array($value, $allowed, true)) {
            $errors[$field] = 'Invalid value selected.';
            return $default;
        }
        return $value;
    }

    private function normalizeString($value, int $maxLength): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        return mb_substr($value, 0, $maxLength);
    }

    private function normalizeText($value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function normalizeGridReference($value, array &$errors): ?string
    {
        $value = strtoupper(trim((string)$value));
        if ($value === '') {
            return null;
        }
        if (strlen($value) > 20 || !preg_match('/^[0-9A-Z ]+$/', $value)) {
            $errors['grid_reference'] = 'Grid reference format is invalid.';
        }
        return $value;
    }

    private function normalizeProvince($value, array &$errors): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (strlen($value) > 100) {
            $errors['province'] = 'Invalid province selection.';
            return null;
        }
        return $value;
    }

    private function normalizeInteger($value, string $field, array &$errors): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 0) {
            $errors[$field] = 'A non-negative whole number is required.';
            return 0;
        }
        return (int)$value;
    }

    private function normalizeCoordinates($lat, $lng, array &$errors): array
    {
        $lat = trim((string)$lat);
        $lng = trim((string)$lng);
        if ($lat === '' && $lng === '') {
            return [null, null];
        }
        if (!is_numeric($lat) || !is_numeric($lng)) {
            $errors['coordinates'] = 'Latitude and longitude must both be numeric.';
            return [null, null];
        }

        $latValue = round((float)$lat, 8);
        $lngValue = round((float)$lng, 8);
        if ($latValue < -90 || $latValue > 90 || $lngValue < -180 || $lngValue > 180) {
            $errors['coordinates'] = 'Coordinates are outside the valid range.';
            return [null, null];
        }

        return [$latValue, $lngValue];
    }

    private function normalizeJson($value, string $field, array &$errors): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[$field] = 'Invalid JSON payload.';
            return null;
        }
        return $value;
    }
}
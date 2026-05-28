# Zambian Military Incident Report System

## Project Overview

Build a **Zambian Military Internal Security Operations (ISO) Incident Report System** using **PHP 8.x (MVC, no heavy framework)**, **MySQL 8**, **Bootstrap 5**, and **Leaflet.js + OpenStreetMap**. The system must digitise a formal 8-section incident logging template used at forward command posts, with full map integration, role-based access, PDF export, and a legal/compliance audit trail.

---

## Tech Stack

- **Backend:** PHP 8.x, custom MVC (lightweight router), PDO for DB access
- **Database:** MySQL 8 with foreign keys, indexed timestamps, audit log
- **Frontend:** Bootstrap 5, Leaflet.js (OpenStreetMap tiles — no API key), vanilla JS / jQuery
- **PDF Export:** Dompdf (via Composer)
- **Auth:** PHP sessions + bcrypt password hashing
- **Security:** CSRF tokens on all forms, prepared statements everywhere, input sanitisation

---

## Directory Structure

```
/incident-system/
├── /app/
│   ├── /Controllers/
│   │   ├── AuthController.php
│   │   ├── IncidentController.php
│   │   ├── ReportController.php
│   │   └── AdminController.php
│   ├── /Models/
│   │   ├── Incident.php
│   │   ├── User.php
│   │   ├── AuditLog.php
│   │   └── Evidence.php
│   ├── /Views/
│   │   ├── layout/
│   │   │   ├── header.php
│   │   │   └── footer.php
│   │   ├── auth/
│   │   │   └── login.php
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   ├── incidents/
│   │   │   ├── create.php
│   │   │   ├── edit.php
│   │   │   ├── detail.php
│   │   │   └── list.php
│   │   └── reports/
│   │       └── export.php
│   └── /Services/
│       ├── MapService.php
│       ├── PdfService.php
│       └── NotificationService.php
├── /public/
│   ├── /css/
│   │   └── custom.css
│   ├── /js/
│   │   ├── map.js
│   │   ├── form.js
│   │   └── dashboard.js
│   ├── /uploads/          ← attachments, photos (gitignored)
│   └── index.php          ← Front controller / router
├── /config/
│   ├── db.php
│   └── roles.php
├── /storage/
│   └── /pdf_exports/
├── composer.json
└── .htaccess
```

---

## Database Schema

Generate the full SQL migration for the following tables:

### `users`
- `id` INT PK AUTO_INCREMENT
- `name` VARCHAR(100)
- `rank` VARCHAR(50)
- `email` VARCHAR(150) UNIQUE
- `password_hash` VARCHAR(255)
- `role` ENUM('commanding_officer', 'incident_officer', 'hq_readonly', 'admin')
- `unit` VARCHAR(100)
- `created_at` TIMESTAMP DEFAULT NOW()
- `last_login` TIMESTAMP NULL

### `incidents`
- `id` INT PK AUTO_INCREMENT
- `incident_number` VARCHAR(30) UNIQUE (auto-generated: INC-YYYYMMDD-XXXX)
- `reported_at` DATETIME
- `type` ENUM('public_disorder','crowd_control','evacuation','crime','intelligence_tip','other')
- `reporting_unit` VARCHAR(100)
- `commanding_officer` VARCHAR(100)
- `shift` ENUM('day','night')
- `comms_channels` TEXT
- `liaison_notes` TEXT
- `narrative` LONGTEXT
- `personnel_count_military` INT DEFAULT 0
- `personnel_count_police` INT DEFAULT 0
- `personnel_count_civilians` INT DEFAULT 0
- `personnel_count_adversaries` INT DEFAULT 0
- `civilian_impact` TEXT
- `environmental_conditions` TEXT
- `threat_level` ENUM('low','moderate','high','critical') DEFAULT 'low'
- `escalation_measures` TEXT
- `weapons_hazmat_present` TINYINT(1) DEFAULT 0
- `weapons_hazmat_details` TEXT
- `patterns_forecast` TEXT
- `military_actions` TEXT
- `support_actions` TEXT
- `intelligence_gathered` TEXT
- `resources_utilized` TEXT
- `immediate_outcome` TEXT
- `casualties_count` INT DEFAULT 0
- `damages_description` TEXT
- `followup_actions` TEXT
- `followup_officer` VARCHAR(100)
- `followup_unit` VARCHAR(100)
- `report_completed_by` INT FK → users.id
- `reviewed_by` INT FK → users.id NULL
- `grid_reference` VARCHAR(20)
- `latitude` DECIMAL(10,8) NULL
- `longitude` DECIMAL(11,8) NULL
- `ao_sector` VARCHAR(50)
- `ao_polygon` JSON NULL
- `status` ENUM('open','contained','closed','under_review') DEFAULT 'open'
- `confidentiality_level` ENUM('restricted','confidential','secret') DEFAULT 'restricted'
- `roe_compliance_notes` TEXT
- `human_rights_notes` TEXT
- `submitted_to_hq_at` DATETIME NULL
- `created_at` TIMESTAMP DEFAULT NOW()
- `updated_at` TIMESTAMP ON UPDATE NOW()

### `incident_attachments`
- `id`, `incident_id` FK, `file_name`, `file_path`, `file_type` ENUM('photo','map','witness_statement','intel_report','other'), `uploaded_by` FK → users.id, `uploaded_at` TIMESTAMP

### `evidence_chain_of_custody`
- `id`, `incident_id` FK, `item_description`, `seized_by` FK → users.id, `seized_at` DATETIME, `signature_hash` VARCHAR(64), `custody_notes` TEXT, `current_location` VARCHAR(150)

### `audit_log`
- `id`, `user_id` FK, `incident_id` FK NULL, `action` VARCHAR(100), `table_affected` VARCHAR(50), `old_value` JSON NULL, `new_value` JSON NULL, `ip_address` VARCHAR(45), `created_at` TIMESTAMP DEFAULT NOW()

---

## Authentication & Roles

- Login form with rank + email + password
- PHP session-based auth with CSRF token regeneration on login
- Role middleware — check role before every controller action:
  - `commanding_officer` — full access, can endorse/close incidents, view all reports
  - `incident_officer` — create, edit own incidents, upload attachments
  - `hq_readonly` — view and export only, no write access
  - `admin` — user management, system config

---

## Incident Form (8 Sections)

Build a multi-section Bootstrap 5 form (`incidents/create.php`) with these exact sections matching the template:

1. **Incident Identification** — auto-generated incident number (display only), datetime picker, incident type dropdown, reporting unit, grid reference input + map pin drop
2. **Command & Control** — CO name/rank, shift toggle, comms channels, liaison notes textarea
3. **Situation Description** — narrative textarea (with timestamps toolbar), personnel count inputs (4 types), civilian impact, environmental/tactical conditions
4. **Threat Assessment & Escalation** — threat level radio (colour-coded Low/Moderate/High/Critical), escalation measures, weapons/hazmat checkbox + conditional details, patterns/forecast
5. **Actions Taken** — military actions, support actions, intelligence gathered, resources utilized
6. **Outcome & Follow-Up** — immediate outcome, casualties count, damages description, follow-up actions, responsible officer/unit
7. **Reporting & Documentation** — completed-by (pre-filled from session), review/endorsement (CO only), attachments upload (multi-file: photos, maps, intel), HQ submission timestamp
8. **Confidentiality & Legal Compliance** — confidentiality level, ROE compliance notes, human rights notes, chain-of-custody items

Form must:
- Save as draft on every section navigation (AJAX auto-save)
- Validate all required fields on submission with inline Bootstrap validation
- Generate `INC-YYYYMMDD-XXXX` incident number on first save
- Log creation to `audit_log` on submit

---

## Map Integration (Leaflet.js)

### `public/js/map.js`

Implement these features:

1. **Dashboard map** — full-width Leaflet map centred on the operational area. Load all incidents as markers from `/api/incidents/geojson`. Colour-code pins by threat level:
   - Low = green `#1D9E75`
   - Moderate = amber `#EF9F27`
   - High = coral `#D85A30`
   - Critical = red `#E24B4A`
   Use MarkerCluster for dense areas. Clicking a pin opens a popup with incident number, type, status, and a "View" link.

2. **Incident form map** — smaller embedded map on the create/edit form. User can:
   - Click to drop a pin (writes lat/lng + nearest address to hidden fields)
   - Type a grid reference and press Enter to fly to the location (use a MGRS-to-latlon JS library or a simple UK OS grid converter)
   - Draw an AO sector polygon (Leaflet.draw plugin) saved as GeoJSON to `ao_polygon` field

3. **AO Sector overlays** — load sector polygons from `/api/sectors` and display as semi-transparent polygons on all maps. Sectors selectable from a dropdown on the incident form.

4. **Offline tile caching** — implement a basic service worker or use the `leaflet.offline` plugin so recently viewed map tiles are cached for field use with degraded connectivity.

---

## API Endpoints (PHP)

Build these JSON endpoints (no framework, plain PHP returning `Content-Type: application/json`):

```
GET  /api/incidents/geojson          → All incidents as GeoJSON FeatureCollection
GET  /api/incidents/{id}             → Single incident JSON
POST /api/incidents/save-draft       → AJAX draft save (returns incident_id)
GET  /api/sectors                    → AO sector polygons as GeoJSON
GET  /api/dashboard/stats            → Counts by status/threat level for stat cards
```

---

## Dashboard (`dashboard/index.php`)

- Full-width Leaflet map (60% of viewport height) with all active incident pins
- Row of Bootstrap stat cards below: Total Open, High/Critical, Closed Today, Pending HQ Submission
- Recent incidents table: Incident No. | Type | Threat Level (badge) | AO Sector | Reported By | Status | Actions
- Filter bar: date range, type, threat level, AO sector, status
- Quick-create button → opens create form

---

## PDF Export

Using Dompdf, generate a PDF matching the 8-section template layout:

- Header: crest/logo placeholder, "RESTRICTED" watermark, incident number, date
- Each section rendered as a labelled block
- Map screenshot: embed a static map image (use Leaflet's `map.getBounds()` to call a static map URL, or generate with `mapbox/static-images` — if no API key available, embed the Leaflet map as an SVG screenshot)
- Footer: page numbers, "Report completed by:", "Reviewed by:", submission timestamp
- Endpoint: `GET /reports/export/{id}/pdf`

---

## Security Requirements

- All DB queries via PDO prepared statements — no raw string interpolation
- CSRF token in every POST form, validated server-side
- File uploads: validate MIME type (allow jpg, png, pdf only), rename to UUID, store outside webroot
- Sessions: `session_regenerate_id(true)` on login, `session_destroy()` on logout, secure/httponly cookie flags
- Rate limiting on login: lock account for 15 minutes after 5 failed attempts (store attempts in DB)
- All `audit_log` writes are wrapped in a try/catch — logging failure must never break the main transaction

---

## Coding Standards

- PHP: PSR-4 autoloading via Composer, PSR-12 code style
- Use `declare(strict_types=1)` at top of every PHP file
- Controllers are thin — logic lives in Models and Services
- Views use PHP short tags (`<?= ?>`) only for output, never for logic
- JS: ES6+, no build step required, module pattern in each file
- CSS: BEM naming for custom classes, Bootstrap utility classes for layout
- All timestamps stored in UTC, displayed in local time via JS `Intl.DateTimeFormat`

---

## Build Order

Build in this sequence so each phase is independently testable:

1. `composer.json`, `.htaccess`, `config/db.php`, front controller `public/index.php`, basic router
2. Database migration SQL — run and verify all tables
3. Auth system — login, session, role middleware
4. Incident model + IncidentController (create, read, update, list)
5. Incident form view — all 8 sections, Bootstrap validation, CSRF
6. Leaflet map — pin drop on form, dashboard map with colour-coded markers
7. Dashboard with stat cards and recent incidents table
8. API endpoints for GeoJSON and AJAX draft save
9. Attachments upload + evidence chain of custody
10. PDF export with Dompdf
11. Audit log writes across all controller actions
12. Role-based access restrictions + admin user management panel

---

## Sample Data

Seed the database with:
- 3 users (one per role: CO, IO, HQ readonly)
- 5 sample incidents across different threat levels and AO sectors, with lat/lng coordinates set to a plausible operational area
- 2 AO sectors as GeoJSON polygons

---

Start with **Step 1**: scaffold the directory structure, `composer.json` (requiring dompdf/dompdf), `.htaccess` rewrite rules, `config/db.php`, and the front controller with a basic URL router.

# SYNAPSE

> **A Unified Web-Based Campus Health and Counseling Management System** — an IoT-enabled platform that consolidates clinic operations, mental-health appointments, and community-outreach coordination into a single, role-aware web application.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](#server-requirements)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7-EF4223?logo=codeigniter&logoColor=white)](#server-requirements)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](#server-requirements)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Table of Contents

1. [Overview](#overview)
2. [Key Features](#key-features)
3. [System Modules](#system-modules)
4. [Tech Stack](#tech-stack)
5. [Repository Structure](#repository-structure)
6. [Server Requirements](#server-requirements)
7. [Installation & Setup](#installation--setup)
8. [Default Test Credentials](#default-test-credentials)
9. [AI Subsystems](#ai-subsystems)
10. [IoT Subsystem](#iot-subsystem)
11. [Security & Privacy](#security--privacy)
12. [Documentation](#documentation)
13. [Author & Acknowledgements](#author--acknowledgements)

---

## Overview

**SYNAPSE** addresses the operational fragmentation of campus health services. Most institutions run their clinic, counselling office, and outreach programs on **disconnected ledgers, spreadsheets, and chat threads** — which creates duplicated data entry, lost referrals, and missed risks.

SYNAPSE unifies clinic and counselling operations under a single login, a shared normalized database, and a role-based access control system. The platform digitizes patient records, automates medicine expiration and low-stock alerts, schedules counselling appointments with validated screening tools (PHQ-9, GAD-7), and provides role-specific analytics dashboards.

The implementation is built for **Foundation University**.

---

## Key Features

- **Unified login** with role-based access control (4 roles: Administrator, Clinic Staff, Counsellor, Student)
- **Patient records** — consultation history, allergies, vitals, treatments, emergency contacts
- **Medicine inventory** — FEFO batch tracking, expiration alerts, low-stock notifications, transaction history
- **Counselling appointments** — online booking, availability blocks, no-show tracking, intake forms
- **Validated screening** — PHQ-9 (depression), GAD-7 (anxiety), institutional intake survey
- **Bidirectional referrals** — clinic ↔ counselling with 48-hour SLA, automatic escalation
- **Crisis alert protocol** — PHQ-9 Item 9 detection flags immediate counsellor alert
- **IoT student ID scanning** — QR / RFID kiosk with offline fallback buffer
- **AI-assisted features** — risk scoring, triage prediction, inventory forecasting, summary generation
- **Reports & analytics** — Chart.js visualizations per module, CSV exports
- **Comprehensive audit trail** — hash-chained log of every authentication, read, write, and override
- **Branded error pages** — context-aware 403 / 404 / 500 / 503 with request IDs and mailto fallback

---

## System Modules

| Module | Path | Responsibility |
|---|---|---|
| **Auth** | `app/Controllers/AuthController.php` | Login, logout, session lifecycle, rate limiting |
| **Dashboard** | `app/Controllers/DashboardController.php` | Role-aware landing page with KPI tiles |
| **Clinic** | `app/Controllers/Clinic/` | Consultations, vitals, patients, medicines, allergies |
| **Counselling** | `app/Controllers/Counselling/` | Appointments, screening, referrals, crisis alerts |
| **Inventory** | `app/Controllers/Inventory/` | Medicine batches, transactions, forecasts |
| **IoT** | `app/Controllers/Iot/` | QR/RFID scan handler, kiosk view, offline buffer |
| **Reports** | `app/Controllers/Reports/` | Cross-module analytics, Chart.js, CSV exports |
| **Admin** | `app/Controllers/Admin/` | User CRUD, role/permission management, audit log viewer |
| **Profile** | `app/Controllers/ProfileController.php` | Self-service account management |
| **Notifications** | `app/Controllers/NotificationController.php` | Real-time alerts, polling endpoint |

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Language** | PHP 8.2+ |
| **Framework** | CodeIgniter 4.7 (PSR-4, MVC) |
| **Database** | MySQL 8.0+ / MariaDB 10.4+ via MySQLi |
| **Frontend** | Vanilla JS, Chart.js (CDN), Font Awesome 6.5 |
| **Typography** | Inter (body) + Outfit (display) + JetBrains Mono (code), self-served from Google Fonts CDN |
| **Design system** | CSS custom properties, hairline borders, pill buttons, no heavy shadows |
| **Server** | Apache (XAMPP for local dev) |
| **Testing** | PHPUnit 10.5 |

---

## Repository Structure

```
synapse_ag/
├── app/
│   ├── Controllers/          # HTTP layer (Auth, Clinic, Counselling, etc.)
│   ├── Models/               # 36 domain models
│   ├── Views/                # 11 layout + per-module view directories
│   ├── Libraries/            # 6 domain libraries (AI + business logic)
│   ├── Filters/              # AuthFilter, RoleFilter (RBAC enforcement)
│   ├── Helpers/              # Autoloaded helper functions
│   ├── Database/
│   │   ├── Migrations/       # 30+ schema migrations
│   │   └── Seeds/            # Demo users + sample data
│   └── Config/               # CI4 config (Routes, Database, Filters, etc.)
├── public/
│   ├── index.php             # Web entry point
│   └── assets/img/           # Logo SVGs (logo.svg, text.svg, logowtext.svg)
├── writable/
│   ├── cache/                # CI4 cache
│   ├── logs/                 # Application logs
│   ├── session/              # File-based session storage
│   └── uploads/              # User-uploaded files
├── Database/
│   └── synapse_ag.sql        # Full schema dump
├── Diagrams/
│   ├── Flowchart/            # PlantUML module diagrams
│   └── GanttChart/           # Project timeline
├── Documents/
│   ├── Synapse_ A Unified Web-Based Campus Health and Counseling Management System.md
│   ├── SYNAPSE_CH1_2 (1).md
│   └── Analysis/             # AI feature analysis
├── scratch/                  # One-off utility scripts (reset users, smoke tests)
├── tests/                    # PHPUnit test suite
├── vendor/                   # Composer dependencies
├── env                       # Environment template (copy to .env)
├── composer.json
├── phpunit.dist.xml
└── spark                     # CI4 CLI entry point
```

---

## Server Requirements

- **PHP 8.2 or higher** with these extensions:
  - `intl`
  - `mbstring`
  - `json` (enabled by default)
  - `mysqlnd` (for MySQL)
  - `libcurl` (for `HTTP\CURLRequest`)
- **MySQL 8.0+** or **MariaDB 10.4+**
- **Apache** with `mod_rewrite` enabled (or Nginx with equivalent rewrite rules)
- **Composer 2.x**

> [!WARNING]
> PHP 8.2 reaches end-of-life on **December 31, 2026**. Plan an upgrade to 8.3+ before then.

---

## Installation & Setup

### 1. Clone the repository

```bash
git clone https://github.com/your-org/synapse_ag.git
cd synapse_ag
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure the environment

```bash
cp env .env
```

Edit `.env` and set:

```ini
app.baseURL = 'http://localhost/synapse_ag/'
CI_ENVIRONMENT = development

database.default.hostname = localhost
database.default.database = synapse_ag
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

### 4. Import the database

Using phpMyAdmin (XAMPP):

1. Open `http://localhost/phpmyadmin`
2. Create a database named `synapse_ag`
3. Import `Database/synapse_ag.sql`

Or via CLI:

```bash
mysql -u root -p synapse_ag < Database/synapse_ag.sql
```

### 5. Run migrations (optional — only if starting from scratch)

```bash
php spark migrate
php spark db:seed InitialSeeder
```

### 6. Configure your web server

**Apache (XAMPP):** Place the project in `htdocs/synapse_ag/` and access it at `http://localhost/synapse_ag/public/` — or configure a virtual host pointing to the `public/` directory.

**Nginx:** Point the document root to the `public/` folder and add the standard CI4 rewrite rules.

### 7. First login

Open the app and use one of the [default test credentials](#default-test-credentials).

---

## Default Test Credentials

> These credentials are seeded by `app/Database/Seeds/StudentSeeder.php` for local development and UAT only. **Change all passwords before deploying to any environment with real data.**

| Role | Email | Password |
|---|---|---|
| **Administrator** | `admin@synapse.edu.ph` | `TestAdmin123!` |
| **Clinic Staff** | `clinic@synapse.edu.ph` | `TestPass123!` |
| **Counsellor** | `counsellor@synapse.edu.ph` | `TestPass123!` |
| **Student** | `maria.santos@feu.edu.ph` | `TestPass123!` |

If you've lost track of passwords during development, the `scratch/` directory contains helpers like `scratch/reset_admin.php` and `scratch/reset_test_users.php` that reset specific accounts.

---

## AI Subsystems

SYNAPSE ships with six domain libraries that provide AI-assisted decision support. They run **on the PHP backend** — no external API calls, no model weights — using deterministic scoring algorithms tuned for the campus health domain.

| Library | Purpose | File |
|---|---|---|
| `TriageAssistant` | Suggests urgency tier for incoming clinic visits based on presenting complaint + vitals | `app/Libraries/TriageAssistant.php` |
| `RiskScorer` | Computes a student-level composite risk from screening scores, missed appointments, and referral history | `app/Libraries/RiskScorer.php` |
| `SchedulingOptimizer` | Detects double-bookings and suggests slot reassignment for counsellor availability | `app/Libraries/SchedulingOptimizer.php` |
| `InventoryForecaster` | Predicts depletion dates for medicine batches based on historical transaction velocity | `app/Libraries/InventoryForecaster.php` |
| `ReportSummarizer` | Generates plain-English narrative summaries for the analytics dashboards | `app/Libraries/ReportSummarizer.php` |
| `ConflictDetector` | Detects schedule overlaps between counselling appointments and clinic duty shifts | `app/Libraries/ConflictDetector.php` |

Each library's output is **advisory only** — clinicians and counsellors retain full override authority. Every override is recorded in the audit trail.

---

## IoT Subsystem

The IoT module (`app/Controllers/Iot/`) provides a **pluggable scan-listener interface** for student identification at clinic reception. Two hardware modalities are supported:

- **QR Code** — embedded directly in the official institutional ID; encodes only the student ID number (no PHI)
- **RFID** — optional upgrade using NFC-capable USB readers; treated as institutional option, not a default

### Kiosk mode

A lightweight kiosk view (`app/Views/iot/kiosk.php`) runs on reception tablets and displays: student name, photo, contact, known allergies, and current referral status. It does **not** display counselling session notes, screening scores, or diagnoses.

### Offline fallback

`OfflineCheckinBufferModel` queues scan events locally when the network is down and syncs to the server when connectivity is restored. All buffered events are flushed atomically on reconnect.

---

## Security & Privacy

SYNAPSE implements the security framework specified in the project proposal:

- **Authentication** — `password_hash()` (Argon2/bcrypt) + `password_verify()`; CSRF tokens on every POST form; CI4 built-in session regeneration
- **Authorization** — RBAC enforced by `AuthFilter` + `RoleFilter`; permission checks at controller entry, not just route level
- **Brute-force protection** — 5 failed attempts per 15 minutes, keyed by hashed email
- **Audit trail** — every authentication, read, write, and override logged with timestamp + user ID + role + action type + affected record ID; logs are write-once, hash-chained, verified nightly by `AuditController::verify()`
- **Input escaping** — all view output uses `esc()`; SQL queries go through CI4 Query Builder (prepared statements)
- **Session security** — HTTP-only cookies, secure flag when over HTTPS, automatic regeneration on login

> ⚠️ The codebase still has some `<?= old('field') ?>` patterns without `esc()` wrapping (e.g., `app/Views/clinic/students/form.php`, `app/Views/inventory/medicines/form.php`) — these are scheduled for hardening in the next security pass.

---

## Documentation

| Document | Purpose |
|---|---|
| `Documents/Synapse_ A Unified Web-Based Campus Health and Counseling Management System.md` | Full capstone proposal (Chapters 1–3) |
| `Documents/SYNAPSE_CH1_2 (1).md` | Chapters 1–2 (Background, Related Literature) |
| `Documents/Analysis/analysis_and_ai_features.md` | AI feature design rationale |
| `Database/synapse_ag.sql` | Full schema dump |
| `Diagrams/Flowchart/modules/` | PlantUML module flowcharts |
| `Diagrams/GanttChart/` | Project timeline |
| `LICENSE` | MIT license |

---

## Development Workflow

### Running the dev server

```bash
php spark serve
```

### Running tests

```bash
vendor/bin/phpunit
```

### Clearing caches

```bash
php spark cache:clear
```

### Database migrations

```bash
php spark migrate              # run pending
php spark migrate:rollback     # rollback last batch
php spark migrate:status       # list applied
```

---

## License

This project is released under the **MIT License**. See [LICENSE](LICENSE) for details.

---

## Author & Acknowledgements

**SYNAPSE** is a capstone project submitted to the **College of Computer Studies, Foundation University**, in partial fulfillment of the requirements for the degree of **Bachelor of Science in Computer Science**.

**Author:** Rafael Torres — 2024–2026

Special thanks to the capstone panel reviewers, the Foundation University Health Services Office, and the Guidance and Counselling Office for their domain expertise and pilot-testing support.

---

<p align="center">
  <sub>Built with CodeIgniter 4.7 · PHP 8.2 · MySQL 8.0 · Chart.js · ❤️</sub>
</p>
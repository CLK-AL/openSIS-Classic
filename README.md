# openSIS Classic (Fork)

Community Edition version 9.3 | Forked from [OS4ED/openSIS-Classic](https://github.com/OS4ED/openSIS-Classic)

openSIS is a Student Information System for K-12, trade schools, and higher education. This fork adds RTL support, multi-language translations, SQLite backend, Docker/local installation, white-label branding, a Translation Manager UI, comprehensive test suites, and architecture documentation.

## Fork Modifications vs Upstream

| Area | What changed |
|------|-------------|
| **RTL Support** | Fixed `dir="ltr"` HTML bug, added `assets/css/rtl.css` with full Bootstrap 3 RTL overrides (grid, forms, navbar, tables, modals, dropdowns), conditional loading on all entry points |
| **Languages** | Aligned all 2,039 translation keys across EN/AR/FR/ES/HE. Added complete Hebrew (he) RTL translation. Fixed Arabic trailing-space key bug. Added 17 missing keys to English from non-English files |
| **Translation Manager** | New UI at Tools > Translation Manager with DB-backed `i18n` table (compound PK: key+lang), import/export from lang files, paged editing, search/filter, per-row save, RTL textareas |
| **SQLite Support** | `SqliteAdapter.php` — drop-in SQLite backend with automatic MySQL-to-SQLite SQL translation (AUTO_INCREMENT, CONCAT, NOW, LIMIT/OFFSET, etc.). Zero-config single-file database for small schools |
| **Docker** | `Dockerfile` (PHP 8.3/Apache/Bookworm), `docker-compose.yml` (app + MySQL 8.0), `docker-entrypoint.sh` (auto-generates Data.php from env vars) |
| **Local Install** | `install.sh` (Ubuntu/Debian/Fedora full install), `uninstall.sh`, `dev.sh` (PHP built-in server for development) |
| **White-Label** | `WhiteLabelInc.php` + `assets/branding/` — drop in custom logo/favicon, configure app name and footer from one file |
| **Library** | Book catalog with ISBN/author/barcode, checkout/return to students/staff, overdue tracking, category management, inventory reports |
| **Inventory** | Lab equipment catalog with serial/asset tag/condition/warranty/cost, checkout with quantity tracking, maintenance log (preventive/repair/calibration) |
| **Field Trips** | Trip planning with destination, dates, cost/budget, student enrollment with consent and payment tracking, iCal VEVENT export |
| **Birthdays** | Monthly birthday calendar from student data, gift budget/collection tracking, status flow (collecting→purchased→delivered), iCal export |
| **Finance** | Collections and payments from parents (8 categories: Field Trip, Birthday, Lab Fee, Book Fee, etc.), receipt tracking, finance reports |
| **vCard/iCal** | Export students/staff/parents as .vcf contacts; export events/school days/marking periods as .ics; import .vcf and .ics files |
| **DB Migration** | `migrate-db.php` — CLI tool to migrate between SQLite, MySQL, and PostgreSQL with automatic type mapping |
| **PHPUnit Tests** | 392 tests / 768 assertions: security, input validation, date/time, UI generation, DB-dependent via TSV mock and SQLite, full donation-classroom E2E simulation |
| **Playwright E2E** | 113 browser tests across 14 spec files covering all 11 modules: auth, navigation, RTL, school setup, students, users, scheduling, grades, attendance, messaging, tools, eligibility |
| **Architecture Docs** | 16 PlantUML diagrams: module workflows per role, authentication flow, data model, role-access matrix, test coverage map |
| **Bug Fixes** | Missing closing quote on `dir="ltr"` (Warehouse.php), uninitialized `$langCode` (language.php), missing semicolon (supportedLanguages.php), debug var_dumps removed (LoginInc.php), `langDirection()` null-safety |

## Quick Start

### Docker (recommended)

```bash
cp .env.example .env
docker compose up -d
open http://localhost:8080/install/
```

### Development (no root, no Apache)

```bash
./dev.sh              # http://localhost:8080
./dev.sh 9090         # custom port
```

### Production Linux Install

```bash
sudo ./install.sh
open http://localhost/install/
```

### SQLite (no MySQL needed)

Edit `Data.php`:
```php
$DatabaseType = 'sqlite';
$DatabaseName = __DIR__ . '/data/opensis.db';
```

## Requirements

| Component | Docker | Local |
|-----------|--------|-------|
| PHP | 8.3 (included) | 8.x + mysqli, gd, zip, intl, mbstring |
| Database | MySQL 8.0 (included) | MySQL 5.7+ / MariaDB 10.4+ / SQLite 3 |
| Web Server | Apache (included) | Apache 2.4+ or PHP built-in server |

## Supported Languages

| Language | Code | Direction | Coverage |
|----------|------|-----------|----------|
| English | en | LTR | 2,039 keys (base) |
| Arabic | ar | RTL | 2,030 keys (100%) |
| French | fr | LTR | 2,047 keys (100%) |
| Spanish | es | LTR | 2,031 keys (100%) |
| Hebrew | he | RTL | 2,039 keys (100%) |

Manage translations at **Tools > Translation Manager**.

## White-Label Branding

Drop custom files in `assets/branding/`:

| File | Size | Where |
|------|------|-------|
| `logo.png` | ~200x50px | Navbar (every page) |
| `logo-login.png` | ~300x80px | Login, forgot password |
| `favicon.ico` | 32x32 | Browser tab |

Edit `WhiteLabelInc.php` to change app name, title, and footer.

## Testing

### PHPUnit (392 tests)

```bash
composer install
./vendor/bin/phpunit                    # all tests
./vendor/bin/phpunit --testsuite Unit   # unit only
./vendor/bin/phpunit --testsuite Integration  # integration only
```

### Playwright E2E (113 tests)

```bash
npm install
npx playwright install chromium
BASE_URL=http://localhost:8080 npm run test:e2e
```

See [tests.md](tests.md) for detailed test documentation.

## Architecture Documentation

PlantUML diagrams in `docs/diagrams/`:

| Diagram | Content |
|---------|---------|
| `system-overview.puml` | All modules with role access |
| `role-access-matrix.puml` | Page counts per role per module |
| `authentication-flow.puml` | Login, CSRF, session, request routing |
| `data-model.puml` | Core entity relationships |
| `test-coverage.puml` | Function coverage map (green/yellow) |
| `school-setup.puml` | School Setup menu workflow |
| `students.puml` | Students menu workflow |
| `users.puml` | Users menu workflow |
| `scheduling.puml` | Scheduling menu workflow |
| `grades.puml` | Grades menu workflow |
| `attendance.puml` | Attendance menu workflow |
| `eligibility.puml` | Eligibility menu workflow |
| `library.puml` | Library (Books) menu workflow |
| `inventory.puml` | Inventory + Field Trips + Birthdays + Finance |
| `messaging.puml` | Messaging menu workflow |
| `tools.puml` | Tools + vCard/iCal + Translation Manager |

Render: `plantuml docs/diagrams/*.puml`

## Project Structure

```
openSIS-Classic/
├── Dockerfile, docker-compose.yml    # Docker setup
├── install.sh, uninstall.sh, dev.sh  # Local Linux scripts
├── WhiteLabelInc.php                 # White-label config
├── SqliteAdapter.php                 # SQLite backend
├── DatabaseInc.php                   # DB layer (MySQL + SQLite)
├── phpunit.xml                       # PHPUnit config
├── playwright.config.ts              # Playwright config
├── assets/
│   ├── branding/                     # Custom logo/favicon drop-in
│   └── css/rtl.css                   # RTL stylesheet
├── docs/
│   └── diagrams/*.puml               # 16 PlantUML diagrams
├── e2e/                              # 113 Playwright E2E tests
│   ├── fixtures.ts                   # Login/navigate helpers
│   ├── auth.spec.ts                  # Authentication tests
│   ├── rtl.spec.ts                   # RTL tests
│   └── *.spec.ts                     # Module tests
├── lang/
│   ├── supportedLanguages.php        # Language registry
│   ├── lang_en/ar/fr/es/he.php       # Translation files
│   └── language.php                  # Language loader
├── modules/
│   ├── library/                      # Book catalog, checkout, overdue
│   ├── inventory/                    # Equipment, trips, birthdays, finance
│   └── tools/
│       ├── TranslationManager.php    # Translation Manager UI
│       ├── VCardExport.php           # vCard import/export
│       └── ICalExport.php            # iCal import/export
├── tests/
│   ├── bootstrap.php                 # Test setup
│   ├── TsvMock.php                   # TSV-based DB mock
│   ├── Unit/                         # 19 unit test suites
│   └── Integration/                  # 6 integration test suites
├── bootstrap-upgrade.md              # Bootstrap 3→5 upgrade plan
└── tests.md                          # Full test documentation
```

## Other Documentation

- [tests.md](tests.md) — Full test suite documentation (392 tests)
- [bootstrap-upgrade.md](bootstrap-upgrade.md) — Bootstrap 3.3.5 → 5.x upgrade plan
- [assets/branding/README.md](assets/branding/README.md) — White-label instructions

## License

openSIS is licensed under the [GNU General Public License v2](docs/License.txt).

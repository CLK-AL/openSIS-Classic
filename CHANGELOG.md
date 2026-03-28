# Changelog — Fork Modifications

All changes in this fork compared to [OS4ED/openSIS-Classic](https://github.com/OS4ED/openSIS-Classic) upstream.

## Library Module (New)

- **Added** `modules/library/` — complete book management system
- Books.php: Book catalog with search, add/edit/delete, ISBN, author, barcode, categories
- Categories.php: Book category management
- Checkout.php: Check out books to students/staff, return with date tracking
- Overdue.php: Overdue books list with days-overdue calculation
- BookReport.php: Inventory stats by category (titles, copies, available, checked out)
- SetupInc.php: Auto-creates `library_books`, `library_book_categories`, `library_checkout` tables

## Inventory Module (New)

- **Added** `modules/inventory/` — equipment and school operations management
- Equipment.php: Lab equipment catalog with serial, asset tag, condition, cost, warranty
- Checkout.php: Equipment checkout to staff/students with quantity tracking
- Maintenance.php: Maintenance log (preventive, repair, calibration, inspection) with cost
- Categories.php: Equipment categories (8 defaults seeded)
- Locations.php: Storage locations (room + building)
- EquipmentReport.php: Stats by category, condition, total asset value

## Field Trips, Birthdays & Finance (New)

- **Added** FieldTrips.php: Trip planning with destination, dates, times, cost/budget, student enrollment, consent tracking, iCal VEVENT export
- **Added** Birthdays.php: Monthly birthday calendar from student birthdates, gift budget/collection, status tracking (collecting→purchased→delivered), iCal export
- **Added** Finance.php: Collections and payments from parents (8 categories: Field Trip, Birthday Gift, Lab Fee, Book Fee, Activity Fee, Donation, Supplies, General), receipt tracking
- **Added** FinanceReport.php: Dashboard with collected/pending/overdue totals, breakdown by category
- Tables: `inventory_field_trips`, `inventory_trip_students`, `inventory_finance`, `inventory_birthdays`

## vCard & iCal Import/Export (New)

- **Added** `modules/tools/VCardExport.php` — export students/staff/parents as .vcf, import .vcf as parent contacts
- **Added** `modules/tools/ICalExport.php` — export events/school days/marking periods as .ics, import .ics events
- **Added** `functions/VCardFnc.php` — vCard 3.0 build/parse library (RFC 6350)
- **Added** `functions/ICalFnc.php` — iCalendar build/parse library (RFC 5545)

## Database Migration Tool (New)

- **Added** `migrate-db.php` — CLI tool for SQLite ↔ MySQL ↔ PostgreSQL migration
- Full type mapping (20+ types), compound PK support, Unicode preservation
- Auto-detects source from Data.php, prints target Data.php config

## RTL & Language Support

- **Fixed** missing closing `"` on `dir="ltr"` HTML attribute in `Warehouse.php` (malformed HTML on all LTR pages)
- **Fixed** uninitialized `$langCode` in `lang/language.php` causing PHP include errors
- **Fixed** missing semicolon in `lang/supportedLanguages.php`
- **Fixed** `langDirection()` null-safety for unset session
- **Fixed** Arabic `_teachersWhoHaventTaken` key had trailing space
- **Fixed** `generalInfo` English value from raw key name to "General Info"
- **Removed** debug `var_dump` lines from `LoginInc.php`
- **Added** `assets/css/rtl.css` — comprehensive Bootstrap 3 RTL overrides (grid, forms, navbar, tables, dropdowns, modals, breadcrumbs, pagination, utilities)
- **Added** conditional RTL CSS loading in `Modules.php`, `LoginInc.php`, `ForgotPass.php`, `ForWindow.php`
- **Added** dynamic `lang` attribute on `<html>` based on session language
- **Moved** scattered RTL rules from `core.css` into dedicated `rtl.css`

## Translations

- **Aligned** all 1,948 English keys present in every language file (EN, AR, FR, ES, HE)
- **Added** 25 missing keys to French with translations
- **Added** 9 missing keys to Spanish with translations
- **Added** 3 missing keys to Arabic with translations
- **Added** 17 keys from non-English files back into English
- **Added** `lang/lang_he.php` — complete Hebrew (RTL) translation (1,943/1,948 keys translated)
- **Added** Hebrew to `supportedLanguages.php` with `direction => 'rtl'`

## Translation Manager

- **Added** `modules/tools/TranslationManager.php` — web UI for managing translations
  - `i18n` database table with compound primary key (`translation_key`, `lang`)
  - Import from / export to lang files
  - Paged editing grid (25/50/75/100 per page)
  - Language filter, text search, missing-only filter
  - Per-row save with CSRF protection
  - Add/delete keys across all languages
  - RTL textarea support for Arabic/Hebrew
  - Completion stats per language
- **Added** menu entry in Tools module

## SQLite Support

- **Added** `SqliteAdapter.php` — drop-in SQLite backend
  - `SqliteConnection` wraps PDO SQLite with mysqli-compatible `query()` API
  - `SqliteResultSet` wraps PDOStatement with `fetch_assoc()` interface
  - Automatic MySQL-to-SQLite SQL translation (30+ patterns)
- **Modified** `DatabaseInc.php` — added `sqlite` case to `db_start()`, `DBQuery()`, `db_fetch_row()`, `db_seq_nextval()`, `db_properties()`
- **Modified** top-level connection init to skip `mysqli_connect` for SQLite

## Docker

- **Added** `Dockerfile` — PHP 8.3 Apache on Debian Bookworm with mysqli/gd/zip/intl/opcache
- **Added** `docker-compose.yml` — app + MySQL 8.0 with healthchecks, named volumes, bridge network
- **Added** `docker-entrypoint.sh` — auto-generates `Data.php` from environment variables
- **Added** `.env.example` — configuration template
- **Added** `.dockerignore` — excludes git, tests, vendor, docs

## Local Install Scripts

- **Added** `install.sh` — full production install (Ubuntu/Debian/Fedora: Apache + MySQL + PHP)
- **Added** `uninstall.sh` — clean removal (DB, files, Apache config)
- **Added** `dev.sh` — PHP built-in dev server for local development

## White-Label Branding

- **Added** `WhiteLabelInc.php` — centralized branding config with auto-detection of `assets/branding/` files
- **Added** `assets/branding/` directory with README
- **Modified** `Warehouse.php` — favicon uses `WhiteLabel('favicon')`
- **Modified** `Modules.php` — navbar logo uses `WhiteLabel('logo')`
- **Modified** `LoginInc.php` — login logo uses `WhiteLabel('logo_login')`
- **Modified** `ForgotPass.php` — forgot password logo uses `WhiteLabel('logo_login')`
- **Modified** `ResetUserInfo.php` — reset password logo uses `WhiteLabel('logo_login')`

## Test Suite

### PHPUnit (332 tests, 575 assertions)

Unit tests (25 suites):
- `PasswordHashTest` (9), `CSRFSecurityTest` (11), `SqlSecurityFilterTest` (22), `CleanParamTest` (24), `MonthNwSwitchTest` (30), `LangDirectionTest` (8), `PercentTest` (10), `DbDateTest` (13), `SortFncTest` (10), `BrowserTest` (6), `ButtonsTest` (3), `ReindexResultsTest` (4), `ErrorMessageTest` (8), `ShowVarTest` (4), `DrawTabTest` (8), `DrawHeaderTest` (5), `PopTableTest` (4), `UrlFncTest` (5), `PreparePhpSelfTest` (5), `TsvMockTest` (11), `ConfigFncTest` (4), `DeCodedsTest` (6), `UserFncTest` (8), `AllowEditTest` (14), `SqliteAdapterTest` (16)

Integration tests (6 suites):
- `AuthenticationWorkflowTest` (8), `InputValidationWorkflowTest` (16), `SessionSecurityWorkflowTest` (10), `LanguageWorkflowTest` (10), `DateWorkflowTest` (10), `DBGetWorkflowTest` (12)

Infrastructure:
- `TsvMock.php` — TSV-based DB mock (replaces `db_fetch_row`/`DBQuery` for testing)
- `phpunit.xml` — test suite configuration
- `composer.json` — PHPUnit 11 dependency

### Playwright E2E (62 tests)

- `auth.spec.ts` (10), `navigation.spec.ts` (4), `school-setup.spec.ts` (8), `students.spec.ts` (6), `users.spec.ts` (6), `scheduling.spec.ts` (6), `grades.spec.ts` (7), `attendance.spec.ts` (5), `messaging.spec.ts` (5), `tools.spec.ts` (7), `eligibility.spec.ts` (3), `rtl.spec.ts` (7)

Infrastructure:
- `playwright.config.ts` — Chromium project, configurable `BASE_URL`
- `e2e/fixtures.ts` — `login()`, `navigateTo()` helpers

## Architecture Documentation

- **Added** 13 PlantUML diagrams in `docs/diagrams/`:
  - Module workflows: school-setup, students, users, scheduling, grades, attendance, eligibility, messaging, tools
  - Architecture: system-overview, role-access-matrix, authentication-flow, data-model, test-coverage
- **Added** `bootstrap-upgrade.md` — Bootstrap 3.3.5 → 5.x migration plan with effort estimates
- **Added** `tests.md` — full test suite documentation
- **Updated** `README.md` — complete fork documentation

## Configuration Files

- **Added** `.gitignore` — vendor, node_modules, Data.php, .env, test artifacts
- **Added** `package.json` — Playwright dependency and npm scripts

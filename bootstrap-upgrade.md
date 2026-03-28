# Bootstrap Upgrade Plan: 3.3.5 → 5.x

## Current State

- **Bootstrap CSS**: 3.3.5 (`assets/css/bootstrap.css`)
- **Bootstrap JS**: 3.3.7 (`assets/js/core/libraries/bootstrap.min.js`)
- **jQuery**: 2.1.4
- **Jasny Bootstrap**: 3.1.3 (off-canvas nav, input masking)
- **LESS source files**: `assets/less/_main_full/`

## Scope Summary

| Area | Occurrences | Files Affected |
|------|-------------|----------------|
| Grid columns (`col-xs-*` removed in BS5) | 2,528 | 126 |
| Panels → Cards (`panel` removed) | 432 | 105 |
| Form groups (`form-group` changed) | 1,130 | 173 |
| Buttons (`btn-default`, `btn-xs` removed) | 741 | 164 |
| Modals (API + `data-bs-*` prefix) | 445 | 48+ |
| Float utilities (`pull-left/right` removed) | — | 26 |
| Visibility classes (`hidden-xs` etc. removed) | — | 2 |
| Wells (removed) | — | 22 |
| Glyphicons (removed) | — | 2 |
| Custom CSS overrides | 1,700+ selectors | 9 CSS files |
| LESS source files | — | 4+ |
| JS components (jQuery → vanilla) | — | 24+ theme files + app.js |
| Jasny Bootstrap (no BS5 equivalent) | — | off-canvas nav, input mask |
| Color theme JS/LESS files | — | 24 |

## Major Breaking Changes

### 1. Grid System
- `col-xs-*` removed — use `col-*` instead
- `col-*-offset-*` renamed to `offset-*-*`
- `col-*-push/pull` replaced by `order-*` utilities
- Affects 2,528 occurrences across 126 files

### 2. Components Removed or Renamed
- **Panels → Cards**: `panel` / `panel-heading` / `panel-body` → `card` / `card-header` / `card-body` (432 occurrences, 105 files)
- **Wells → removed**: replace with `card` or custom CSS (22 files)
- **Glyphicons → removed**: already mostly using icomoon/fontawesome (2 files)
- **Labels → Badges**: `label` class merged into `badge`
- **`btn-default` → `btn-secondary`**, `btn-xs` removed entirely

### 3. JavaScript API
- All `data-toggle` attributes become `data-bs-toggle`
- All `data-dismiss` attributes become `data-bs-dismiss`
- jQuery plugin API removed — `$(el).modal()` becomes `new bootstrap.Modal(el)`
- Affects `app.js`, 24 color theme files, and all modal/tooltip/popover usage

### 4. Utility Classes
- `pull-left` / `pull-right` → `float-start` / `float-end`
- `hidden-xs` / `visible-xs` → `d-none` / `d-sm-block` etc.
- `img-responsive` → `img-fluid`
- `text-left` / `text-right` → `text-start` / `text-end`

### 5. RTL Support
- Bootstrap 5 has **native RTL support** via `bootstrap.rtl.min.css`
- The current custom `assets/css/rtl.css` can be mostly replaced
- `pull-left/right` RTL overrides in `custom.css` become unnecessary
- Float/text utilities are direction-aware in BS5

### 6. Jasny Bootstrap
- No Bootstrap 5 port exists
- Off-canvas navigation → use BS5 built-in Offcanvas component
- Input masking → use a standalone library (e.g., inputmask)
- Row links → custom JS or CSS

### 7. Forms
- `form-group` still works but is no longer documented; replaced by margin utilities
- `form-inline` removed — use grid or utility classes
- Floating labels are a new native option

## Phased Migration Plan

### Phase 1: Centralized Files (2-3 days)
Update files that generate Bootstrap markup for the entire app:
- `functions/ButtonFnc.php` — button class generation
- `functions/ButtonsFnc.php` — button group generation
- `functions/ListOutputFnc.php` — list/table output
- `Warehouse.php` — page wrapper and header
- `Modules.php` — CSS/JS includes

### Phase 2: Grid & Layout (2-3 days)
- Find-replace `col-xs-` → `col-` across 126 files
- Update offset/push/pull classes
- Test responsive breakpoints

### Phase 3: Panels → Cards (4-5 days)
- Convert panel markup to card markup in 105 files
- Update associated CSS in `core.css`, `components.css`, `custom.css`
- Modules with heaviest usage: grades, scheduling, students, schoolsetup, messaging

### Phase 4: Buttons & Forms (2-3 days)
- `btn-default` → `btn-secondary` (164 files)
- Remove or replace `btn-xs` sizing
- Update form markup and classes (173 files)

### Phase 5: Modals (2-3 days)
- Update `data-toggle` → `data-bs-toggle` and `data-dismiss` → `data-bs-dismiss`
- Replace jQuery modal calls with vanilla JS API
- Test all modal flows (48+ files)

### Phase 6: JavaScript Migration (3-4 days)
- Replace `assets/js/core/libraries/bootstrap.min.js` with BS5 bundle
- Rewrite `assets/js/core/app.js` — tooltip, popover, collapse, dropdown init
- Update 24 color theme JS files
- Remove jQuery dependency from Bootstrap calls (jQuery itself can remain for other usage)

### Phase 7: Jasny Bootstrap Replacement (2-3 days)
- Replace off-canvas sidebar with BS5 Offcanvas component
- Replace input masking with standalone library
- Remove `jasny_bootstrap.min.js`

### Phase 8: CSS Overhaul (5-7 days)
- Rewrite `assets/css/core.css` (1,134 Bootstrap references)
- Rewrite `assets/css/components.css` (593 Bootstrap references)
- Update `assets/css/colors.css` and 24 color LESS files
- Update `assets/css/custom.css` Bootstrap overrides section
- Migrate LESS → Sass (BS5 uses Sass, not LESS)

### Phase 9: RTL Simplification (1-2 days)
- Replace `assets/css/rtl.css` with BS5 `bootstrap.rtl.min.css`
- Remove manual float/text direction overrides
- Test Arabic layout across all modules

### Phase 10: Utilities & Cleanup (1-2 days)
- `pull-left/right` → `float-start/end` (26 files)
- `hidden-xs` etc. → `d-*` display utilities (2 files)
- `img-responsive` → `img-fluid` (1 file)
- Remove well classes (22 files)
- Remove glyphicon references (2 files)

### Phase 11: Testing & Regression (3-5 days)
- Test every module in LTR and RTL modes
- Test all modals, forms, and interactive components
- Cross-browser testing (Chrome, Firefox, Safari, Edge)
- Mobile/responsive testing

## Estimated Total Effort

**25-37 developer-days (5-8 weeks for one developer)**

## Risk Factors

- No automated test suite — all regression testing is manual
- PHP functions generate Bootstrap markup as strings — harder than template-based find-replace
- 24 color themes multiply the CSS work
- Jasny Bootstrap has no drop-in BS5 replacement

## Modules by Migration Complexity

| Module | Complexity | Key Concerns |
|--------|------------|--------------|
| `modules/grades/` | High | Heavy panel, form, modal usage |
| `modules/scheduling/` | High | Grid system, panels, modals, collapse |
| `modules/students/` | High | Forms, panels, modals, file uploads |
| `modules/schoolsetup/` | Medium | Panels, forms, course management |
| `modules/messaging/` | Medium | Modals, list-groups, panels |
| `modules/users/` | Medium | Forms, user management panels |
| `modules/attendance/` | Medium | Data tables, alerts, forms |
| `modules/tools/` | Medium | DataImport.php has 36 panel occurrences |
| `modules/eligibility/` | Low | Limited Bootstrap usage |
| `modules/miscellaneous/` | Low | Export functionality |

## Quick Wins (Can Be Done Independently)

These changes are backwards-compatible with BS3 and can be done before the full migration:
1. Replace glyphicon usage with fontawesome equivalents (2 files)
2. Add `id` attributes to modals that lack them (preparation for BS5 JS API)
3. Standardize button sizes — remove `btn-xs`, use `btn-sm` instead
4. Replace `img-responsive` with both `img-responsive img-fluid` (works in both versions)

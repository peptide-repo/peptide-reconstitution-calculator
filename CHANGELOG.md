# Changelog

All notable changes to Peptide Reconstitution Calculator are documented here.
Format: [Semantic Versioning](https://semver.org/).

## [1.2.0] — 2026-06-16

### Added
- PHPUnit test suite (WP stubs, `tests: stubs`) — 40 tests across four classes:
  - `MathTest`: 25 tests covering all PRC_Math methods — concentration mg/mL,
    mcg/unit, injection volume, syringe units, doses per vial, and the full
    `calculate()` orchestrator with named peptide scenarios (BPC-157, TB-500,
    Semaglutide, MK-677, Ipamorelin) plus edge cases (zero water, zero dose,
    negative inputs, floor rounding, rounding precision).
  - `DefaultPresetsTest`: 11 tests — collection count, all 8 expected slugs present,
    required keys on every preset (via dataProvider), valid dose units, min < max,
    source='default', and spot-checks on BPC-157 / Semaglutide / MK-677.
  - `RestControllerTest`: 7 tests — calculate() 200 response + correct values for
    mcg and mg dose units; get_presets() returns 200; get_preset() 200 for known
    slug and WP_Error for unknown slug.
  - `PresetProviderTest`: 4 tests — fallback to defaults without PR Core, all
    presets have slug, cache invalidation, idempotent invalidation.
- `phpunit.xml.dist` + `tests/bootstrap.php` (WP function/class stubs).
- `composer test` script (`vendor/bin/phpunit --configuration phpunit.xml.dist`).
- `phpunit/phpunit ^9.6` and `yoast/phpunit-polyfills ^2.0` in `require-dev`.

### Changed
- `ci.yml` replaced with thin caller delegating to estate reusable workflow
  (`peptide-e2e/.github/workflows/ci.yml@main`; `tests: stubs`, `has_js: true`,
  `permissions: contents: write`, `workflow_call` so `deploy.yml` gate works).

## [1.1.0] — 2026-04-26

### Changed
- `assets/css/calculator.css`: adopted brand tokens — header gradient → teal (was blue), focus states → teal, results panel → teal-50, font → Inter; dark mode updated to teal scale

## [1.0.0] — 2026-04-17

### Added
- Interactive reconstitution calculator via `[prc_calculator]` shortcode.
- Client-side instant calculation: concentration (mg/mL), mcg per syringe unit, injection volume (mL), syringe units (IU), doses per vial.
- Peptide-specific presets from Peptide Repo Core (when active) with live dosing data.
- 8 built-in default presets: BPC-157, TB-500, Semaglutide, CJC-1295 (DAC), Ipamorelin, PT-141, MK-677, GHRP-6.
- Preset pre-selection via shortcode attribute: `[prc_calculator peptide="bpc-157"]`.
- REST API (`prc/v1`): GET /presets, GET /presets/{slug}, POST /calculate.
- Server-side math engine (`PRC_Math`) mirroring client-side calculations.
- Transient-cached presets with event-driven invalidation on PR Core data changes.
- Admin notice when PR Core is not installed (informational, not blocking).
- Dark mode support via `prefers-color-scheme`.
- Responsive design down to 320px viewport.
- Full teardown in `uninstall.php` (options + transients).
- CI workflow: PHP lint (8.1/8.2/8.3), PHPCS WordPress, 300-line file check.

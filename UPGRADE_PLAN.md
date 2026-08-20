# Upgrade Plan: `aclai/piton` — Laravel 8 → Laravel 13

**Current state:** package targets Laravel 8 (`orchestra/testbench ^6.17`), no PHP version
constraint, `doctrine/dbal` pinned to `2.10.0`, legacy (Laravel 7-style) model factories,
PHPUnit 9-era `phpunit.xml`, and tests that require a live MySQL server with hardcoded
credentials.

**Target state:** Laravel 13 / PHP 8.3+ / Testbench 11 / PHPUnit 12, class-based factories,
SQLite in-memory test database, modernized test suite with substantially expanded coverage.

Version ladder reference (per the official upgrade guides 7.x → 13.x):

| Laravel | PHP    | Testbench | PHPUnit | Changes that touch this package |
|---------|--------|-----------|---------|----------------------------------|
| 8       | ^7.3   | 6         | 9       | **Model factories rewritten** (class-based; legacy `$factory->define` incompatible) |
| 9       | ^8.0.2 | 7         | 9       | Symfony 6 components |
| 10      | ^8.1   | 8         | 10      | Doc-comment test metadata deprecated |
| 11      | ^8.2   | 9         | 10/11   | `casts()` method on models; SQLite 3.26+ |
| 12      | ^8.2   | 10        | 11      | Carbon 3; no direct impact |
| 13      | ^8.3   | 11        | **12**  | `symfony/polyfill-php85` global-function conflicts; PHPUnit 12 removes `/** @test */` annotations |

Because we jump from 8 → 13 in one step, only the cumulative changes listed below apply
(no intermediate releases need to be installed).

---

## Phase 0 — Reference Laravel 13 projects for comparison

During implementation, agents MAY scaffold throwaway reference projects to validate
decisions against Laravel 13 defaults — never inside this repo, always under `/tmp`:

```bash
composer create-project laravel/laravel /tmp/laravel13-ref "^13.0"
```

Uses:
- Compare skeleton defaults (`phpunit.xml` schema, composer scripts, config layout)
  before adopting conventions in this package.
- **Smoke-install test:** `composer config repositories.piton path <repo>` +
  `composer require aclai/piton:*@dev` in the reference app to prove the upgraded
  package installs, the service provider boots, migrations run and configs publish
  in a real Laravel 13 application.
- Reference projects are disposable; delete after use.

## Phase 1 — Dependencies (`composer.json`)

1. Add `"php": "^8.3"` (Laravel 13 requirement; dev environment runs PHP 8.5).
2. Add `"illuminate/support": "^13.0"` to `require` (explicit framework-component
   constraint — best practice for packages; `Model`, `ServiceProvider`, `Command`,
   facades and config all come from Illuminate components).
3. `require-dev`: `orchestra/testbench: ^11.0` (maps to Laravel 13) and
   `phpunit/phpunit: ^12.0`.
4. **Remove `doctrine/dbal`** — pinned to `2.10.0`, blocks the upgrade, and a full
   source scan shows zero `Doctrine\DBAL` usage in `src/`, `database/` or `tests/`.
5. Add convenience scripts:
   ```json
   "scripts": {
       "test": "vendor/bin/phpunit",
       "test-coverage": "vendor/bin/phpunit --coverage-text"
   }
   ```
6. Add autoload mapping for the new class-based factories:
   `"aclai\\piton\\Database\\Factories\\": "database/factories/"`.
7. Add a `"version"` field (e.g. `"2.0.0"` for the Laravel-13 line) — the release
   workflow in Phase 4.2 derives tags from it.

## Phase 2 — Source code changes

### 2.1 PHP 8 fatal error (blocker)

- `src/Utils.php` uses **`create_function()`** (removed in PHP 8.0 → fatal). Replace
  with a native closure: `array_map(fn ($p) => trim($p, "/"), $paths)`.

### 2.2 Eloquent models (`ClassModel`, `ModelVersion`, `Problem`)

- Convert `protected $casts` properties to the modern `protected function casts(): array`
  method (Laravel 11+ idiom; property form is deprecated direction-wise).
- Add the `HasFactory` trait plus `protected static function newFactory()` so
  `ClassModel::factory()` resolves to the new factory classes.
- Keep `protected $connection = 'piton_connection'` and `$table` mappings unchanged
  (behavioral compatibility).

### 2.3 Factories (Laravel 8 breaking change)

- Delete legacy `database/factories/ClassModel.php` / `ModelVersion.php`
  (`$factory->define(...)` no longer exists).
- Create `Database\Factories\ClassModelFactory` and `ModelVersionFactory` extending
  `Illuminate\Database\Eloquent\Factories\Factory`.
- Fix latent factory bugs while migrating: `rand()` primary keys and
  `rand()` FK values → use real FKs (`ModelVersion::factory()`), let the DB assign
  auto-increment IDs, and replace the constant `mt_rand(1262055681, 1262055681)`
  timestamp with `$faker->dateTime`.

### 2.4 Migrations

- Convert the four migration files to anonymous-class style
  (`return new class extends Migration`) — current best practice, avoids class-name
  collisions across packages.
- Verify all schema definitions run on SQLite (JSON columns, indexes) since tests
  will use `sqlite :memory:`.

### 2.5 Service provider

- Move `$this->commands([...])` registration from `register()` into `boot()` guarded
  by `$this->app->runningInConsole()` (console commands should not be registered on
  web requests).
- Everything else (`loadMigrationsFrom`, `publishes`, singleton bindings) is already
  compatible with Laravel 13.

### 2.6 Console commands

- Ensure every `handle()` returns an integer exit code (`self::SUCCESS` /
  `self::FAILURE`) — Symfony Console (since 5.x, enforced through 7/8) expects int
  returns. Currently `void`.
- Confirm `protected $signature` usage only (all four commands already use it).

### 2.7 Dead code cleanup

- `src/DBFit/Fetcher`, `src/DBFit/Fitter`, `src/DBFit/Predicter` are extension-less
  draft files — never autoloaded, never executed. Delete them (git history preserves
  the content; they also match TODO.md item #1 as unfinished work).

### 2.8 Laravel 13 global-function conflicts (note)

- Laravel 13 pulls in `symfony/polyfill-php85`, which defines `array_first()` /
  `array_last()` globals on PHP < 8.5. Our package defines **no** global functions
  with those names (helpers like `array_list`/`array_equiv`/`array_column_assoc`/
  `array_map_kv` are `Utils` methods), so no conflict — verified during implementation.

## Phase 3 — Test suite upgrade

### 3.1 Infrastructure

- `phpunit.xml`: migrate to the PHPUnit 12 schema — replace the removed
  `<filter><whitelist>` block with `<source><include>`, keep both testsuites.
- `tests/TestCase.php`:
  - **Remove `$this->withFactories(...)`** — method no longer exists.
  - Keep MySQL as the test driver (DBFit generates MySQL-flavored SQL), but align
    the `piton_connection` coordinates with **`bin/test-db.sh`** (the throwaway
    MySQL container the suite runs against): host `127.0.0.1`, **port `3310`**,
    database `test`, user `test`, password `test`. Today the config points at port
    `3306` / database `test_database`, which matches nothing.
  - Local workflow: `bin/test-db.sh start` before running the suite,
    `bin/test-db.sh stop` afterwards (container is ephemeral on purpose).
  - While touching it, also fix the stale comment in `bin/test-db.sh` header that
    still says "127.0.0.1:3306" (actual mapped port is 3310).
- PHPUnit 12 removed doc-comment metadata: rename all `/** @test */ public function
  foo_bar()` methods to `public function test_foo_bar()` (or `#[Test]` attributes —
  `test_` prefix chosen for simplicity).

### 3.2 Existing tests migration

- Rewrite `SaveClassModelTest` / `SaveModelVersionTest` from
  `factory(ClassModel::class)->create()` to `ClassModel::factory()->create()`.
- Keep `RefreshDatabase` (works against the in-memory SQLite connection).
- Python-backed learner tests (`SklearnLearnerTest`, `WittgensteinLearnerTest`):
  guard with `$this->markTestSkipped()` when `python3` or the required Python
  packages (`sklearn`, `wittgenstein`) are unavailable — they shell out to Python,
  and neither package is installed in the dev environment.
- Feature tests that hit the database run against the MySQL container from
  `bin/test-db.sh` (locally) or the CI MySQL service (in GitHub Actions) — same
  coordinates in both. Tests that need no database must not boot one.

### 3.3 New tests (coverage expansion)

No coverage driver (Xdebug/PCOV) is installed in the current environment, so coverage
must be measured locally with `pcov` or `xdebug`; the target below refers to the
pure-logic classes, which form the bulk of the package's behavior.

Priority targets (pure PHP, no DB, no Python — highest value per test):

| Class | Size | New tests |
|-------|------|-----------|
| `Instances\Instances` | ~1017 LOC | construction from arrays, attribute access, instance push/iteration, class-attribute handling, weighted instances |
| `Attributes\*` | ~410 LOC | continuous/discrete attribute domains, metadata handling |
| `Antecedents\*` | ~820 LOC | continuous (cut points, intervals) and discrete antecedents, `covers()`/`satisfiedBy()` behavior |
| `Rules\*` | ~625 LOC | rule construction, antecedent add, coverage/length, string output |
| `RuleStats\RuleStats` | ~860 LOC | distributions, accuracy/laplace measures |
| `Utils` | ~549 LOC | array helpers (`array_list`, `array_equiv`, `array_column_assoc`, `array_map_kv`), path join, mysql quoting — **also covers the `create_function` fix** |
| `Learners\PRip` (pure parts) | ~714 LOC | rule growing/pruning helpers that don't require the full DBFit pipeline |

Feature-level (MySQL test-container): model persistence round-trips (ClassModel,
ModelVersion, Problem casts), factory integration, service-provider boot (config
publishing tags, command registration), facades.

**Coverage goal:** ≥ 80% lines on the pure-logic classes above; overall package
coverage will be dominated by `DBFit.php` (3373 LOC, DB-coupled) — its unit-testable
helpers get covered where feasible without mocking a live RDBMS.

## Phase 4 — Continuous Integration (GitHub Actions)

### 4.1 `tests.yml` — run the suite automatically

- **Trigger:** push to `main` and `dev`, plus every pull request.
- **Matrix:** PHP `8.3`, `8.4`, `8.5` × `composer update` (--prefer-lowest optional
  later).
- **Services:** MySQL 8 container published on `127.0.0.1:3310` with database `test`
  and credentials `test`/`test` — mirroring `bin/test-db.sh` exactly, so local and
  CI runs are identical. Include a health-check wait
  (`mysqladmin ping`) before tests start.
- **Steps:** `shivammathur/setup-php` (with `pcov` for coverage on one matrix leg) →
  `composer update --prefer-dist` → `vendor/bin/phpunit` → on the coverage leg,
  `vendor/bin/phpunit --coverage-text` (Codecov upload optional, only if the
  maintainers want it).
- Composer cache keyed on `composer.lock` hash.

### 4.2 `release.yml` — tag & release on version bump (main only)

- **Trigger:** push to `main` **only**.
- **Logic:** read `"version"` from `composer.json` (the field must be added during
  Phase 1 — e.g. start at `2.0.0` for the Laravel-13 line); if a git tag
  `v<version>` does not yet exist, create it and publish a GitHub Release with
  auto-generated notes (`gh release create v<version> --generate-notes`). If the
  tag already exists, the job no-ops — releases therefore happen exactly once per
  version bump, and never from branches/PRs.
- Guard with `if: github.ref == 'refs/heads/main'` for defense in depth.
- Permissions: `contents: write` on the job only.

## Phase 5 — Quality gates

1. `composer update` resolves cleanly on PHP 8.5.
2. `bin/test-db.sh start` + `vendor/bin/phpunit` green (locally and in CI).
3. `composer test-coverage` measured (requires pcov/xdebug locally; CI installs pcov).
4. Smoke-install into a fresh Laravel 13 reference app succeeds (Phase 0).
5. Final code review pass over the complete changeset.

## Out of scope (explicit)

- Behavioral refactors from `TODO.md` (DBFit split, weighted datasets, SQL escaping
  strategy, parallelization) — separate efforts, not part of a framework upgrade.
- Dropping the `piton_connection` convention or renaming config keys (BC break for
  consumers).

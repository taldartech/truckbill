# TruckBill Broker Module — Continuous Integration (CI) Guide

This guide details how to configure and maintain continuous automated testing, code quality checks, and coverage enforcement for **TruckBill Broker** in CI/CD environments such as GitHub Actions.

---

## 1. CI Pipeline Architecture

The CI pipeline guarantees that every Pull Request and branch commit is automatically validated before merging to `main` or deploying to production.

```
+-------------------------------------------------------------------------+
|                          GITHUB ACTIONS PIPELINE                        |
+-------------------------------------------------------------------------+
| 1. Environment Setup (Ubuntu 22.04 + PHP 8.4 + Extensions: pcov, sqlite)|
| 2. Dependency Caching & Installation (Composer install --prefer-dist)   |
| 3. Test Runner Execution (PHPUnit with isolated SQLite :memory:)        |
| 4. Security & Tenant-Isolation Gate (Zero failures permitted)          |
| 5. Code Coverage Verification (PCOV reporting + coverage threshold gate)|
| 6. Artifact Archiving (HTML coverage & test log retention)              |
+-------------------------------------------------------------------------+
```

---

## 2. GitHub Actions Workflow Configuration

Create or update `.github/workflows/broker-tests.yml` in your repository:

```yaml
name: TruckBill Broker Automated Tests & Coverage

on:
  push:
    branches: [ main, develop, 'feature/**' ]
  pull_request:
    branches: [ main, develop ]

jobs:
  broker-test-suite:
    name: Run Broker Test Suite (PHP 8.4)
    runs-on: ubuntu-latest

    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup PHP Runtime with PCOV
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mbstring, xml, ctype, iconv, intl, pdo, sqlite3, pdo_sqlite, pcov, zip, bcmath
          coverage: pcov
          ini-values: memory_limit=512M

      - name: Get Composer Cache Directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer Dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install Composer Dependencies
        run: composer install --prefer-dist --no-interaction --no-progress

      - name: Prepare Application Test Key
        run: php artisan key:generate --env=testing

      - name: Execute Automated Test Suite
        run: |
          php -d memory_limit=512M ./vendor/bin/phpunit --testdox

      - name: Generate Code Coverage Report
        run: |
          php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app \
            ./vendor/bin/phpunit --coverage-text --coverage-html=coverage-report

      - name: Upload Test Coverage Artifact
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: broker-coverage-report
          path: coverage-report/
          retention-days: 14
```

---

## 3. CI Quality Gates & Threshold Enforcements

1. **Zero Test Failures**:
   - The CI pipeline must immediately fail if any test produces a Failure or Error.
2. **Critical Financial Logic Threshold**:
   - `TransporterTdsCalculator` must maintain 100% executable line coverage.
   - `InvoiceNumberService` must maintain 100% executable line coverage.
   - `SubscriptionUsageService` must maintain 100% executable line coverage.
   - `FreightBookingAmountVerifier` must maintain 100% executable line coverage.
3. **Multi-Tenant Security Gate**:
   - All tests in `tests/Feature/MultiTenant/` must pass without exception to prevent IDOR / data leak vulnerabilities.

---

## 4. Pre-Push Local Git Hook Setup

To prevent pushing broken tests to GitHub, developers can install a lightweight pre-push Git hook:

Create `.git/hooks/pre-push`:
```bash
#!/bin/sh
echo "Running TruckBill Broker fast test check..."
php -d memory_limit=512M ./vendor/bin/phpunit --stop-on-failure

STATUS=$?
if [ $STATUS -ne 0 ]; then
    echo "ERROR: Automated tests failed. Push rejected."
    exit 1
fi

echo "All tests passed! Proceeding with push."
exit 0
```
Make the script executable:
```bash
chmod +x .git/hooks/pre-push
```

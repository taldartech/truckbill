# TruckBill Broker Module — Test Setup & Execution Guide

This guide is a beginner-friendly, step-by-step manual for setting up the local testing environment, executing automated tests, and generating reproducible code coverage reports for **TruckBill Broker**.

---

## 1. Prerequisites & System Requirements

Before running the test suite, ensure your development environment has:

1. **PHP**: Version 8.2 or higher (PHP 8.4 recommended).
   ```bash
   php -v
   ```
2. **Composer**: Version 2.x.
   ```bash
   composer --version
   ```
3. **PCOV Extension** (for ultra-fast code coverage):
   ```bash
   php -m | grep pcov
   ```
   *Note: If PCOV is not installed, tests still run seamlessly without coverage flags.*
4. **SQLite Extension**:
   ```bash
   php -m | grep sqlite
   ```

---

## 2. Environment Configuration

TruckBill Broker is pre-configured to execute in an isolated, in-memory SQLite database via `phpunit.xml`. No external MySQL server or database migration setup is needed for testing!

The relevant environment variables defined in `phpunit.xml` are:
```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="BCRYPT_ROUNDS" value="4"/>
<env name="CACHE_STORE" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
```

---

## 3. Step-by-Step Test Execution Commands

### A. Run the Entire Test Suite
Run all 553 automated tests across Unit, Feature, Security, and Integration suites:
```bash
php -d memory_limit=512M ./vendor/bin/phpunit
```

### B. Run Tests with Descriptive Output (`--testdox`)
Displays human-readable scenario names for each test:
```bash
php -d memory_limit=512M ./vendor/bin/phpunit --testdox
```

### C. Run an Individual Test File
To run a specific test suite file, provide the relative path to PHPUnit:

```bash
# 1. Financial calculations & TDS rules (16 tests)
php -d memory_limit=512M ./vendor/bin/phpunit tests/Unit/Services/BrokerCalculationAndPricingUnitTest.php

# 2. Accounting reports, Day Book & Tally XML (11 tests)
php -d memory_limit=512M ./vendor/bin/phpunit tests/Feature/Accounting/AccountingReportFeatureTest.php

# 3. Multi-tenant isolation & IDOR prevention (8 tests)
php -d memory_limit=512M ./vendor/bin/phpunit tests/Feature/MultiTenant/BrokerTenantIsolationSecurityTest.php

# 4. Double-entry vouchers & DB rollback (5 tests)
php -d memory_limit=512M ./vendor/bin/phpunit tests/Feature/Integration/BrokerAccountingAndTransactionIntegrationTest.php

# 5. Customer & Transporter portals (6 tests)
php -d memory_limit=512M ./vendor/bin/phpunit tests/Feature/Portal/CustomerAndTransporterPortalTest.php

# 6. Complete End-to-End lifecycle (1 test)
php -d memory_limit=512M ./vendor/bin/phpunit tests/Feature/Broker/BrokerEndToEndWorkflowTest.php
```

### D. Run a Single Test Method
Use the `--filter` flag to execute an exact scenario:
```bash
php -d memory_limit=512M ./vendor/bin/phpunit --filter=test_complete_broker_workflow_lifecycle
```

---

## 4. Code Coverage Generation

### A. Generate Text Coverage in Terminal
```bash
php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app ./vendor/bin/phpunit --coverage-text
```

> **Important**: You must include `-d pcov.directory=app` so that PCOV instruments the application code inside `app/`.

### B. Generate Browsable HTML Coverage Report
```bash
php -d memory_limit=512M -d pcov.enabled=1 -d pcov.directory=app ./vendor/bin/phpunit --coverage-html=coverage-report
```
Once complete, open `coverage-report/index.html` in your browser to inspect line-by-line coverage highlighting:
- **Green**: Executed and verified line.
- **Red**: Unexecuted line.
- **Orange / Yellow**: Partially executed branch.

---

## 5. Troubleshooting & Frequently Asked Questions

### Issue: "Allowed memory size exhausted"
- **Solution**: Always prefix the command with `-d memory_limit=512M` to provide sufficient buffer for large report queries.

### Issue: "PCOV not loaded"
- **Solution**: If PCOV is not installed in your PHP runtime, run tests normally without the `-d pcov...` flags:
  ```bash
  php -d memory_limit=512M ./vendor/bin/phpunit
  ```

### Issue: "Database locked or foreign key error"
- **Solution**: The suite uses Laravel's `RefreshDatabase` trait which automatically cleans up tables between tests. If you notice state bleed in a custom test, verify that your test class uses `use RefreshDatabase;`.

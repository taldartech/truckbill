# TruckBill Test Data Setup & Fixtures Guide

## 1. Overview
This guide documents how test data, seeders, and factories are configured to enable reliable, repeatable testing across local environments and CI pipelines without touching production data.

---

## 2. Environment Configuration

### 2.1 Testing Environment Variables (`phpunit.xml`)
The test suite operates entirely in memory using SQLite:
```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="APP_MAINTENANCE_DRIVER" value="file"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="TELESCOPE_ENABLED" value="false"/>
</php>
```

---

## 3. Core Database Factories

The codebase provides factory definitions for generating realistic test data:

| Entity | Factory Class | Key Default Attributes |
|--------|---------------|------------------------|
| **User** | `Database\Factories\UserFactory` | Name, email, bcrypt password, `user_role_id = 2` |
| **Company** | `Database\Factories\CompanyFactory` | Name, auto-generated short name, dummy PAN, GSTIN |
| **Branch** | `Database\Factories\BranchFactory` | Branch name, contact number, `head_office = true` |
| **Customer** | `Database\Factories\CustomerFactory` | Company relationship, name, contact number, PAN |
| **Consignee**| `Database\Factories\ConsigneeFactory` | Name, state, city, pincode, contact |
| **Truck** | `Database\Factories\TruckFactory` | Registration number (e.g. MH12AB1234), truck type, owner |
| **Driver** | `Database\Factories\DriverFactory` | Name, driving license number, phone, active status |
| **LorryReceipt** | `Database\Factories\LorryReceiptFactory` | GR number, freight amount, status, delivery date |
| **FreightBooking** | `Database\Factories\FreightBookingFactory` | Booking number, truck hire amount, advance, balance |

---

## 4. Setting Up Test Data for Manual / E2E Verification

### 4.1 Running Database Seeders Locally
To populate a fresh local development database with complete mock transport records:
```bash
# Run migrations and seed foundational test data
php artisan migrate:fresh --seed
```

### 4.2 Creating an E2E Browser Test User
To run Playwright tests locally with an authenticated user:
```bash
php artisan tinker
```
```php
$user = App\Models\User::firstOrCreate(
    ['email' => 'admin@example.com'],
    [
        'name' => 'Demo Admin',
        'password' => bcrypt('secret'),
        'user_role_id' => 2,
    ]
);

$company = App\Models\Company::firstOrCreate(
    ['name' => 'Apex Logistics'],
    [
        'user_id' => $user->id,
        'short_name' => 'APEX',
        'is_blocked' => false,
    ]
);
```

Configure `e2e/.env`:
```env
E2E_BASE_URL=http://127.0.0.1:8000
E2E_EMAIL=admin@example.com
E2E_PASSWORD=secret
```

---

## 5. Running Automated Test Commands

### 5.1 PHPUnit Test Suite
```bash
# Run full suite (1,390 tests)
php -d memory_limit=1G vendor/bin/phpunit

# Run specific functional domain
php vendor/bin/phpunit tests/Feature/LR/
php vendor/bin/phpunit tests/Feature/Freight/
php vendor/bin/phpunit tests/Feature/Invoice/

# Generate HTML code coverage report
php -d memory_limit=1G -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html coverage --coverage-text
```

### 5.2 Playwright Browser Tests
```bash
cd e2e
npm test
npm run test:headed
```

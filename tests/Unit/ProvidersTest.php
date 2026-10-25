<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use App\Providers\DatabaseServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProvidersTest extends TestCase
{
    public function test_app_service_provider_register_and_boot(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $this->assertTrue(true);
    }

    public function test_database_service_provider_register_executes_without_errors(): void
    {
        $provider = new DatabaseServiceProvider($this->app);
        $provider->register();

        $this->assertTrue(true);
    }

    public function test_database_service_provider_boot_executes_sql_mode_statement_when_mysql(): void
    {
        Config::set('database.default', 'mysql');

        DB::shouldReceive('statement')
            ->once()
            ->with("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

        $provider = new DatabaseServiceProvider($this->app);
        $provider->boot();
    }

    public function test_database_service_provider_boot_does_not_execute_statement_when_not_mysql(): void
    {
        Config::set('database.default', 'sqlite');

        DB::shouldReceive('statement')->never();

        $provider = new DatabaseServiceProvider($this->app);
        $provider->boot();
    }

    public function test_storage_interface_bound_to_pulse_database_storage(): void
    {
        $this->assertInstanceOf(\App\Pulse\PulseDatabaseStorage::class, app(\Laravel\Pulse\Contracts\Storage::class));
    }

    public function test_pulse_database_storage_requires_manual_key_hash(): void
    {
        $currentDriver = 'sqlite';
        $db = \Mockery::mock(\Illuminate\Database\DatabaseManager::class);
        $db->shouldReceive('connection')->with(null)->andReturnUsing(function () use (&$currentDriver) {
            $connection = \Mockery::mock(\Illuminate\Database\Connection::class);
            $connection->shouldReceive('getDriverName')->andReturn($currentDriver);
            return $connection;
        });

        $config = \Mockery::mock(\Illuminate\Contracts\Config\Repository::class);
        $config->shouldReceive('get')->with('pulse.storage.database.connection')->andReturn(null);

        $storage = new \App\Pulse\PulseDatabaseStorage($db, $config);

        $reflection = new \ReflectionMethod($storage, 'requiresManualKeyHash');
        $reflection->setAccessible(true);

        // Drivers that require manual key hash: sqlite, mysql, mariadb
        foreach (['sqlite', 'mysql', 'mariadb'] as $driver) {
            $currentDriver = $driver;
            $this->assertTrue($reflection->invoke($storage), "Driver {$driver} should require manual key hash.");
        }

        // Drivers that do not require manual key hash: pgsql, sqlsrv, etc.
        foreach (['pgsql', 'sqlsrv', 'unknown'] as $driver) {
            $currentDriver = $driver;
            $this->assertFalse($reflection->invoke($storage), "Driver {$driver} should not require manual key hash.");
        }
    }
}


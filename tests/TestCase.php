<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Keep track of the original PHP error handler so PHPUnit
     * does not mark tests as risky for handler changes.
     */
    protected $originalErrorHandler;

    protected function setUp(): void
    {
        // Capture the current handler (typically PHPUnit's) and restore immediately.
        $this->originalErrorHandler = set_error_handler(static function (): bool {
            return false;
        });
        restore_error_handler();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->originalErrorHandler !== null) {
            set_error_handler($this->originalErrorHandler);
        } else {
            restore_error_handler();
        }
    }
}


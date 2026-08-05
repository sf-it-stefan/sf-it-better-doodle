<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards against the test suite pointing at the live development database.
 * The container exports DB_* as real environment variables, which override
 * phpunit.xml's <env> entries unless they are marked force="true" — and
 * RefreshDatabase then wipes the developer's actual data.
 */
class TestDatabaseIsolationTest extends TestCase
{
    public function test_tests_never_run_against_postgres(): void
    {
        $this->assertSame(
            'sqlite',
            config('database.default'),
            'Test suite is not isolated — it would destroy the development database.'
        );

        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database'),
            'Test suite must use an in-memory database.'
        );
    }
}

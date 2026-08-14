<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Every Pest test under tests/Feature gets the application TestCase and a
| freshly migrated in-memory SQLite database. The pre-existing PHPUnit test
| classes in the same directory are untouched: Pest only applies these
| bindings to files that declare their tests with the Pest functions.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

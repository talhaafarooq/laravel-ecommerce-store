<?php

declare(strict_types = 1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against the dedicated MySQL testing database and are
| wrapped in a transaction by RefreshDatabase. Unit tests extend the
| application test case so they can use factories and the container.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

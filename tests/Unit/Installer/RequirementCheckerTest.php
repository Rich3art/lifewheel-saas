<?php

namespace Tests\Unit\Installer;

use App\Services\Installer\RequirementChecker;
use Tests\TestCase;

final class RequirementCheckerTest extends TestCase
{
    public function test_requirement_checker_returns_required_checks(): void
    {
        $checks = app(RequirementChecker::class)->check();

        $this->assertNotEmpty($checks);
        $this->assertContains('PHP 8.2.0+', array_column($checks, 'name'));
        $this->assertContains('Extension: pdo_mysql', array_column($checks, 'name'));
        $this->assertTrue(collect($checks)->every(fn (array $check): bool => array_key_exists('required', $check)));
    }
}

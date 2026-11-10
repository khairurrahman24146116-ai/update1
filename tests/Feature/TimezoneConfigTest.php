<?php

namespace Tests\Feature;

use Tests\TestCase;

class TimezoneConfigTest extends TestCase
{
    public function test_application_uses_jakarta_timezone_by_default(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }
}

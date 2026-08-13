<?php

namespace Scry\Tests\Unit;

use Illuminate\Http\Request;
use Scry\Facades\Scry as ScryFacade;
use Scry\Scry;
use Scry\Tests\TestCase;

class ScryFacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        Scry::$authUsing = null;
        parent::tearDown();
    }

    public function test_scry_check_allows_testing_environment_by_default(): void
    {
        $request = Request::create('/scry/api/tables', 'GET');
        $this->assertTrue(Scry::check($request));
    }

    public function test_custom_auth_callback_can_deny_access(): void
    {
        Scry::auth(function ($request) {
            return false;
        });

        $request = Request::create('/scry/api/tables', 'GET');
        $this->assertFalse(Scry::check($request));
    }

    public function test_custom_auth_callback_can_allow_access(): void
    {
        Scry::auth(function ($request) {
            return true;
        });

        $request = Request::create('/scry/api/tables', 'GET');
        $this->assertTrue(Scry::check($request));
    }

    public function test_facade_alias_resolves(): void
    {
        ScryFacade::auth(function ($request) {
            return true;
        });

        $request = Request::create('/scry/api/tables', 'GET');
        $this->assertTrue(ScryFacade::check($request));
    }
}

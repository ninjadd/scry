<?php

namespace Scry\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Scry\Facades\Scry as ScryFacade;
use Scry\Scry;
use Scry\Tests\TestCase;

class ScryFacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        Scry::resetForTesting();
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

    public function test_no_warning_is_logged_for_the_default_local_or_testing_environment(): void
    {
        Log::spy();

        $request = Request::create('/scry/api/tables', 'GET');
        Scry::check($request);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_a_warning_is_logged_when_env_gate_alone_allows_access_outside_local_or_testing(): void
    {
        Log::spy();
        $this->app->instance('env', 'staging');
        config(['scry.allowed_environments' => ['local', 'testing', 'staging']]);

        $request = Request::create('/scry/api/tables', 'GET');
        $this->assertTrue(Scry::check($request));

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_the_env_gate_warning_is_only_logged_once_per_process(): void
    {
        Log::spy();
        $this->app->instance('env', 'staging');
        config(['scry.allowed_environments' => ['local', 'testing', 'staging']]);

        $request = Request::create('/scry/api/tables', 'GET');
        Scry::check($request);
        Scry::check($request);
        Scry::check($request);

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_no_warning_is_logged_when_a_custom_auth_closure_grants_access_outside_local_or_testing(): void
    {
        Log::spy();
        $this->app->instance('env', 'staging');
        Scry::auth(fn () => true);

        $request = Request::create('/scry/api/tables', 'GET');
        $this->assertTrue(Scry::check($request));

        Log::shouldNotHaveReceived('warning');
    }
}

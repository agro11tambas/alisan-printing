<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExpiredSessionRedirectTest extends TestCase
{
    public function test_expired_web_session_redirects_to_login(): void
    {
        Route::get('/test/expired-session', function () {
            throw new TokenMismatchException;
        });

        $this->get('/test/expired-session')
            ->assertRedirect('/login')
            ->assertSessionHas('logout_notice');
    }

    public function test_expired_ajax_session_returns_419_with_login_target(): void
    {
        Route::get('/test/expired-session-ajax', function () {
            throw new TokenMismatchException;
        });

        $this->getJson('/test/expired-session-ajax')
            ->assertStatus(419)
            ->assertJson([
                'message' => 'Session expired, silakan login kembali.',
                'redirect' => '/login',
            ]);
    }
}
<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('login', function () {
    it('logs the user in and returns their profile', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertExactJson([
            'data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
        $this->assertAuthenticatedAs($user, 'web');
    });

    it('returns 422 when the password is wrong', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'These credentials do not match our records.']);
        $this->assertGuest('web');
    });

    it('returns 422 when credentials are missing', function () {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ]);
    });

    it('returns 403 when the request does not come from the frontend', function () {
        $user = User::factory()->create();

        $response = $this->withoutHeader('Origin')->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertForbidden()
            ->assertExactJson(['message' => 'This endpoint only accepts requests from the frontend application.']);
        $this->assertGuest('web');
    });

    it('returns 429 after too many failed attempts', function () {
        $user = User::factory()->create();
        $credentials = ['email' => $user->email, 'password' => 'wrong-password'];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', $credentials)->assertUnprocessable();
        }

        $this->postJson('/api/login', $credentials)->assertTooManyRequests();
    });
});

describe('current user', function () {
    it('returns the authenticated user', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertOk()->assertExactJson([
            'data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    });

    it('returns 401 when not authenticated', function () {
        $this->getJson('/api/user')->assertUnauthorized();
    });
});

describe('logout', function () {
    it('logs the user out', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/logout');

        $response->assertNoContent();
        $this->assertGuest('web');
    });

    it('returns 401 when not authenticated', function () {
        $this->postJson('/api/logout')->assertUnauthorized();
    });

    it('returns 403 when the request does not come from the frontend', function () {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->withoutHeader('Origin')->postJson('/api/logout');

        $response->assertForbidden()
            ->assertExactJson(['message' => 'This endpoint only accepts requests from the frontend application.']);
    });
});

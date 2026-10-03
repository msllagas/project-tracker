<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a new user and logs them in', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $user = User::firstWhere('email', 'jane@example.com');
    $response->assertCreated()->assertExactJson([
        'data' => ['id' => $user->id, 'name' => 'Jane Doe', 'email' => 'jane@example.com'],
    ]);
    expect(Hash::check('secret-password', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user, 'web');
});

it('returns 422 when required fields are missing', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertUnprocessable()->assertJsonValidationErrors([
        'name' => 'The name field is required.',
        'email' => 'The email field is required.',
        'password' => 'The password field is required.',
    ]);
    $this->assertDatabaseCount('users', 0);
});

it('returns 422 when the email is already taken', function () {
    $existing = User::factory()->create();

    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => $existing->email,
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'The email has already been taken.']);
    $this->assertDatabaseCount('users', 1);
});

it('returns 422 when the password confirmation does not match', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'The password field confirmation does not match.']);
    $this->assertGuest('web');
});

it('returns 403 when the request does not come from the frontend', function () {
    $response = $this->withoutHeader('Origin')->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertForbidden()
        ->assertExactJson(['message' => 'This endpoint only accepts requests from the frontend application.']);
    $this->assertDatabaseCount('users', 0);
});

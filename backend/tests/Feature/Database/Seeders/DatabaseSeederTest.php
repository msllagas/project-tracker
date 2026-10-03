<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('seeds the demo account used by the login page', function () {
    $this->seed();

    $user = User::firstWhere('email', 'demo@example.com');
    expect($user)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('seeds the provided test data projects', function () {
    $this->seed();

    $this->assertDatabaseCount('projects', 12);
});

<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a user and returns a Sanctum token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.name', 'Test User')
        ->assertJsonPath('data.user.email', 'test@example.com')
        ->assertJsonMissingPath('data.user.password');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'balance' => 0,
    ]);

    expect(Hash::check('password123', User::query()->where('email', 'test@example.com')->firstOrFail()->password))
        ->toBeTrue();
});

it('rejects invalid login credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'Invalid credentials.');
});

it('returns a token when a user logs in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonMissingPath('data.user.password');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

    $this->withToken($response->json('data.token'))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});

it('validates required registration fields', function () {
    $this->postJson('/api/register', [])
        ->assertUnprocessable()
        ->assertInvalid(['name', 'email', 'password']);
});

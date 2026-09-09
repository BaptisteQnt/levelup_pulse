<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'over_18_declared' => true,
        'rgpd_consent' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

    expect($user->over_18_declared)->toBeTrue()
        ->and($user->age)->toBe(18)
        ->and($user->over_18_declaration_label)->toBe('Je déclare avoir 18 ans ou plus.')
        ->and($user->over_18_declared_at)->not->toBeNull()
        ->and($user->rgpd_consent)->toBeTrue()
        ->and($user->rgpd_consent_label)->toBe('J’accepte que mes données personnelles soient traitées conformément à la politique de confidentialité.')
        ->and($user->rgpd_consented_at)->not->toBeNull();
});

test('registration requires both consents', function (array $payload, string $field) {
    $response = $this->post('/register', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'over_18_declared' => true,
        'rgpd_consent' => true,
        ...$payload,
    ]);

    $response->assertSessionHasErrors($field);
    $this->assertGuest();
})->with([
    'majority declaration is missing' => [['over_18_declared' => false], 'over_18_declared'],
    'RGPD consent is missing' => [['rgpd_consent' => false], 'rgpd_consent'],
]);

test('a user under 18 cannot register', function () {
    $response = $this->post('/register', [
        'email' => 'minor@example.com',
        'age' => 17,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'over_18_declared' => true,
        'rgpd_consent' => true,
    ]);

    $response->assertSessionHasErrors([
        'age' => 'Vous devez avoir au moins 18 ans pour créer un compte.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'minor@example.com']);
});

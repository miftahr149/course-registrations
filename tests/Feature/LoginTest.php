<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('the home page is the login form', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Log in')
        ->assertSee(route('login.store'));
});

test('a user can log in with valid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('a wrong password is rejected', function () {
    $user = User::factory()->create();

    $this->from('/')
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertRedirect('/')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a guest is sent to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a logged-in user visiting the login page goes to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

test('the dashboard shows the user and their role', function () {
    $lecturer = User::factory()->lecturer()->create(['name' => 'Dr. Budi']);

    $this->actingAs($lecturer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dr. Budi')
        ->assertSee('Lecturer');
});

test('a user can log out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('every demo account shown on the login page can log in after seeding', function () {
    $this->seed(DatabaseSeeder::class);

    $demoAccounts = $this->get('/')->viewData('demoAccounts');

    expect(collect($demoAccounts)->pluck('role')->unique()->sort()->values()->all())
        ->toBe(['Admin', 'Lecturer', 'Student']);

    foreach ($demoAccounts as $account) {
        $this->post(route('login.store'), ['email' => $account['email'], 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        expect(auth()->user()->role->name)->toBe($account['role']);

        $this->post(route('logout'));
    }
});

test('demo accounts are hidden in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('/')
        ->assertOk()
        ->assertViewHas('demoAccounts', [])
        ->assertDontSee('Demo accounts');
});

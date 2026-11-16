<?php

use App\Models\Admin;
use App\Models\Customer;
use Database\Seeders\DatabaseSeeder;

it('seeds a public 顧客 account that can sign in on the 前台 but not the 後台', function () {
    $this->seed(DatabaseSeeder::class);

    $this->post('/admin/login', ['email' => 'customer@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->post('/login', ['email' => 'customer@example.com', 'password' => 'password'])
        ->assertSessionHasNoErrors();
    $this->assertAuthenticated('customer');
});

it('seeds a public 店家 account that can sign in on the 後台 but not the 前台', function () {
    $this->seed(DatabaseSeeder::class);

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
        ->assertRedirect('/admin/products');
    $this->assertAuthenticated('admin');
});

it('keeps one of each demo account, with the public password, when seeded again on every start', function () {
    $this->seed(DatabaseSeeder::class);
    Admin::where('email', 'admin@example.com')->update(['password' => bcrypt('changed')]);

    $this->seed(DatabaseSeeder::class);

    expect(Customer::where('email', 'customer@example.com')->count())->toBe(1)
        ->and(Admin::where('email', 'admin@example.com')->count())->toBe(1);
    $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
        ->assertRedirect('/admin/products');
});

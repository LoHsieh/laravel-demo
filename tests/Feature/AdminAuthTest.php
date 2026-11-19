<?php

use App\Models\Admin;
use App\Models\Customer;

it('lets a 店家 sign in on the 後台 login page and reach the 後台', function () {
    Admin::factory()->create(['email' => 'admin@bairi.test']);

    $this->post('/admin/login', ['email' => 'admin@bairi.test', 'password' => 'password'])
        ->assertRedirect('/admin');

    $this->get('/admin')->assertOk()->assertSeeText('銷售概況');
});

it('rejects a wrong password on the 後台 login page', function () {
    Admin::factory()->create(['email' => 'admin@bairi.test']);

    $this->from('/admin/login')
        ->post('/admin/login', ['email' => 'admin@bairi.test', 'password' => 'wrong'])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors('email');

    $this->get('/admin/products')->assertRedirect('/admin/login');
});

it('does not accept a 顧客 account on the 後台 login page', function () {
    Customer::factory()->create(['email' => 'customer@bairi.test']);

    $this->post('/admin/login', ['email' => 'customer@bairi.test', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->get('/admin/products')->assertRedirect('/admin/login');
});

it('sends a 訪客 to the 後台 login page, not the 顧客 login page', function () {
    $this->get('/admin/products')->assertRedirect('/admin/login');
});

it('keeps a signed-in 顧客 out of the 後台', function () {
    $this->actingAs(Customer::factory()->create(), 'customer');

    $this->get('/admin/products')->assertRedirect('/admin/login');
    $this->post('/admin/products', ['name' => '偷偷新增'])->assertRedirect('/admin/login');
});

it('does not sign a 店家 into the 前台 as a 顧客', function () {
    $this->actingAs(Admin::factory()->create(), 'admin');

    $this->get('/account')->assertRedirect('/login');
});

it('signs a 店家 out of the 後台', function () {
    $this->actingAs(Admin::factory()->create(), 'admin');

    $this->post('/admin/logout')->assertRedirect('/admin/login');

    $this->assertGuest('admin');
});

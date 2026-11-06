<?php

use App\Mail\ResetCustomerPassword;
use App\Models\Customer;
use Illuminate\Support\Facades\Mail;

it('lets a 訪客 register with an Email and password and become a logged-in 顧客', function () {
    $this->post('/register', [
        'email' => 'mei@example.com',
        'password' => 'roast-daily-2026',
        'password_confirmation' => 'roast-daily-2026',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs(Customer::where('email', 'mei@example.com')->sole(), 'customer');
});

it('refuses to register an Email that already belongs to a 顧客', function () {
    Customer::factory()->create(['email' => 'mei@example.com']);

    $this->from('/register')->post('/register', [
        'email' => 'mei@example.com',
        'password' => 'roast-daily-2026',
        'password_confirmation' => 'roast-daily-2026',
    ])->assertRedirect('/register')
        ->assertSessionHasErrors(['email' => '這個Email已經註冊過了。']);

    $this->assertGuest('customer');
});

it('shows the registration form in Chinese', function () {
    $this->get('/register')->assertOk()->assertSee('註冊會員');
});

it('lets a 顧客 log in with their Email and password', function () {
    $customer = Customer::factory()->create(['email' => 'mei@example.com', 'password' => 'roast-daily-2026']);

    $this->get('/login')->assertOk()->assertSee('登入');

    $this->post('/login', ['email' => 'mei@example.com', 'password' => 'roast-daily-2026'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('refuses to log in with a wrong password', function () {
    Customer::factory()->create(['email' => 'mei@example.com', 'password' => 'roast-daily-2026']);

    $this->from('/login')->post('/login', ['email' => 'mei@example.com', 'password' => 'wrong-password'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'Email 或密碼不正確。']);

    $this->assertGuest('customer');
});

it('lets a 顧客 log out', function () {
    $this->actingAs(Customer::factory()->create(), 'customer')
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest('customer');
});

it('emails a password reset link that lets the 顧客 set a new password', function () {
    Mail::fake();
    $customer = Customer::factory()->create(['email' => 'mei@example.com']);

    $this->get('/forgot-password')->assertOk()->assertSee('忘記密碼');

    $this->post('/forgot-password', ['email' => 'mei@example.com'])
        ->assertSessionHas('status', '如果這個 Email 已註冊，我們已寄出重設密碼的連結。');

    $resetUrl = null;
    Mail::assertSent(ResetCustomerPassword::class, function (ResetCustomerPassword $mail) use ($customer, &$resetUrl) {
        $resetUrl = $mail->url;

        return $mail->hasTo('mei@example.com') && $mail->customer->is($customer);
    });

    $token = basename(parse_url($resetUrl, PHP_URL_PATH));
    $this->get($resetUrl)->assertOk()->assertSee('重設密碼');

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'mei@example.com',
        'password' => 'fresh-beans-2026',
        'password_confirmation' => 'fresh-beans-2026',
    ])->assertRedirect('/login')
        ->assertSessionHas('status', '密碼已重設，請用新密碼登入。');

    $this->post('/login', ['email' => 'mei@example.com', 'password' => 'fresh-beans-2026']);
    $this->assertAuthenticatedAs($customer, 'customer');
});

it('does not reveal whether an Email is registered when asking for a reset link', function () {
    Mail::fake();

    $this->post('/forgot-password', ['email' => 'nobody@example.com'])
        ->assertSessionHas('status', '如果這個 Email 已註冊，我們已寄出重設密碼的連結。')
        ->assertSessionHasNoErrors();

    Mail::assertNothingSent();
});

it('refuses to reset the password with an invalid token', function () {
    Customer::factory()->create(['email' => 'mei@example.com', 'password' => 'roast-daily-2026']);

    $this->from('/reset-password/bogus')->post('/reset-password', [
        'token' => 'bogus',
        'email' => 'mei@example.com',
        'password' => 'fresh-beans-2026',
        'password_confirmation' => 'fresh-beans-2026',
    ])->assertSessionHasErrors(['email' => '重設密碼的連結無效或已過期，請重新申請。']);

    $this->post('/login', ['email' => 'mei@example.com', 'password' => 'roast-daily-2026']);
    $this->assertAuthenticated('customer');
});

it('lets a 顧客 save a default recipient name, phone and address', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->put('/account/recipient', [
            'recipient_name' => '林小梅',
            'recipient_phone' => '0912-345-678',
            'recipient_address' => '台北市大安區復興南路一段 100 號 3 樓',
        ])
        ->assertRedirect('/account')
        ->assertSessionHas('status', '已儲存預設收件資訊。');

    $this->get('/account')
        ->assertOk()
        ->assertSee('value="林小梅"', false)
        ->assertSee('value="0912-345-678"', false)
        ->assertSee('value="台北市大安區復興南路一段 100 號 3 樓"', false);
});

it('requires a recipient name, a valid phone and an address', function () {
    $this->actingAs(Customer::factory()->create(), 'customer')
        ->from('/account')
        ->put('/account/recipient', [
            'recipient_name' => '',
            'recipient_phone' => 'call me',
            'recipient_address' => '',
        ])
        ->assertRedirect('/account')
        ->assertSessionHasErrors([
            'recipient_name' => '請填寫收件人姓名。',
            'recipient_phone' => '收件人電話格式不正確。',
            'recipient_address' => '請填寫收件地址。',
        ]);
});

it('sends a 訪客 to the login page before showing the account page', function () {
    $this->get('/account')->assertRedirect('/login');
    $this->put('/account/recipient', ['recipient_name' => '林小梅'])->assertRedirect('/login');
});

it('treats Email addresses case-insensitively when registering and logging in', function () {
    $this->post('/register', [
        'email' => 'Mei@Example.com',
        'password' => 'roast-daily-2026',
        'password_confirmation' => 'roast-daily-2026',
    ])->assertSessionHasNoErrors();

    $customer = Customer::sole();
    expect($customer->email)->toBe('mei@example.com');

    $this->post('/logout');
    $this->post('/login', ['email' => 'MEI@example.COM', 'password' => 'roast-daily-2026']);

    $this->assertAuthenticatedAs($customer, 'customer');
});

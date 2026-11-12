<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetCustomerPassword extends Mailable
{
    public readonly string $url;

    public function __construct(public readonly Customer $customer, string $token)
    {
        $this->url = route('password.reset', ['token' => $token, 'email' => $customer->email]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '重設你的焙日密碼');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.reset-customer-password');
    }
}

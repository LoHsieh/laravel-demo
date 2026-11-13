<?php

namespace App\Models;

use App\Mail\ResetCustomerPassword;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

/**
 * 在前台註冊並登入的會員，使用 customer guard；店家另有自己的資料表與 guard。
 */
#[Fillable(['email', 'password', 'recipient_name', 'recipient_phone', 'recipient_address'])]
#[Hidden(['password', 'remember_token'])]
class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 以中文信件取代 Laravel 預設的英文重設密碼通知。
     */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this)->send(new ResetCustomerPassword($this, $token));
    }
}

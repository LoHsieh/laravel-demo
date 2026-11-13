<?php

namespace App\Cart;

use App\Models\Customer;
use Illuminate\Auth\Events\Login;

/**
 * 顧客登入（含註冊後自動登入、記住我）時，把訪客購物車併入顧客購物車，再清空訪客購物車。
 * 因庫存而調整的項目在下一頁的頁首下方提示顧客。
 */
final class MergeGuestCart
{
    public function __construct(private readonly SessionCartStore $guestCart) {}

    public function handle(Login $event): void
    {
        if ($event->guard !== 'customer' || ! $event->user instanceof Customer) {
            return;
        }

        $guestEntries = $this->guestCart->entries();

        if ($guestEntries === []) {
            return;
        }

        $adjustments = (new Cart(new CustomerCartStore($event->user)))->merge($guestEntries);

        $this->guestCart->clear();

        if ($adjustments !== []) {
            session()->flash('cart_adjustments', $adjustments);
        }
    }
}

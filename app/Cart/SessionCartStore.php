<?php

namespace App\Cart;

use Illuminate\Contracts\Session\Session;

/**
 * 訪客的購物車，存在 session 中，登出（session 失效）時一併消失。
 */
final class SessionCartStore implements CartStore
{
    private const SESSION_KEY = 'cart.items';

    public function __construct(private readonly Session $session) {}

    public function entries(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    public function save(array $entries): void
    {
        $this->session->put(self::SESSION_KEY, $entries);
    }

    /**
     * 清空訪客購物車，用在併入顧客購物車之後。
     */
    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}

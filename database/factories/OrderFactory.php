<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Ordering\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'number' => Order::newNumber(),
            'status' => OrderStatus::PendingPayment,
            'recipient_name' => '林小美',
            'recipient_phone' => '0912-345-678',
            'recipient_address' => '台北市大安區復興南路一段 100 號',
            'subtotal' => 840,
            'shipping_fee' => 100,
            'total' => 940,
            'payment_due_at' => now()->addMinutes(30),
        ];
    }
}

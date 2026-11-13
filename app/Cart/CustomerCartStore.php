<?php

namespace App\Cart;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * 顧客的購物車，存在 cart_items 資料表，登出再登入、換裝置都還在。
 */
final class CustomerCartStore implements CartStore
{
    public function __construct(private readonly Customer $customer) {}

    public function entries(): array
    {
        return DB::table('cart_items')
            ->where('customer_id', $this->customer->id)
            ->orderBy('id')
            ->get(['variant_id', 'grind', 'quantity', 'seen_price'])
            ->mapWithKeys(fn (object $row) => [
                Cart::keyOf((int) $row->variant_id, $row->grind) => [
                    'variant_id' => (int) $row->variant_id,
                    'grind' => $row->grind,
                    'quantity' => (int) $row->quantity,
                    'seen_price' => $row->seen_price === null ? null : (int) $row->seen_price,
                ],
            ])
            ->all();
    }

    /**
     * 以 upsert 寫入，既有項目保留原本的 id，購物車維持加入順序。
     */
    public function save(array $entries): void
    {
        DB::transaction(function () use ($entries) {
            $rows = DB::table('cart_items')->where('customer_id', $this->customer->id);

            if ($entries === []) {
                $rows->delete();

                return;
            }

            $rows->whereNot(function ($query) use ($entries) {
                foreach ($entries as $entry) {
                    $query->orWhere(fn ($match) => $match
                        ->where('variant_id', $entry['variant_id'])
                        ->where('grind', $entry['grind']));
                }
            })->delete();

            $now = now();

            DB::table('cart_items')->upsert(
                array_map(fn (array $entry) => [
                    'customer_id' => $this->customer->id,
                    'variant_id' => $entry['variant_id'],
                    'grind' => $entry['grind'],
                    'quantity' => $entry['quantity'],
                    'seen_price' => $entry['seen_price'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], array_values($entries)),
                ['customer_id', 'variant_id', 'grind'],
                ['quantity', 'seen_price', 'updated_at'],
            );
        });
    }
}

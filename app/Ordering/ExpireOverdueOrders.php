<?php

namespace App\Ordering;

use App\Models\Customer;
use App\Models\Order;

/**
 * 處理所有（或某位顧客）已超過付款期限的待付款訂單。排程每分鐘執行一次；網站休眠時排程不會準時，
 * 所以顧客查看訂單與購物車時也會先為該顧客執行一次（ADR 0002）。
 */
final class ExpireOverdueOrders
{
    public function __construct(private readonly ExpireOrder $expireOrder) {}

    public function __invoke(?Customer $customer = null): void
    {
        Order::overdue()
            ->when($customer, fn ($query) => $query->whereBelongsTo($customer))
            // 依 id 分批：處理過的訂單會離開查詢條件，以 offset 分批會跳過其中一批
            ->lazyById()
            ->each(function (Order $order) {
                try {
                    ($this->expireOrder)($order);
                } catch (InvalidOrderTransition) {
                    // 已被別的請求處理（顧客取消、付款通知或另一次排程），不需再處理
                }
            });
    }
}

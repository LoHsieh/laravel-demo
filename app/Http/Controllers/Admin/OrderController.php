<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Ordering\InvalidOrderTransition;
use App\Ordering\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 後台的訂單管理：依狀態篩選訂單、查看要出的豆子與收件資訊，並把訂單推進到下一個狀態。
 */
class OrderController extends Controller
{
    /**
     * 店家可以手動改成的狀態。付款成功、取消與付款異常只由付款通知、顧客與排程觸發。
     */
    private const STATUSES_SET_BY_ADMIN = [OrderStatus::Shipped, OrderStatus::Completed];

    public function index(Request $request): View
    {
        // 不認得的狀態視為未篩選
        $status = OrderStatus::tryFrom((string) $request->query('status'));

        return view('admin.orders.index', [
            'status' => $status,
            'orders' => Order::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'paymentExceptionCount' => Order::where('status', OrderStatus::PaymentException)->count(),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order->load(['items', 'customer']),
            'nextStatuses' => array_values(array_filter(
                self::STATUSES_SET_BY_ADMIN,
                fn (OrderStatus $to) => $order->status->canTransitionTo($to),
            )),
        ]);
    }

    /**
     * 合法與否由 Order::transitionTo() 判斷，與前台、付款通知同一套規則。
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_map(fn (OrderStatus $status) => $status->value, self::STATUSES_SET_BY_ADMIN))],
        ]);
        $to = OrderStatus::from($validated['status']);

        try {
            $order->transitionTo($to);
        } catch (InvalidOrderTransition $e) {
            return redirect()->route('admin.orders.show', $order)->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('admin.orders.show', $order)->with('status', "訂單狀態已更新為{$to->label()}。");
    }
}

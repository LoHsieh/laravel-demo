@props(['order'])

{{--
    付款異常的訂單：顧客已付款，但訂單已取消（付款逾期或顧客自行取消後才完成付款），不會出貨。
    退款由店家在綠界後台人工處理（規格「不做的事」：退款），這裡只告訴顧客會發生什麼。
    付款逾期時訂單項目已放回購物車，顧客可能重新下單再付一次，所以提醒那是另一筆付款。
--}}
@if ($order->status === \App\Ordering\OrderStatus::PaymentException)
    <div role="status" {{ $attributes->class('bg-sand px-5 py-4 text-sm') }}>
        <p>我們已收到你的付款，但這筆訂單已取消，不會出貨。店家會與你聯絡，將款項退回。</p>
        @if ($order->cancelledForPaymentExpiry())
            <p class="mt-1 text-subtle">訂單項目已在付款逾期時放回<a href="{{ route('cart.show') }}" class="underline underline-offset-4">購物車</a>。重新下單會是一筆新的訂單，需要另外付款；這筆付款仍會退回給你。</p>
        @endif
    </div>
@endif

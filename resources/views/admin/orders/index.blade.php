@extends('layouts.admin')

@section('title', '訂單')

@section('content')
    <h1 class="mb-8 font-serif text-[30px] font-medium tracking-[0.1em]">訂單</h1>

    {{-- 付款異常需要店家人工聯絡顧客退款，不論目前篩選哪個狀態都提示 --}}
    @if ($paymentExceptionCount > 0)
        <p role="alert" class="mb-8 border-l-4 border-accent bg-sand px-5 py-4 text-sm">
            有 {{ $paymentExceptionCount }} 筆付款異常的訂單需要聯絡顧客處理。
            @if ($status !== \App\Ordering\OrderStatus::PaymentException)
                <a href="{{ route('admin.orders.index', ['status' => 'payment_exception']) }}" class="ml-2 text-ink underline underline-offset-4">查看付款異常訂單</a>
            @endif
        </p>
    @endif

    <nav aria-label="依狀態篩選" class="mb-6 flex flex-wrap gap-x-6 text-sm tracking-[0.08em]">
        <a href="{{ route('admin.orders.index') }}"
           @if ($status === null) aria-current="page" @endif
           class="inline-flex min-h-11 items-center text-subtle no-underline aria-[current=page]:text-ink aria-[current=page]:underline aria-[current=page]:underline-offset-8">全部</a>
        @foreach (\App\Ordering\OrderStatus::cases() as $case)
            <a href="{{ route('admin.orders.index', ['status' => $case->value]) }}"
               @if ($status === $case) aria-current="page" @endif
               class="inline-flex min-h-11 items-center text-subtle no-underline aria-[current=page]:text-ink aria-[current=page]:underline aria-[current=page]:underline-offset-8">{{ $case->label() }}</a>
        @endforeach
    </nav>

    @if ($orders->isEmpty())
        <p class="text-subtle">沒有{{ $status?->label() }}的訂單。</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                <thead class="border-b border-ink text-[13px] tracking-[0.08em] text-subtle">
                    <tr>
                        <th scope="col" class="py-3 pr-4 font-normal">訂單編號</th>
                        <th scope="col" class="py-3 pr-4 font-normal">下單時間</th>
                        <th scope="col" class="py-3 pr-4 font-normal">收件人</th>
                        <th scope="col" class="py-3 pr-4 font-normal">總計</th>
                        <th scope="col" class="py-3 pr-4 font-normal">狀態</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr class="border-b border-line">
                            <td class="py-3 pr-4">
                                <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex min-h-11 items-center font-latin tracking-[0.06em] text-ink underline-offset-4 hover:underline">{{ $order->number }}</a>
                            </td>
                            <td class="py-3 pr-4"><x-shop-time :at="$order->created_at" /></td>
                            <td class="py-3 pr-4">{{ $order->recipient_name }}</td>
                            <td class="py-3 pr-4 font-latin">NT${{ number_format($order->total) }}</td>
                            <td class="py-3 pr-4">
                                @if ($order->status === \App\Ordering\OrderStatus::PaymentException)
                                    <span class="font-medium text-accent">{{ $order->status->label() }}</span>
                                @else
                                    {{ $order->status->label() }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
@endsection

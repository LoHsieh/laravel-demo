@extends('layouts.admin')

@section('title', '銷售概況')

@section('content')
    <h1 class="mb-8 font-serif text-[30px] font-medium tracking-[0.1em]">銷售概況</h1>

    {{-- 只計入已付款、已出貨、已完成的訂單；待付款、已取消、付款異常不算營收 --}}
    <div class="grid gap-6 sm:grid-cols-2">
        @foreach ($periods as $label => $sales)
            <section aria-labelledby="period-{{ $loop->index }}" class="border border-line px-6 py-5">
                <h2 id="period-{{ $loop->index }}" class="mb-4 font-serif text-xl tracking-[0.1em]">{{ $label }}</h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-[13px] tracking-[0.08em] text-subtle">營收</dt>
                        <dd class="font-latin text-[26px]">NT${{ number_format($sales['revenue']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[13px] tracking-[0.08em] text-subtle">訂單數</dt>
                        <dd class="text-[26px]"><span class="font-latin">{{ $sales['orders'] }}</span> 筆</dd>
                    </div>
                </dl>
            </section>
        @endforeach
    </div>

    <section aria-labelledby="top-products-heading" class="mt-12">
        <h2 id="top-products-heading" class="mb-4 font-serif text-xl tracking-[0.1em]">熱銷咖啡豆</h2>
        <p class="mb-4 text-sm text-subtle">本月售出的包數，各規格合併計算。</p>
        @if ($topProducts->isEmpty())
            <p class="text-subtle">本月還沒有售出的咖啡豆。</p>
        @else
            <ol class="divide-y divide-line border-y border-line">
                @foreach ($topProducts as $product)
                    <li class="flex items-baseline justify-between gap-4 py-3">
                        <span><span class="mr-3 font-latin text-subtle">{{ $loop->iteration }}</span>{{ $product->name }}</span>
                        <span class="shrink-0"><span class="font-latin">{{ $product->bags }}</span> 包</span>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
@endsection

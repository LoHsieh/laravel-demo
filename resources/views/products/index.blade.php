@extends('layouts.app')

@section('title', '咖啡豆｜焙日 Bairi')

@section('content')
    <h1 class="mb-12 font-serif text-[34px] font-medium tracking-[0.12em]">咖啡豆</h1>

    <ul class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-x-8 gap-y-12">
        @foreach ($products as $product)
            <li class="flex flex-col gap-2 border-t border-line pt-5">
                <h2 class="font-serif text-lg leading-normal tracking-[0.06em]">{{ $product->name }}</h2>
                <p class="text-[13px] text-muted">{{ $product->origin }}・{{ $product->process }}・{{ $product->roast }}</p>
                <p class="text-sm text-subtle">{{ $product->flavor_description }}</p>

                <div
                    data-vue-component="VariantPrices"
                    data-props="{{ json_encode(['variants' => $product->variants->map->only(['id', 'weight_grams', 'price'])]) }}"
                >
                    {{-- Vue 掛載前與未啟用 JavaScript 時顯示的內容 --}}
                    <ul class="mt-2 text-[13px] text-muted">
                        @foreach ($product->variants as $variant)
                            <li>{{ $variant->weight_grams }}g NT${{ number_format($variant->price) }}</li>
                        @endforeach
                    </ul>
                </div>
            </li>
        @endforeach
    </ul>
@endsection

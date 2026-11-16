@extends('layouts.admin')

@section('title', '咖啡豆')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <h1 class="font-serif text-[30px] font-medium tracking-[0.1em]">咖啡豆</h1>
        <a href="{{ route('admin.products.create') }}" class="inline-flex min-h-12 items-center bg-ink px-6 text-sm tracking-[0.14em] text-paper no-underline">新增咖啡豆</a>
    </div>

    @if ($products->isEmpty())
        <p class="text-subtle">還沒有咖啡豆。</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                <thead class="border-b border-ink text-[13px] tracking-[0.08em] text-subtle">
                    <tr>
                        <th scope="col" class="py-3 pr-4 font-normal">名稱</th>
                        <th scope="col" class="py-3 pr-4 font-normal">產地</th>
                        <th scope="col" class="py-3 pr-4 font-normal">烘焙度</th>
                        <th scope="col" class="py-3 pr-4 font-normal">規格數</th>
                        <th scope="col" class="py-3 pr-4 font-normal">總庫存</th>
                        <th scope="col" class="py-3 pr-4 font-normal">狀態</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-b border-line">
                            <td class="py-3 pr-4">
                                <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex min-h-11 items-center font-serif tracking-[0.06em] text-ink underline-offset-4 hover:underline">{{ $product->name }}</a>
                            </td>
                            <td class="py-3 pr-4">{{ $product->origin }}</td>
                            <td class="py-3 pr-4">{{ $product->roast->value }}</td>
                            <td class="py-3 pr-4">{{ $product->variants_count }}</td>
                            <td class="py-3 pr-4">{{ (int) $product->variants_sum_stock }}</td>
                            <td class="py-3 pr-4">
                                @if ($product->is_published)
                                    上架中
                                @else
                                    <span class="text-accent">已下架</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

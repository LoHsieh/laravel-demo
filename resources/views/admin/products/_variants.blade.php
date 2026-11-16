{{-- 咖啡豆編輯頁的規格管理。每個規格是自己的表單，驗證錯誤放在各自的 error bag（variant{id}、newVariant） --}}
<section aria-labelledby="variants-heading" class="mt-16 max-w-[720px] border-t border-line pt-10">
    <h2 id="variants-heading" class="font-serif text-[24px] font-medium tracking-[0.1em]">規格</h2>
    <p class="mt-1 mb-6 text-[13px] text-subtle">重量建立後不能修改，要換重量請刪除後再新增。刪除的規格不會出現在前台，但既有訂單仍會顯示它。</p>

    @error('variant')
        <p role="alert" class="mb-6 border border-accent px-4 py-3 text-sm text-accent">{{ $message }}</p>
    @enderror

    @if ($product->variants->isEmpty())
        <p class="mb-8 text-sm text-subtle">還沒有規格，新增規格後顧客才能購買這支咖啡豆。</p>
    @else
        <ul class="mb-10 border-t border-line">
            @foreach ($product->variants as $variant)
                @php($bag = $errors->getBag("variant{$variant->id}"))
                <li class="flex flex-wrap items-end gap-x-6 gap-y-3 border-b border-line py-4">
                    <form id="variant-{{ $variant->id }}" method="POST" action="{{ route('admin.variants.update', $variant) }}" novalidate
                          class="flex flex-1 flex-wrap items-end gap-x-6 gap-y-3">
                        @csrf
                        @method('PATCH')
                        <p class="w-20 font-latin text-xl leading-12">{{ $variant->label }}</p>
                        <div>
                            <label for="variant-{{ $variant->id }}-price" class="block text-[13px] text-subtle">價格（NT$）</label>
                            <input id="variant-{{ $variant->id }}-price" name="price" type="number" min="1" inputmode="numeric"
                                   value="{{ $bag->any() ? old('price') : $variant->price }}"
                                   @if ($bag->has('price')) aria-invalid="true" @endif
                                   class="block min-h-12 w-32 border border-outline bg-paper px-3 outline-none focus:border-ink aria-[invalid=true]:border-accent">
                        </div>
                        <div>
                            <label for="variant-{{ $variant->id }}-stock" class="block text-[13px] text-subtle">庫存（包）</label>
                            <input id="variant-{{ $variant->id }}-stock" name="stock" type="number" min="0" inputmode="numeric"
                                   value="{{ $bag->any() ? old('stock') : $variant->stock }}"
                                   @if ($bag->has('stock')) aria-invalid="true" @endif
                                   class="block min-h-12 w-28 border border-outline bg-paper px-3 outline-none focus:border-ink aria-[invalid=true]:border-accent">
                        </div>
                        <button type="submit" class="min-h-12 border border-ink px-5 text-sm tracking-[0.12em]">儲存</button>
                    </form>
                    <form method="POST" action="{{ route('admin.variants.destroy', $variant) }}"
                          onsubmit="return confirm('確定要刪除規格 {{ $variant->label }} 嗎？')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="min-h-12 px-2 text-sm text-accent underline underline-offset-4">刪除</button>
                    </form>
                    @if ($bag->any())
                        <ul class="w-full text-[13px] text-accent">
                            @foreach ($bag->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @php($bag = $errors->getBag('newVariant'))
    <h3 class="mb-3 text-[13px] tracking-[0.08em] text-subtle">新增規格</h3>
    <form method="POST" action="{{ route('admin.products.variants.store', $product) }}" novalidate
          class="flex flex-wrap items-end gap-x-6 gap-y-3">
        @csrf
        <div>
            <label for="new-variant-weight" class="block text-[13px] text-subtle">重量（g）</label>
            <input id="new-variant-weight" name="weight_grams" type="number" min="1" inputmode="numeric" value="{{ $bag->any() ? old('weight_grams') : '' }}"
                   @if ($bag->has('weight_grams')) aria-invalid="true" @endif
                   class="block min-h-12 w-28 border border-outline bg-paper px-3 outline-none focus:border-ink aria-[invalid=true]:border-accent">
        </div>
        <div>
            <label for="new-variant-price" class="block text-[13px] text-subtle">價格（NT$）</label>
            <input id="new-variant-price" name="price" type="number" min="1" inputmode="numeric" value="{{ $bag->any() ? old('price') : '' }}"
                   @if ($bag->has('price')) aria-invalid="true" @endif
                   class="block min-h-12 w-32 border border-outline bg-paper px-3 outline-none focus:border-ink aria-[invalid=true]:border-accent">
        </div>
        <div>
            <label for="new-variant-stock" class="block text-[13px] text-subtle">庫存（包）</label>
            <input id="new-variant-stock" name="stock" type="number" min="0" inputmode="numeric" value="{{ $bag->any() ? old('stock') : '0' }}"
                   @if ($bag->has('stock')) aria-invalid="true" @endif
                   class="block min-h-12 w-28 border border-outline bg-paper px-3 outline-none focus:border-ink aria-[invalid=true]:border-accent">
        </div>
        <button type="submit" class="min-h-12 bg-ink px-6 text-sm tracking-[0.14em] text-paper">新增規格</button>
    </form>
    @if ($bag->any())
        <ul class="mt-3 text-[13px] text-accent">
            @foreach ($bag->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</section>

@extends('layouts.admin')

@section('title', $product->exists ? $product->name : '新增咖啡豆')

@section('content')
    <p class="mb-2 text-[13px]"><a href="{{ route('admin.products.index') }}" class="inline-flex min-h-11 items-center text-subtle underline underline-offset-4">← 咖啡豆列表</a></p>
    <h1 class="mb-8 font-serif text-[30px] font-medium tracking-[0.1em]">{{ $product->exists ? $product->name : '新增咖啡豆' }}</h1>

    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" novalidate
          enctype="multipart/form-data" class="max-w-[720px]">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <x-form-field name="name" label="名稱" :value="$product->name" required />

        <div class="grid grid-cols-2 gap-x-6 max-sm:grid-cols-1">
            <x-form-field name="origin" label="產地" :value="$product->origin" list="origin-options" required />
            <x-form-field name="process" label="處理法" :value="$product->process" list="process-options" required />
        </div>
        <datalist id="origin-options">
            @foreach ($origins as $origin)
                <option value="{{ $origin }}"></option>
            @endforeach
        </datalist>
        <datalist id="process-options">
            @foreach ($processes as $process)
                <option value="{{ $process }}"></option>
            @endforeach
        </datalist>

        <fieldset class="mb-5">
            <legend class="mb-1.5 text-[13px] tracking-[0.08em] text-subtle">烘焙度</legend>
            <div class="flex flex-wrap gap-x-6">
                @foreach ($roasts as $roast)
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2">
                        <input type="radio" name="roast" value="{{ $roast->value }}" class="size-4 accent-ink"
                               @checked(old('roast', $product->roast?->value) === $roast->value)>
                        {{ $roast->value }}
                    </label>
                @endforeach
            </div>
            @error('roast')
                <p class="mt-1.5 text-[13px] text-accent">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="mb-5">
            <legend class="mb-1.5 text-[13px] tracking-[0.08em] text-subtle">風味標籤</legend>
            @php($selectedNotes = array_map('intval', old('flavor_notes', $product->exists ? $product->flavorNotes->modelKeys() : [])))
            <div class="flex flex-wrap gap-x-6">
                @forelse ($flavorNotes as $note)
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2">
                        <input type="checkbox" name="flavor_notes[]" value="{{ $note->id }}" class="size-4 accent-ink"
                               @checked(in_array($note->id, $selectedNotes, true))>
                        {{ $note->name }}
                    </label>
                @empty
                    <p class="text-sm text-subtle">還沒有風味標籤，請先到<a href="{{ route('admin.flavor-notes.index') }}" class="text-ink underline underline-offset-4">風味標籤</a>新增。</p>
                @endforelse
            </div>
            @error('flavor_notes.*')
                <p class="mt-1.5 text-[13px] text-accent">{{ $message }}</p>
            @enderror
        </fieldset>

        <div class="mb-5">
            <label for="flavor_description" class="mb-1.5 block text-[13px] tracking-[0.08em] text-subtle">風味描述</label>
            <textarea id="flavor_description" name="flavor_description" rows="3"
                      @error('flavor_description') aria-invalid="true" aria-describedby="flavor_description-error" @enderror
                      class="block w-full border border-outline bg-paper px-3.5 py-2.5 text-[15px] text-ink outline-none focus:border-ink aria-[invalid=true]:border-accent">{{ old('flavor_description', $product->flavor_description) }}</textarea>
            @error('flavor_description')
                <p id="flavor_description-error" class="mt-1.5 text-[13px] text-accent">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="image" class="mb-1.5 block text-[13px] tracking-[0.08em] text-subtle">圖片</label>
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="目前的圖片" class="mb-3 aspect-[4/5] w-40 bg-sand object-cover">
            @endif
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                   aria-describedby="image-hint @error('image') image-error @enderror"
                   @error('image') aria-invalid="true" @enderror
                   class="block w-full text-sm text-subtle file:mr-4 file:min-h-11 file:cursor-pointer file:border file:border-ink file:bg-paper file:px-5 file:text-sm file:text-ink">
            <p id="image-hint" class="mt-1.5 text-[13px] text-subtle">JPEG、PNG 或 WebP，2 MB 以內。{{ $product->image_url ? '選擇新圖片會取代目前的圖片。' : '沒有圖片時，前台顯示牛皮紙袋插圖。' }}</p>
            @error('image')
                <p id="image-error" class="mt-1.5 text-[13px] text-accent">{{ $message }}</p>
            @enderror
            @if ($product->image_url)
                <label class="mt-1 inline-flex min-h-11 cursor-pointer items-center gap-2">
                    <input type="checkbox" name="remove_image" value="1" class="size-4 accent-ink">
                    移除圖片，改顯示牛皮紙袋插圖
                </label>
            @endif
        </div>

        <input type="hidden" name="is_published" value="0">
        <label class="mb-8 inline-flex min-h-11 cursor-pointer items-center gap-2">
            <input type="checkbox" name="is_published" value="1" class="size-4 accent-ink"
                   @checked(old('is_published', $product->is_published))>
            上架（取消勾選即下架，下架的咖啡豆不會出現在前台）
        </label>

        <div>
            <button type="submit" class="min-h-13 bg-ink px-10 text-sm tracking-[0.16em] text-paper">{{ $product->exists ? '儲存' : '新增咖啡豆' }}</button>
        </div>
    </form>

    @if ($product->exists)
        @include('admin.products._variants')
    @endif
@endsection

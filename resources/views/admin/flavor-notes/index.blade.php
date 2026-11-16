@extends('layouts.admin')

@section('title', '風味標籤')

@section('content')
    <h1 class="font-serif text-[30px] font-medium tracking-[0.1em]">風味標籤</h1>
    <p class="mt-1 mb-8 text-[13px] text-subtle">顧客在咖啡豆列表依風味標籤篩選。刪除風味標籤會一併從咖啡豆上移除。</p>

    <div class="max-w-[720px]">
        @if ($flavorNotes->isEmpty())
            <p class="mb-8 text-sm text-subtle">還沒有風味標籤。</p>
        @else
            <ul class="mb-10 border-t border-line">
                @foreach ($flavorNotes as $note)
                    @php($bag = $errors->getBag("flavorNote{$note->id}"))
                    <li class="flex flex-wrap items-end gap-x-6 gap-y-3 border-b border-line py-4">
                        <form method="POST" action="{{ route('admin.flavor-notes.update', $note) }}" novalidate
                              class="flex flex-1 flex-wrap items-end gap-x-6 gap-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="flavor-note-{{ $note->id }}" class="block text-[13px] text-subtle">名稱</label>
                                <input id="flavor-note-{{ $note->id }}" name="name" type="text"
                                       value="{{ $bag->any() ? old('name') : $note->name }}"
                                       @if ($bag->has('name')) aria-invalid="true" @endif
                                       class="block min-h-12 w-48 border border-outline bg-paper px-3 outline-none focus:border-ink aria-[invalid=true]:border-accent">
                            </div>
                            <p class="text-[13px] leading-12 text-subtle">{{ $note->products_count }} 支咖啡豆</p>
                            <button type="submit" class="min-h-12 border border-ink px-5 text-sm tracking-[0.12em]">儲存</button>
                        </form>
                        <form method="POST" action="{{ route('admin.flavor-notes.destroy', $note) }}"
                              onsubmit="return confirm('確定要刪除風味標籤「{{ $note->name }}」嗎？')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="min-h-12 px-2 text-sm text-accent underline underline-offset-4">刪除</button>
                        </form>
                        @if ($bag->any())
                            <p class="w-full text-[13px] text-accent">{{ $bag->first('name') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @php($bag = $errors->getBag('newFlavorNote'))
        <form method="POST" action="{{ route('admin.flavor-notes.store') }}" novalidate class="flex flex-wrap items-end gap-x-6 gap-y-3">
            @csrf
            <div>
                <label for="new-flavor-note" class="block text-[13px] text-subtle">新增風味標籤</label>
                <input id="new-flavor-note" name="name" type="text" value="{{ $bag->any() ? old('name') : '' }}"
                       @if ($bag->has('name')) aria-invalid="true" @endif
                       class="block min-h-12 w-48 border border-outline bg-paper px-3 outline-none focus:border-ink aria-[invalid=true]:border-accent">
            </div>
            <button type="submit" class="min-h-12 bg-ink px-6 text-sm tracking-[0.14em] text-paper">新增</button>
        </form>
        @if ($bag->any())
            <p class="mt-3 text-[13px] text-accent">{{ $bag->first('name') }}</p>
        @endif
    </div>
@endsection

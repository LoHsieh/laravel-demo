@extends('layouts.app')

@section('title', '前往付款｜焙日 Bairi')

@section('content')
    <div class="mx-auto max-w-[720px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:px-5 max-sm:pt-7 max-sm:pb-20">
        <h1 class="mb-6 font-serif text-[28px] font-medium tracking-[0.12em] max-sm:text-[24px]">正在前往綠界付款頁…</h1>
        <form id="ecpay-checkout" method="post" action="{{ $form->action }}">
            @foreach ($form->fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <p class="text-sm text-subtle">若頁面沒有自動跳轉，請按下方按鈕。</p>
            <button type="submit" class="mt-6 inline-flex min-h-11 items-center bg-ink px-8 text-sm tracking-[0.2em] text-paper">前往綠界付款</button>
        </form>
    </div>
    <script>document.getElementById('ecpay-checkout').submit();</script>
@endsection

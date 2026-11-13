@extends('layouts.app')

@section('title', '結帳｜焙日 Bairi')

@section('content')
    <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:px-5 max-sm:pt-7 max-sm:pb-20">
        <h1 class="mb-6 font-serif text-[34px] font-medium tracking-[0.12em] max-sm:text-[26px]">結帳</h1>
        <p class="text-sm text-subtle">結帳功能即將開放。你的購物車已保存在會員帳號中，之後登入時依然會在。</p>
        <a href="{{ route('cart.show') }}" class="mt-8 inline-flex min-h-11 items-center text-sm tracking-[0.1em] text-ink">回到購物車</a>
    </div>
@endsection

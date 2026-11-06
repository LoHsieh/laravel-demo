@extends('layouts.app')

@section('title', '會員中心｜焙日 Bairi')

@section('content')
    <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:px-5 max-sm:pt-7 max-sm:pb-20">
        <div class="mb-12 flex flex-wrap items-end justify-between gap-x-8 gap-y-3 max-sm:mb-8">
            <div>
                <p class="font-latin text-[13px] tracking-[0.3em] text-accent uppercase">My account</p>
                <h1 class="mt-1 font-serif text-[34px] font-medium tracking-[0.12em] max-sm:text-[26px]">會員中心</h1>
                <p class="mt-1 text-[13px] text-subtle">{{ $customer->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center border-b border-ink text-sm tracking-[0.1em] text-ink">登出</button>
            </form>
        </div>

        <section aria-labelledby="recipient-heading" class="max-w-[560px] border border-line bg-raised px-10 py-10 max-sm:border-x-0 max-sm:bg-transparent max-sm:px-0 max-sm:py-8">
            <h2 id="recipient-heading" class="font-serif text-[22px] font-medium tracking-[0.1em]">
                <span class="mr-2 font-latin text-accent">01</span>預設收件資訊
            </h2>
            <p class="mt-1 mb-7 text-[13px] text-subtle">結帳時會自動帶入，仍可在結帳頁修改。</p>

            @if (session('status'))
                <p role="status" class="mb-6 bg-sand px-4 py-3 text-sm text-ink">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('account.recipient.update') }}" novalidate>
                @csrf
                @method('PUT')
                <x-form-field name="recipient_name" label="收件人姓名" :value="$customer->recipient_name" autocomplete="name" required />
                <x-form-field name="recipient_phone" label="收件人電話" type="tel" :value="$customer->recipient_phone" autocomplete="tel" required />
                <x-form-field name="recipient_address" label="收件地址" :value="$customer->recipient_address" autocomplete="street-address" required />

                <button type="submit" class="mt-3 min-h-13 bg-ink px-10 text-sm tracking-[0.16em] text-paper max-sm:w-full">儲存</button>
            </form>
        </section>
    </div>
@endsection

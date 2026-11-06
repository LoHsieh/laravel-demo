@props(['title', 'eyebrow'])

{{-- 註冊、登入、重設密碼共用的置中表單框 --}}
<div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-16 pb-30 max-sm:px-5 max-sm:pt-8 max-sm:pb-20">
    <div class="mx-auto max-w-[440px] border border-line bg-raised px-10 py-12 max-sm:border-x-0 max-sm:px-0 max-sm:bg-transparent max-sm:py-8">
        <p class="font-latin text-[13px] tracking-[0.3em] text-accent uppercase">{{ $eyebrow }}</p>
        <h1 class="mt-2 mb-8 font-serif text-[30px] font-medium tracking-[0.12em] max-sm:text-[26px]">{{ $title }}</h1>

        @if (session('status'))
            <p role="status" class="mb-6 bg-sand px-4 py-3 text-sm text-ink">{{ session('status') }}</p>
        @endif

        {{ $slot }}
    </div>
</div>

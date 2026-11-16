@extends('layouts.admin')

@section('title', '店家登入')

@section('content')
    <div class="mx-auto max-w-[440px] border border-line bg-raised px-10 py-12 max-sm:border-x-0 max-sm:bg-transparent max-sm:px-0 max-sm:py-6">
        <p class="font-latin text-[13px] tracking-[0.3em] text-accent uppercase">Admin sign in</p>
        <h1 class="mt-2 mb-2 font-serif text-[30px] font-medium tracking-[0.12em]">店家登入</h1>
        <p class="mb-8 text-[13px] text-subtle">後台只接受店家帳號，顧客請由<a href="{{ route('login') }}" class="text-ink underline underline-offset-4">前台登入</a>。</p>

        <form method="POST" action="{{ route('admin.login') }}" novalidate>
            @csrf
            <x-form-field name="email" label="Email" type="email" autocomplete="username" required autofocus />
            <x-form-field name="password" label="密碼" type="password" autocomplete="current-password" required />

            <label class="mb-6 inline-flex min-h-11 cursor-pointer items-center gap-2 text-[13px] text-subtle">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-ink" @checked(old('remember'))>
                記住我
            </label>

            <button type="submit" class="min-h-13 w-full bg-ink text-sm tracking-[0.16em] text-paper">登入後台</button>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', '登入｜焙日 Bairi')

@section('content')
    <x-auth-card title="登入" eyebrow="Sign in">
        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <x-form-field name="email" label="Email" type="email" autocomplete="email" required autofocus />
            <x-form-field name="password" label="密碼" type="password" autocomplete="current-password" required />

            <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-[13px] text-subtle">
                    <input type="checkbox" name="remember" value="1" class="size-4 accent-ink" @checked(old('remember'))>
                    記住我
                </label>
                <a href="{{ route('password.request') }}" class="inline-flex min-h-11 items-center text-[13px] text-ink underline underline-offset-4">忘記密碼？</a>
            </div>

            <button type="submit" class="min-h-13 w-full bg-ink text-sm tracking-[0.16em] text-paper">登入</button>
        </form>

        <p class="mt-8 text-center text-[13px] text-subtle">
            還沒有帳號？<a href="{{ route('register') }}" class="inline-flex min-h-11 items-center text-ink underline underline-offset-4">註冊會員</a>
        </p>
    </x-auth-card>
@endsection

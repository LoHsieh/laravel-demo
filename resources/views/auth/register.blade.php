@extends('layouts.app')

@section('title', '註冊｜焙日 Bairi')

@section('content')
    <x-auth-card title="註冊會員" eyebrow="Create account">
        <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf
            <x-form-field name="email" label="Email" type="email" autocomplete="email" required autofocus />
            <x-form-field name="password" label="密碼（至少 8 個字元）" type="password" autocomplete="new-password" required />
            <x-form-field name="password_confirmation" label="再次輸入密碼" type="password" autocomplete="new-password" required />

            <button type="submit" class="mt-3 min-h-13 w-full bg-ink text-sm tracking-[0.16em] text-paper">註冊</button>
        </form>

        <p class="mt-8 text-center text-[13px] text-subtle">
            已經有帳號了？<a href="{{ route('login') }}" class="inline-flex min-h-11 items-center text-ink underline underline-offset-4">登入</a>
        </p>
    </x-auth-card>
@endsection

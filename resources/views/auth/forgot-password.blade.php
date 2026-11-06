@extends('layouts.app')

@section('title', '忘記密碼｜焙日 Bairi')

@section('content')
    <x-auth-card title="忘記密碼" eyebrow="Forgot password">
        <p class="mb-6 text-sm text-subtle">輸入註冊時的 Email，我們會寄給你重設密碼的連結。</p>

        <form method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf
            <x-form-field name="email" label="Email" type="email" autocomplete="email" required autofocus />

            <button type="submit" class="mt-3 min-h-13 w-full bg-ink text-sm tracking-[0.16em] text-paper">寄送重設連結</button>
        </form>

        <p class="mt-8 text-center text-[13px] text-subtle">
            想起來了？<a href="{{ route('login') }}" class="inline-flex min-h-11 items-center text-ink underline underline-offset-4">回到登入</a>
        </p>
    </x-auth-card>
@endsection

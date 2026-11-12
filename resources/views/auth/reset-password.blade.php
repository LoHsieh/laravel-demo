@extends('layouts.app')

@section('title', '重設密碼｜焙日 Bairi')

@section('content')
    <x-auth-card title="重設密碼" eyebrow="Reset password">
        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-form-field name="email" label="Email" type="email" :value="$email" autocomplete="email" required />
            <x-form-field name="password" label="新密碼（至少 8 個字元）" type="password" autocomplete="new-password" required autofocus />
            <x-form-field name="password_confirmation" label="再次輸入新密碼" type="password" autocomplete="new-password" required />

            <button type="submit" class="mt-3 min-h-13 w-full bg-ink text-sm tracking-[0.16em] text-paper">設定新密碼</button>
        </form>
    </x-auth-card>
@endsection

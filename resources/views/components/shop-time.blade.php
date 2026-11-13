@props(['at'])

{{-- 以店家時區顯示的時間，例如 2026/11/13 14:30。所有顯示給顧客與店家看的時間都經過這裡。 --}}
<time datetime="{{ $at->toIso8601String() }}" {{ $attributes }}>{{ $at->copy()->setTimezone(config('app.shop_timezone'))->format('Y/m/d H:i') }}</time>

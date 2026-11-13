{{-- 前往綠界付款（或重新付款）。$autoSubmit 為 true 時載入後自動送出，用在剛下單完成時。 --}}
<form id="start-payment" method="post" action="{{ route('payments.store', $order) }}" class="mt-8">
    @csrf
    @error('payment')
        <p role="alert" class="mb-4 text-sm text-accent">{{ $message }}</p>
    @enderror
    <button type="submit" class="flex min-h-14 w-full cursor-pointer items-center justify-center bg-ink text-[15px] tracking-[0.16em] text-paper">{{ $label ?? '前往綠界付款' }}</button>
    <p class="mt-3 text-xs text-subtle">綠界測試環境，可使用測試卡號 4311-9511-1111-1111，有效期限填未來的月年，安全碼任意 3 碼，簡訊驗證碼 1234。</p>
</form>
@if ($autoSubmit ?? false)
    <script>document.getElementById('start-payment').submit();</script>
@endif

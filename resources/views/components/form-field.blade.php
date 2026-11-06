@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null])

<div class="mb-5">
    <label for="{{ $name }}" class="mb-1.5 block text-[13px] tracking-[0.08em] text-subtle">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
           {{ $attributes->merge(['class' => 'block min-h-12 w-full border border-outline bg-paper px-3.5 text-[15px] text-ink outline-none focus:border-ink aria-[invalid=true]:border-accent']) }}>
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-[13px] text-accent">{{ $message }}</p>
    @enderror
</div>

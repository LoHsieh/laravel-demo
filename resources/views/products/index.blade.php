@extends('layouts.app')

@section('title', '咖啡豆｜焙日 Bairi')

@section('content')
    <div class="mx-auto max-w-[1200px] px-[clamp(16px,4vw,48px)] pt-14 pb-30 max-sm:pt-7">
        <div data-vue-component="ProductBrowser" data-props="{{ json_encode($browser) }}">
            <h1 class="font-serif text-[34px] font-medium tracking-[0.12em]">咖啡豆</h1>
        </div>
    </div>
@endsection

<?php

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('shows the image of a 咖啡豆 on the list, the detail page and the home page', function () {
    $image = UploadedFile::fake()->create('bag.jpg', 300, 'image/jpeg')->store('products', 'public');
    $product = Product::factory()->has(Variant::factory())->create(['image' => $image]);
    $url = Storage::disk('public')->url($image);

    $this->getJson('/beans')->assertJsonPath('products.0.image_url', $url);
    $this->get("/beans/{$product->id}")->assertSee('<img src="'.e($url).'"', escape: false);
    $this->get('/')->assertSee('<img src="'.e($url).'"', escape: false);
});

it('shows the 牛皮紙袋 illustration for a 咖啡豆 without an image', function () {
    $product = Product::factory()->has(Variant::factory())->create(['image' => null]);

    $this->getJson('/beans')->assertJsonPath('products.0.image_url', null);
    $this->get("/beans/{$product->id}")->assertDontSee('<img', escape: false);
    $this->get('/')->assertDontSee('<img', escape: false);
});

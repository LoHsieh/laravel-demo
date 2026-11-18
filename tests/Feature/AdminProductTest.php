<?php

use App\Catalog\Roast;
use App\Models\Admin;
use App\Models\FlavorNote;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(Admin::factory()->create(), 'admin');

    $this->floral = FlavorNote::factory()->create(['name' => '花香']);
    $this->fruity = FlavorNote::factory()->create(['name' => '果酸']);
    $this->nutty = FlavorNote::factory()->create(['name' => '堅果']);
});

/**
 * 新增或編輯咖啡豆表單送出的欄位。
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productForm(array $overrides = []): array
{
    return [
        'name' => '盧安達 尼亞魯 水洗',
        'origin' => '盧安達',
        'process' => '水洗',
        'roast' => '淺中焙',
        'flavor_description' => '紅茶・柑橘・蔗糖',
        'flavor_notes' => [test()->floral->id, test()->fruity->id],
        'is_published' => '1',
        ...$overrides,
    ];
}

it('lets a 店家 add a 咖啡豆 with its 產地, 處理法, 烘焙度, 風味標籤 and 風味描述', function () {
    $response = $this->post('/admin/products', productForm());

    $product = Product::sole();
    $response->assertRedirect("/admin/products/{$product->id}/edit");

    expect($product)
        ->name->toBe('盧安達 尼亞魯 水洗')
        ->origin->toBe('盧安達')
        ->process->toBe('水洗')
        ->roast->toBe(Roast::MediumLight)
        ->flavor_description->toBe('紅茶・柑橘・蔗糖')
        ->is_published->toBeTrue()
        ->and($product->flavorNotes->pluck('name')->sort()->values()->all())->toBe(['果酸', '花香']);

    $this->get("/beans/{$product->id}")->assertOk()->assertSee('盧安達 尼亞魯 水洗');
});

it('requires a name, 產地, 處理法, a known 烘焙度 and a 風味描述 for a 咖啡豆', function () {
    $this->from('/admin/products/create')
        ->post('/admin/products', productForm([
            'name' => '', 'origin' => '', 'process' => '', 'roast' => '極深焙', 'flavor_description' => '',
            'flavor_notes' => [999999],
        ]))
        ->assertRedirect('/admin/products/create')
        ->assertSessionHasErrors(['name', 'origin', 'process', 'roast', 'flavor_description', 'flavor_notes.0']);

    expect(Product::count())->toBe(0);
});

it('lets a 店家 edit a 咖啡豆 and change its 風味標籤', function () {
    $product = Product::factory()->create(['name' => '舊名稱']);
    $product->flavorNotes()->attach([$this->floral->id, $this->fruity->id]);

    $this->get("/admin/products/{$product->id}/edit")->assertOk()->assertSee('舊名稱');

    $this->put("/admin/products/{$product->id}", productForm([
        'name' => '新名稱', 'roast' => '深焙', 'flavor_notes' => [$this->nutty->id],
    ]))->assertRedirect("/admin/products/{$product->id}/edit");

    expect($product->fresh())
        ->name->toBe('新名稱')
        ->roast->toBe(Roast::Dark)
        ->and($product->flavorNotes()->pluck('name')->all())->toBe(['堅果']);
});

it('hides an unpublished 咖啡豆 from the 前台 and shows it again when republished', function () {
    $product = Product::factory()->create(['name' => '盧安達 尼亞魯 水洗']);

    $this->put("/admin/products/{$product->id}", productForm(['is_published' => '0']));

    expect($product->fresh()->is_published)->toBeFalse();
    $this->get('/beans')->assertDontSee('盧安達 尼亞魯 水洗');
    $this->get("/beans/{$product->id}")->assertNotFound();
    $this->get('/admin/products')->assertSee('盧安達 尼亞魯 水洗')->assertSee('已下架');

    $this->put("/admin/products/{$product->id}", productForm(['is_published' => '1']));

    $this->get("/beans/{$product->id}")->assertOk();
});

it('does not offer a way to delete a 咖啡豆', function () {
    $product = Product::factory()->create();

    $this->delete("/admin/products/{$product->id}")->assertMethodNotAllowed();

    expect($product->fresh())->not->toBeNull();
});

it('lets a 店家 upload an image for a new 咖啡豆', function () {
    Storage::fake('public');

    $this->post('/admin/products', productForm([
        'image' => UploadedFile::fake()->create('rwanda.jpg', 300, 'image/jpeg'),
    ]))->assertRedirect();

    $product = Product::sole();
    expect($product->image)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image);
});

it('replaces the image of a 咖啡豆 and deletes the old file', function () {
    Storage::fake('public');
    $old = UploadedFile::fake()->create('old.jpg', 300, 'image/jpeg')->store('products', 'public');
    $product = Product::factory()->create(['image' => $old]);

    $this->put("/admin/products/{$product->id}", productForm([
        'image' => UploadedFile::fake()->create('new.png', 300, 'image/png'),
    ]))->assertRedirect("/admin/products/{$product->id}/edit");

    $new = $product->fresh()->image;
    expect($new)->not->toBe($old);
    Storage::disk('public')->assertExists($new);
    Storage::disk('public')->assertMissing($old);
});

it('keeps the image of a 咖啡豆 when the 店家 saves without choosing a new one', function () {
    Storage::fake('public');
    $image = UploadedFile::fake()->create('bag.jpg', 300, 'image/jpeg')->store('products', 'public');
    $product = Product::factory()->create(['image' => $image]);

    $this->put("/admin/products/{$product->id}", productForm(['name' => '新名稱']));

    expect($product->fresh()->image)->toBe($image);
    Storage::disk('public')->assertExists($image);
});

it('lets a 店家 remove the image of a 咖啡豆', function () {
    Storage::fake('public');
    $image = UploadedFile::fake()->create('bag.jpg', 300, 'image/jpeg')->store('products', 'public');
    $product = Product::factory()->create(['image' => $image]);

    $this->put("/admin/products/{$product->id}", productForm(['remove_image' => '1']));

    expect($product->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing($image);
});

it('stores 咖啡豆 images on the disk chosen by configuration', function () {
    config(['filesystems.product_images' => 's3']);
    Storage::fake('s3');
    Storage::fake('public');

    $this->post('/admin/products', productForm([
        'image' => UploadedFile::fake()->create('rwanda.jpg', 300, 'image/jpeg'),
    ]));

    $image = Product::sole()->image;
    Storage::disk('s3')->assertExists($image);
    Storage::disk('public')->assertMissing($image);
    $this->getJson('/beans')->assertJsonPath('products.0.image_url', Storage::disk('s3')->url($image));
});

it('limits how often a 店家 can save 咖啡豆 from the same IP', function () {
    $product = Product::factory()->create();

    foreach (range(1, 10) as $_) {
        $this->put("/admin/products/{$product->id}", productForm())->assertRedirect();
    }

    $this->put("/admin/products/{$product->id}", productForm())->assertTooManyRequests();
    $this->post('/admin/products', productForm())->assertTooManyRequests();
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->put("/admin/products/{$product->id}", productForm())->assertRedirect();
});

it('stops accepting images once the daily upload limit for the whole site is reached', function () {
    config(['filesystems.product_images_daily_uploads' => 1]);
    Storage::fake('public');
    $product = Product::factory()->create();

    $this->put("/admin/products/{$product->id}", productForm([
        'image' => UploadedFile::fake()->create('first.jpg', 300, 'image/jpeg'),
    ]))->assertSessionHasNoErrors();
    $first = $product->fresh()->image;

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->from("/admin/products/{$product->id}/edit")
        ->put("/admin/products/{$product->id}", productForm([
            'name' => '新名稱',
            'image' => UploadedFile::fake()->create('second.jpg', 300, 'image/jpeg'),
        ]))
        ->assertRedirect("/admin/products/{$product->id}/edit")
        ->assertSessionHasErrors('image');

    expect($product->fresh())->image->toBe($first)->name->not->toBe('新名稱');
    expect(Storage::disk('public')->allFiles())->toBe([$first]);

    $this->put("/admin/products/{$product->id}", productForm(['name' => '新名稱']))->assertSessionHasNoErrors();
    expect($product->fresh()->name)->toBe('新名稱');
});

it('only accepts JPEG, PNG or WebP images up to 2 MB', function (UploadedFile $file) {
    Storage::fake('public');

    $this->from('/admin/products/create')
        ->post('/admin/products', productForm(['image' => $file]))
        ->assertRedirect('/admin/products/create')
        ->assertSessionHasErrors('image');

    expect(Product::count())->toBe(0);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
})->with([
    'not an image' => fn () => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf'),
    'an SVG' => fn () => UploadedFile::fake()->create('bag.svg', 10, 'image/svg+xml'),
    'larger than 2 MB' => fn () => UploadedFile::fake()->create('huge.jpg', 2049, 'image/jpeg'),
]);

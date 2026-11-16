<?php

use App\Catalog\Roast;
use App\Models\Admin;
use App\Models\FlavorNote;
use App\Models\Product;

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

<?php

use App\Models\Admin;
use App\Models\Customer;
use App\Models\FlavorNote;
use App\Models\Product;

beforeEach(function () {
    $this->actingAs(Admin::factory()->create(), 'admin');
});

it('lists the 風味標籤 with how many 咖啡豆 use each', function () {
    $floral = FlavorNote::factory()->create(['name' => '花香']);
    FlavorNote::factory()->create(['name' => '堅果']);
    Product::factory()->count(2)->hasAttached($floral)->create();

    $this->get('/admin/flavor-notes')
        ->assertOk()
        ->assertSeeInOrder(['花香', '2 支咖啡豆', '堅果', '0 支咖啡豆']);
});

it('lets a 店家 add a 風味標籤, which 顧客 can then filter by', function () {
    $this->post('/admin/flavor-notes', ['name' => '柑橘'])->assertRedirect('/admin/flavor-notes');

    $citrus = FlavorNote::sole();
    expect($citrus->name)->toBe('柑橘');

    Product::factory()->hasAttached($citrus)->create();
    $this->getJson('/beans?flavor[]=柑橘')->assertJsonCount(1, 'products');
});

it('rejects an empty or duplicate 風味標籤 name', function () {
    FlavorNote::factory()->create(['name' => '花香']);

    $this->post('/admin/flavor-notes', ['name' => '花香'])->assertSessionHasErrors('name', errorBag: 'newFlavorNote');
    $this->post('/admin/flavor-notes', ['name' => ' '])->assertSessionHasErrors('name', errorBag: 'newFlavorNote');

    expect(FlavorNote::count())->toBe(1);
});

it('lets a 店家 rename a 風味標籤', function () {
    $note = FlavorNote::factory()->create(['name' => '花朵']);
    FlavorNote::factory()->create(['name' => '堅果']);

    $this->patch("/admin/flavor-notes/{$note->id}", ['name' => '花香'])->assertRedirect('/admin/flavor-notes');
    expect($note->fresh()->name)->toBe('花香');

    $this->patch("/admin/flavor-notes/{$note->id}", ['name' => '堅果'])
        ->assertSessionHasErrors('name', errorBag: "flavorNote{$note->id}");
    expect($note->fresh()->name)->toBe('花香');
});

it('lets a 店家 delete a 風味標籤, removing it from its 咖啡豆', function () {
    $note = FlavorNote::factory()->create(['name' => '花香']);
    $product = Product::factory()->hasAttached($note)->create();

    $this->delete("/admin/flavor-notes/{$note->id}")->assertRedirect('/admin/flavor-notes');

    expect(FlavorNote::count())->toBe(0)
        ->and($product->flavorNotes()->count())->toBe(0);
});

it('keeps 顧客 from managing 風味標籤', function () {
    auth('admin')->logout();
    $this->actingAs(Customer::factory()->create(), 'customer');

    $this->post('/admin/flavor-notes', ['name' => '柑橘'])->assertRedirect('/admin/login');

    expect(FlavorNote::count())->toBe(0);
});

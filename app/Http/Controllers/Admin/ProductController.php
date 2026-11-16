<?php

namespace App\Http\Controllers\Admin;

use App\Catalog\Roast;
use App\Http\Controllers\Controller;
use App\Models\FlavorNote;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 後台的咖啡豆管理。咖啡豆不提供刪除，只能下架（ADR 0006）；下架的咖啡豆不出現在前台。
 */
class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::query()
                ->withCount('variants')
                ->withSum('variants', 'stock')
                ->latest()
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [...$this->formOptions(), 'product' => new Product(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create($validated['attributes']);
            $product->flavorNotes()->sync($validated['flavor_notes']);

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('status', '已新增咖啡豆，接著新增它的規格。');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', [
            ...$this->formOptions(),
            'product' => $product->load(['variants', 'flavorNotes']),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($product, $validated) {
            $product->update($validated['attributes']);
            $product->flavorNotes()->sync($validated['flavor_notes']);
        });

        return redirect()->route('admin.products.edit', $product)->with('status', '已儲存咖啡豆。');
    }

    /**
     * @return array{attributes: array<string, mixed>, flavor_notes: list<int>}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'origin' => ['required', 'string', 'max:255'],
            'process' => ['required', 'string', 'max:255'],
            'roast' => ['required', Rule::enum(Roast::class)],
            'flavor_description' => ['required', 'string', 'max:2000'],
            'flavor_notes' => ['array'],
            'flavor_notes.*' => ['integer', 'distinct', Rule::exists(FlavorNote::class, 'id')],
            'is_published' => ['boolean'],
        ]);

        return [
            'attributes' => [
                ...collect($validated)->only(['name', 'origin', 'process', 'roast', 'flavor_description'])->all(),
                'is_published' => $request->boolean('is_published'),
            ],
            'flavor_notes' => array_map('intval', $validated['flavor_notes'] ?? []),
        ];
    }

    /**
     * 表單的選項。產地與處理法可以自由輸入，並提示已經用過的值，讓篩選的分類保持一致。
     *
     * @return array{roasts: list<Roast>, flavorNotes: Collection<int, FlavorNote>, origins: list<string>, processes: list<string>}
     */
    private function formOptions(): array
    {
        return [
            'roasts' => Roast::cases(),
            'flavorNotes' => FlavorNote::oldest('id')->get(),
            'origins' => Product::query()->distinct()->orderBy('origin')->pluck('origin')->all(),
            'processes' => Product::query()->distinct()->orderBy('process')->pluck('process')->all(),
        ];
    }
}

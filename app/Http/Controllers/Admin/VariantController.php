<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * 後台的規格管理，在咖啡豆的編輯頁上操作。
 *
 * 重量建立後不能修改：既有訂單以規格顯示重量，改重量會讓訂單內容跟著改變。要換重量就刪除再新增。
 * 刪除是軟刪除（ADR 0006）；再次新增同一重量時恢復已刪除的那筆，既有訂單仍指向同一個規格。
 */
class VariantController extends Controller
{
    /**
     * MySQL 因外鍵拒絕刪除資料列的錯誤碼（ER_ROW_IS_REFERENCED_2）。
     */
    private const ROW_IS_REFERENCED = 1451;

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validateWithBag('newVariant', [
            'weight_grams' => [
                'required', 'integer', 'min:1', 'max:100000',
                Rule::unique(Variant::class)->where('product_id', $product->id)->withoutTrashed(),
            ],
            ...$this->priceAndStockRules(),
        ], [
            'weight_grams.unique' => '這支咖啡豆已經有這個重量的規格。',
        ]);

        DB::transaction(function () use ($product, $validated) {
            $variant = $product->variants()->withTrashed()->firstOrNew(['weight_grams' => $validated['weight_grams']]);
            $variant->fill($validated);
            $variant->deleted_at = null;
            $variant->save();
        });

        return redirect()->route('admin.products.edit', $product)->with('status', "已新增規格 {$validated['weight_grams']}g。");
    }

    /**
     * 修改價格與調整庫存。已成立的訂單保存下單當下的單價，不受影響。
     */
    public function update(Request $request, Variant $variant): RedirectResponse
    {
        $validated = $request->validateWithBag("variant{$variant->id}", $this->priceAndStockRules());

        $variant->update($validated);

        return redirect()->route('admin.products.edit', $variant->product_id)->with('status', "已儲存規格 {$variant->label}。");
    }

    public function destroy(Variant $variant): RedirectResponse
    {
        try {
            $variant->delete();
        } catch (QueryException $exception) {
            // 軟刪除不會觸發外鍵；這是萬一有程式真的刪除資料列時的最後一道防線（ADR 0006）
            if (($exception->errorInfo[1] ?? null) !== self::ROW_IS_REFERENCED) {
                throw $exception;
            }

            return redirect()->route('admin.products.edit', $variant->product_id)
                ->withErrors(['variant' => '此規格已有訂單，無法刪除。']);
        }

        return redirect()->route('admin.products.edit', $variant->product_id)
            ->with('status', "已刪除規格 {$variant->label}。既有訂單仍會顯示它。");
    }

    /**
     * @return array<string, list<string>>
     */
    private function priceAndStockRules(): array
    {
        return [
            'price' => ['required', 'integer', 'min:1', 'max:1000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }
}

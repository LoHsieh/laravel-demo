<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlavorNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 後台的風味標籤管理，讓顧客篩選的分類保持一致。刪除風味標籤會一併從咖啡豆上移除。
 */
class FlavorNoteController extends Controller
{
    public function index(): View
    {
        return view('admin.flavor-notes.index', [
            'flavorNotes' => FlavorNote::withCount('products')->oldest('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newFlavorNote', $this->rules());

        FlavorNote::create($validated);

        return redirect()->route('admin.flavor-notes.index')->with('status', "已新增風味標籤「{$validated['name']}」。");
    }

    public function update(Request $request, FlavorNote $flavorNote): RedirectResponse
    {
        $validated = $request->validateWithBag("flavorNote{$flavorNote->id}", $this->rules($flavorNote));

        $flavorNote->update($validated);

        return redirect()->route('admin.flavor-notes.index')->with('status', "已儲存風味標籤「{$validated['name']}」。");
    }

    public function destroy(FlavorNote $flavorNote): RedirectResponse
    {
        $flavorNote->delete();

        return redirect()->route('admin.flavor-notes.index')->with('status', "已刪除風味標籤「{$flavorNote->name}」。");
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(?FlavorNote $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:20', Rule::unique(FlavorNote::class)->ignore($ignore)],
        ];
    }
}

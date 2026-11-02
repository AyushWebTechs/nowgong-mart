<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::with('parent')->withCount('products')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true]), 'parents' => $this->parents()]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category($this->payload($request));
        $category->save();

        return redirect()->route('admin.categories.index')->with('success', "Category {$category->name} created.");
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', ['category' => $category, 'parents' => $this->parents($category)]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($this->payload($request, $category));

        return redirect()->route('admin.categories.index')->with('success', "Category {$category->name} updated.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->with('error', 'This category has products or sub-categories. Deactivate it instead.');
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function payload(CategoryRequest $request, ?Category $category = null): array
    {
        $data = $request->safe()->except('image');
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($category?->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        return $data;
    }

    private function parents(?Category $except = null)
    {
        return Category::whereNull('parent_id')->when($except, fn ($q) => $q->whereKeyNot($except->id))->ordered()->get();
    }
}

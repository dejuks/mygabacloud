<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $parentOptions = Category::whereNull('parent_id')->orderBy('sort_order')->get();

        return view('admin.categories', compact('categories', 'parentOptions'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request);
        $data['is_active'] = $request->boolean('is_active', true);

        Category::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        return back()->with('success', "Category \"{$data['name']}\" created.");
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateCategory($request, $category);
        $data['is_active'] = $request->boolean('is_active');

        // Only regenerate the slug if the name actually changed — a stable
        // slug matters because it's already baked into every product URL
        // filter link for this category (?category=slug).
        if ($data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        $category->update($data);

        return back()->with('success', "Category \"{$category->name}\" updated.");
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->with('error',
                'Cannot delete a category that still has products or subcategories in it. '
                . 'Move or delete those first, or just toggle it inactive instead.'
            );
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    protected function validateCategory(Request $request, ?Category $category = null): array
    {
        $parentRules = ['nullable', Rule::exists('categories', 'id')->whereNull('parent_id')];

        if ($category) {
            $parentRules[] = Rule::notIn([$category->id]); // can't be its own parent
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => $parentRules, // only one level of nesting allowed
            'icon' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (
            Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()
        ) {
            $slug = "{$base}-" . ++$i;
        }

        return $slug;
    }
}

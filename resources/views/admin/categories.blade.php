@extends('layouts.admin')
@section('title', 'Categories')

@section('content')

<div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <h2 class="font-bold mb-4">Add a category</h2>
    <form method="POST" action="{{ route('admin.categories.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-xs font-medium mb-1">Name</label>
            <input type="text" name="name" required placeholder="e.g. Bidding Document Templates"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1">Parent (optional)</label>
            <select name="parent_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <option value="">None — top level</option>
                @foreach($parentOptions as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium mb-1">Icon/emoji (optional)</label>
            <input type="text" name="icon" maxlength="10" placeholder="📄"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <button class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                Add category
            </button>
        </div>
    </form>
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="font-bold mb-4">All categories</h2>

    @forelse($categories as $category)
        <div x-data="{ editing: false }" class="border-b border-slate-100 py-4 last:border-0">
            <div class="flex items-center justify-between gap-4" x-show="!editing">
                <div>
                    <span class="font-medium">{{ $category->icon }} {{ $category->name }}</span>
                    <span class="text-xs text-slate-400 ml-2">{{ $category->products_count }} products</span>
                    @unless($category->is_active)
                        <span class="text-xs px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 ml-1">Inactive</span>
                    @endunless
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <button @click="editing = true" class="text-xs text-indigo-600 hover:underline">Edit</button>
                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                          onsubmit="return confirm('Delete this category? Only works if it has no products or subcategories.')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-600 hover:underline">Delete</button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.categories.update', $category) }}"
                  x-show="editing" x-cloak class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end mt-2">
                @csrf @method('PUT')
                <div class="md:col-span-2">
                    <input type="text" name="name" value="{{ $category->name }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <input type="text" name="icon" value="{{ $category->icon }}" maxlength="10" placeholder="Icon"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" placeholder="Sort"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($category->is_active)>
                    Active
                </label>
                <div class="flex gap-2">
                    <button class="flex-1 bg-indigo-600 text-white py-2 rounded-lg text-xs font-medium hover:bg-indigo-700">Save</button>
                    <button type="button" @click="editing = false" class="px-3 border border-slate-300 rounded-lg text-xs hover:bg-slate-50">Cancel</button>
                </div>
            </form>

            @if($category->children->isNotEmpty())
                <div class="ml-6 mt-2 space-y-2">
                    @foreach($category->children as $child)
                        <div x-data="{ editing: false }" class="pl-3 border-l-2 border-slate-100">
                            <div class="flex items-center justify-between gap-4" x-show="!editing">
                                <div>
                                    <span class="text-sm">{{ $child->icon }} {{ $child->name }}</span>
                                    <span class="text-xs text-slate-400 ml-2">{{ $child->products()->count() }} products</span>
                                    @unless($child->is_active)
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 ml-1">Inactive</span>
                                    @endunless
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <button @click="editing = true" class="text-xs text-indigo-600 hover:underline">Edit</button>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $child) }}"
                                          onsubmit="return confirm('Delete this subcategory?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-600 hover:underline">Delete</button>
                                    </form>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.categories.update', $child) }}"
                                  x-show="editing" x-cloak class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end mt-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="parent_id" value="{{ $category->id }}">
                                <div class="md:col-span-2">
                                    <input type="text" name="name" value="{{ $child->name }}" required
                                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <input type="text" name="icon" value="{{ $child->icon }}" maxlength="10" placeholder="Icon"
                                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                                </div>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="is_active" value="1" @checked($child->is_active)>
                                    Active
                                </label>
                                <div class="flex gap-2 md:col-span-2">
                                    <button class="flex-1 bg-indigo-600 text-white py-2 rounded-lg text-xs font-medium hover:bg-indigo-700">Save</button>
                                    <button type="button" @click="editing = false" class="px-3 border border-slate-300 rounded-lg text-xs hover:bg-slate-50">Cancel</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">No categories yet — add one above.</p>
    @endforelse
</div>
@endsection

@csrf
@if(isset($isEdit) && $isEdit) @method('PUT') @endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title', $product->title) }}" required maxlength="150"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Short description</label>
                <input type="text" name="short_description" value="{{ old('short_description', $product->short_description) }}"
                       required maxlength="300"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-slate-400 mt-1">One line shown in search results.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Full description</label>
                <textarea name="description" rows="10" required minlength="100"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('description', $product->description) }}</textarea>
                <p class="text-xs text-slate-400 mt-1">Features, requirements, installation steps. Minimum 100 characters.</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
            <h3 class="font-semibold">Files</h3>

            <div>
                <label class="block text-sm font-medium mb-1">Thumbnail image</label>
                @if($product->thumbnail)
                    <img src="{{ Storage::url($product->thumbnail) }}" class="w-40 h-24 object-cover rounded-lg mb-2">
                @endif
                <input type="file" name="thumbnail" accept="image/*" class="text-sm">
                <p class="text-xs text-slate-400 mt-1">Max 2MB. Shown on cards and search results.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Preview images (up to 6)</label>
                @if($product->exists && $product->images->isNotEmpty())
                    <div class="flex gap-2 mb-2 flex-wrap">
                        @foreach($product->images as $image)
                            <div class="relative group">
                                <img src="{{ Storage::url($image->path) }}" class="w-24 h-16 object-cover rounded-lg">
                                @if($image->is_primary)
                                    <span class="absolute bottom-0 left-0 bg-indigo-600 text-white text-[10px] px-1.5 py-0.5 rounded-tr-lg rounded-bl-lg">Primary</span>
                                @endif
                                <button type="button"
                                        title="Remove image"
                                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-600 text-white rounded-full text-xs leading-none flex items-center justify-center hover:bg-red-700"
                                        onclick="if(confirm('Remove this image?')) {
                                            fetch('{{ route('seller.products.images.destroy', [$product, $image]) }}', {
                                                method: 'DELETE',
                                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                            }).then(() => window.location.reload());
                                        }">&times;</button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="images[]" accept="image/*" multiple class="text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Product file (.zip)</label>
                @if($product->exists && $product->files->isNotEmpty())
                    <div class="text-xs text-slate-500 mb-2">
                        Current: {{ $product->files->last()->original_name }}
                        ({{ round($product->files->last()->size_bytes / 1048576, 1) }} MB)
                    </div>
                @endif
                <input type="file" name="product_file" accept=".zip,.rar,.pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx" class="text-sm">
                <p class="text-xs text-slate-400 mt-1">
                    Code archives (.zip/.rar), documents (.pdf/.doc/.docx), presentations (.ppt/.pptx),
                    or spreadsheets (.xls/.xlsx) — whatever you're selling. Max 200MB.
                    This is what buyers download. Stored privately &mdash; never publicly accessible.
                </p>
                @if(isset($isEdit) && $isEdit && $product->status === 'approved')
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1.5 mt-2">
                        ⚠️ Leave this empty to keep your current file. Uploading a new one takes this
                        listing offline until an admin re-reviews it — thumbnail, images, price, and
                        description changes never do this, only the file itself.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="lg:col-span-1 space-y-5">
        <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <select name="category_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Choose...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Regular price ($)</label>
                <input type="number" step="0.01" min="1" name="regular_price"
                       value="{{ old('regular_price', $product->regular_price) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Extended price ($, optional)</label>
                <input type="number" step="0.01" name="extended_price"
                       value="{{ old('extended_price', $product->extended_price) }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-slate-400 mt-1">Must be equal to or higher than the regular price.</p>
            </div>

            <div class="border border-rose-200 bg-rose-50 rounded-lg p-3 space-y-3">
                <p class="text-sm font-medium text-rose-900">Run a deal (optional)</p>
                <p class="text-xs text-rose-800">
                    When set, buyers are actually charged this price for the regular license — not just
                    a cosmetic badge. Leave blank to sell at the regular price.
                </p>
                <div>
                    <label class="block text-xs font-medium mb-1">Sale price ($)</label>
                    <input type="number" step="0.01" min="0" name="sale_price"
                           value="{{ old('sale_price', $product->sale_price) }}"
                           placeholder="Must be less than the regular price"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Deal ends (optional)</label>
                    <input type="datetime-local" name="sale_ends_at"
                           value="{{ old('sale_ends_at', $product->sale_ends_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <p class="text-xs text-rose-700 mt-1">Leave blank for a deal with no set end date.</p>
                </div>
            </div>

            @if(auth()->user()->is_admin)
                <div class="border border-amber-300 bg-amber-50 rounded-lg p-3">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))>
                        <span class="font-medium">★ Feature on homepage</span>
                    </label>
                    <p class="text-xs text-amber-800 mt-1">Admin-only. Featured products appear in a curated section on the homepage.</p>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium mb-1">Format / framework</label>
                <input type="text" name="framework" value="{{ old('framework', $product->framework) }}"
                       placeholder="Laravel, WordPress, PDF, PowerPoint, Word, Excel..."
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Version</label>
                <input type="text" name="current_version" value="{{ old('current_version', $product->current_version ?? '1.0.0') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Compatible with</label>
                <input type="text" name="compatible_with"
                       value="{{ old('compatible_with', $product->compatible_with ? implode(', ', $product->compatible_with) : '') }}"
                       placeholder="PHP 8.2, MySQL 8"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-slate-400 mt-1">Comma separated.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tags</label>
                <input type="text" name="tags"
                       value="{{ old('tags', $product->exists ? $product->tags->pluck('name')->implode(', ') : '') }}"
                       placeholder="ecommerce, admin, dashboard"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Demo URL</label>
                <input type="url" name="demo_url" value="{{ old('demo_url', $product->demo_url) }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="border border-amber-200 bg-amber-50 rounded-lg p-3" x-data="{ freeUnlock: {{ old('allow_free_unlock', $product->allow_free_unlock) ? 'true' : 'false' }} }">
                <label class="flex items-start gap-2 text-sm cursor-pointer">
                    <input type="checkbox" name="allow_free_unlock" value="1" class="mt-1" x-model="freeUnlock"
                           @checked(old('allow_free_unlock', $product->allow_free_unlock))>
                    <span>
                        <span class="font-medium">Allow free access via YouTube engagement</span>
                        <p class="text-xs text-amber-800 mt-1">
                            Buyers can request free access by watching/subscribing/liking/commenting on a
                            video you choose, instead of paying. You earn nothing from these downloads —
                            it's a promotional giveaway, and every request still needs your marketplace's
                            admin to manually approve it before access is granted.
                        </p>
                    </span>
                </label>

                <div x-show="freeUnlock" x-cloak class="mt-3 pl-6 space-y-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Video buyers must watch</label>
                        <input type="url" name="youtube_video_url" x-bind:required="freeUnlock"
                               value="{{ old('youtube_video_url', $product->youtube_video_url) }}"
                               placeholder="https://youtube.com/watch?v=..."
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <p class="text-xs text-slate-400 mt-1">Embedded on the free-unlock request page.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Channel buyers must subscribe to</label>
                        <input type="url" name="youtube_channel_url"
                               value="{{ old('youtube_channel_url', $product->youtube_channel_url) }}"
                               placeholder="https://youtube.com/@yourchannel"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Required watch time before they can proceed (minutes)</label>
                        <input type="number" name="required_watch_minutes" min="0" max="120" step="0.5"
                               value="{{ old('required_watch_minutes', $product->required_watch_seconds ? round($product->required_watch_seconds / 60, 1) : 4) }}"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <p class="text-xs text-slate-400 mt-1">
                            The subscribe/like/comment/share steps stay locked until the buyer has actually
                            watched this much of the video (tracked via YouTube's player, not just a page timer).
                            Set to 0 to skip this and let them proceed immediately.
                        </p>
                    </div>
                </div>
            </div>

            @if(isset($isEdit) && $isEdit)
            <div>
                <label class="block text-sm font-medium mb-1">Changelog note (optional)</label>
                <textarea name="changelog_notes" rows="3" placeholder="What changed in this version?"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            @endif

            <button class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                {{ isset($isEdit) && $isEdit ? 'Save changes' : 'Create product' }}
            </button>
        </div>
    </div>
</div>

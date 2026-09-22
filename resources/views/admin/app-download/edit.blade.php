{{--
    Admin page for the site-wide "Download the App" section.
    ASSUMPTION: your admin panel has a Blade layout called "layouts.admin"
    with a @section('content') slot. Rename @extends below if yours differs.
--}}
@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <h1 class="text-2xl font-semibold mb-1">Mobile App Download</h1>
    <p class="text-gray-500 mb-6">
        Controls the "Download the App" button shown in the site footer (and anywhere else
        <code>@include('partials.download-app-badge')</code> is placed).
    </p>

    @if (session('status'))
        <div class="mb-6 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.app-download.update') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Master switch --}}
        <section class="border rounded-lg p-5">
            <label class="flex items-center gap-3">
                <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $settings->is_enabled))>
                <span class="font-medium">Show the download section on the website</span>
            </label>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-1">Heading</label>
                    <input type="text" name="heading" maxlength="120" required
                           value="{{ old('heading', $settings->heading) }}"
                           class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Subheading (optional)</label>
                    <input type="text" name="subheading" maxlength="255"
                           value="{{ old('subheading', $settings->subheading) }}"
                           class="w-full rounded-md border-gray-300">
                </div>
            </div>
        </section>

        {{-- Android --}}
        <section class="border rounded-lg p-5">
            <label class="flex items-center gap-3 mb-4">
                <input type="checkbox" name="android_enabled" value="1" @checked(old('android_enabled', $settings->android_enabled))>
                <span class="font-medium">Android</span>
            </label>

            <label class="block text-sm font-medium mb-1">Where should the Android button link to?</label>
            <div class="space-y-2 mb-4">
                @foreach ([
                    'apk' => 'Upload an APK file to this website',
                    'play_store' => 'Google Play Store link',
                    'amazon' => 'Amazon Appstore link',
                    'custom_url' => 'Other URL',
                ] as $value => $label)
                    <label class="flex items-center gap-2">
                        <input type="radio" name="android_source" value="{{ $value }}"
                               @checked(old('android_source', $settings->android_source) === $value)>
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-2 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Version name (optional, e.g. 1.2.0)</label>
                    <input type="text" name="android_version_name" maxlength="30"
                           value="{{ old('android_version_name', $settings->android_version_name) }}"
                           class="w-full rounded-md border-gray-300">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">APK file (used when "Upload an APK" is selected, max 500&nbsp;MB)</label>
                @if ($settings->android_apk_path)
                    <p class="text-sm text-gray-600 mb-2">
                        Current file: {{ $settings->android_apk_original_name }}
                        ({{ $settings->androidApkSizeLabel() }}) &middot;
                        {{ number_format($settings->android_download_count) }} downloads
                        <label class="ml-3 text-red-600">
                            <input type="checkbox" name="remove_apk" value="1"> Remove it
                        </label>
                    </p>
                @endif
                <input type="file" name="android_apk" accept=".apk" class="w-full">
            </div>

            <div class="grid gap-4 sm:grid-cols-1">
                <div>
                    <label class="block text-sm font-medium mb-1">Google Play Store URL</label>
                    <input type="url" name="android_play_store_url" maxlength="500"
                           placeholder="https://play.google.com/store/apps/details?id=com.gabacloud.app"
                           value="{{ old('android_play_store_url', $settings->android_play_store_url) }}"
                           class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Amazon Appstore URL</label>
                    <input type="url" name="android_amazon_url" maxlength="500"
                           placeholder="https://www.amazon.com/dp/XXXXXXXXXX"
                           value="{{ old('android_amazon_url', $settings->android_amazon_url) }}"
                           class="w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Other URL</label>
                    <input type="url" name="android_custom_url" maxlength="500"
                           value="{{ old('android_custom_url', $settings->android_custom_url) }}"
                           class="w-full rounded-md border-gray-300">
                </div>
            </div>
        </section>

        {{-- iOS --}}
        <section class="border rounded-lg p-5">
            <label class="flex items-center gap-3 mb-4">
                <input type="checkbox" name="ios_enabled" value="1" @checked(old('ios_enabled', $settings->ios_enabled))>
                <span class="font-medium">iOS (App Store link only — Apple does not allow direct installs)</span>
            </label>
            <label class="block text-sm font-medium mb-1">Apple App Store URL</label>
            <input type="url" name="ios_app_store_url" maxlength="500"
                   value="{{ old('ios_app_store_url', $settings->ios_app_store_url) }}"
                   class="w-full rounded-md border-gray-300">
        </section>

        <button type="submit" class="px-5 py-2.5 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700">
            Save changes
        </button>
    </form>
</div>
@endsection

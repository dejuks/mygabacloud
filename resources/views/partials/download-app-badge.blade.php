{{--
    "Download the App" section. Content and links come from the admin page
    (Website > Mobile App Download), so nothing here needs editing.

    Usage — drop this one line into the footer, or any other Blade view:
        @include('partials.download-app-badge')

    Optional: pass a compact style for use outside the footer, e.g. a
    homepage banner:
        @include('partials.download-app-badge', ['style' => 'compact'])
--}}
@php
    $appDownload = \App\Models\AppDownloadSetting::current();
    $style = $style ?? 'full';
@endphp

@if ($appDownload->hasAnyLink())
    <div class="gaba-app-download gaba-app-download--{{ $style }}">
        <div class="gaba-app-download__text">
            <p class="gaba-app-download__heading">{{ $appDownload->heading }}</p>
            @if ($appDownload->subheading)
                <p class="gaba-app-download__subheading">{{ $appDownload->subheading }}</p>
            @endif
        </div>

        <div class="gaba-app-download__buttons">
            @if ($appDownload->androidLink())
                <a href="{{ $appDownload->androidLink() }}"
                   class="gaba-app-download__btn gaba-app-download__btn--android"
                   @if($appDownload->android_source === 'apk') rel="nofollow" @endif
                   target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">
                        <path d="M17.6 9.48l1.84-3.18a.5.5 0 0 0-.87-.5l-1.86 3.22a11.5 11.5 0 0 0-9.42 0L5.43 5.8a.5.5 0 1 0-.87.5L6.4 9.48A9.9 9.9 0 0 0 2 17.5h20a9.9 9.9 0 0 0-4.4-8.02zM7.5 14.75a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5zm9 0a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5z"/>
                    </svg>
                    <span>{{ $appDownload->androidButtonLabel() }}</span>
                </a>
            @endif

            @if ($appDownload->iosLink())
                <a href="{{ $appDownload->iosLink() }}" class="gaba-app-download__btn gaba-app-download__btn--ios"
                   target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">
                        <path d="M16.37 1c.1 1.08-.32 2.13-.98 2.9-.68.8-1.79 1.42-2.87 1.34-.13-1.05.38-2.15 1-2.85C14.2 1.55 15.36 1 16.37 1zM20.6 17.3c-.53 1.2-.78 1.74-1.47 2.8-.96 1.48-2.32 3.33-4 3.35-1.5.02-1.88-.98-3.9-.97-2.02.01-2.45.99-3.95.97-1.68-.02-2.97-1.68-3.93-3.15C.9 17.36.28 13.26 1.9 10.5c1.13-1.93 3.02-3.16 5.06-3.19 1.6-.03 2.7 1.05 3.9 1.05 1.19 0 2.05-1.06 3.9-.9 1.5.12 3.02.9 3.98 2.28-3.5 1.9-2.94 6.56 1.86 7.56z"/>
                    </svg>
                    <span>Download on the App Store</span>
                </a>
            @endif
        </div>
    </div>
@endif

@once
    <style>
        .gaba-app-download { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; padding: 20px; }
        .gaba-app-download--full { border-top: 1px solid #e5e7eb; }
        .gaba-app-download--compact { padding: 14px 16px; border-radius: 12px; background: #f1f5f9; }
        .gaba-app-download__heading { font-weight: 600; margin: 0; }
        .gaba-app-download__subheading { margin: 2px 0 0; font-size: 0.875rem; color: #64748b; }
        .gaba-app-download__buttons { display: flex; flex-wrap: wrap; gap: 10px; }
        .gaba-app-download__btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 16px; border-radius: 8px; font-size: 0.9rem; font-weight: 500;
            text-decoration: none; color: #fff; background: #111827; white-space: nowrap;
        }
        .gaba-app-download__btn:hover { opacity: 0.9; }
        .gaba-app-download__btn--android { background: #1a56db; }
        .gaba-app-download__btn--ios { background: #111827; }
    </style>
@endonce

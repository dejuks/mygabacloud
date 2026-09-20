@extends('layouts.app')
@section('title', 'Get ' . $product->title . ' for free')

@section('content')
@php
    $videoId = $product->youtubeVideoId();
    // If the URL didn't parse into an embeddable ID, there's nothing to
    // track — don't gate on watch time we can't measure.
    $requiredSeconds = $videoId ? (int) $product->required_watch_seconds : 0;
@endphp

<div class="max-w-2xl mx-auto"
     x-data="{
        requiredSeconds: {{ $requiredSeconds }},
        watchedSeconds: 0,
        timer: null,
        watched: {{ $requiredSeconds > 0 ? 'false' : 'true' }},
        subscribed: false, liked: false, commented: false, shared: false,
        get unlocked() { return this.requiredSeconds <= 0 || this.watchedSeconds >= this.requiredSeconds },
        get progressPct() { return this.requiredSeconds > 0 ? Math.min(100, Math.round(this.watchedSeconds / this.requiredSeconds * 100)) : 100 },
        get allDone() { return this.watched && this.subscribed && this.liked && this.commented && this.shared },
        formatTime(s) {
            s = Math.max(0, Math.round(s));
            return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        },
        startTimer() {
            if (this.timer) return;
            this.timer = setInterval(() => {
                if (this.watchedSeconds < this.requiredSeconds) {
                    this.watchedSeconds++;
                    if (this.watchedSeconds >= this.requiredSeconds) this.watched = true;
                }
            }, 1000);
        },
        stopTimer() {
            clearInterval(this.timer);
            this.timer = null;
        },
        initPlayer() {
            if (this.requiredSeconds <= 0) return;
            const create = () => {
                new YT.Player('yt-player-{{ $product->id }}', {
                    videoId: '{{ $videoId }}',
                    events: {
                        onStateChange: (e) => {
                            if (e.data === YT.PlayerState.PLAYING) this.startTimer();
                            else this.stopTimer();
                        }
                    }
                });
            };
            if (window.YT && window.YT.Player) {
                create();
            } else {
                window.addEventListener('youtube-api-ready', create, { once: true });
            }
        }
     }"
     x-init="initPlayer()">

    <a href="{{ route('products.show', $product) }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to product</a>

    <div class="bg-white border border-slate-200 rounded-xl p-6 mt-3">
        <h1 class="text-xl font-bold mb-1">Get "{{ $product->title }}" for free</h1>
        <p class="text-sm text-slate-500 mb-6">
            @if($requiredSeconds > 0)
                Watch at least <strong>{{ gmdate('i:s', $requiredSeconds) }}</strong> of the video below, then
                complete the rest of the steps. An admin still reviews every request by hand before approving.
            @else
                Complete every step below, then confirm what you did. An admin reviews each request by hand.
            @endif
        </p>

        @if($videoId)
            <div class="rounded-lg overflow-hidden mb-3 aspect-video bg-black">
                <div id="yt-player-{{ $product->id }}" class="w-full h-full"></div>
            </div>

            @if($requiredSeconds > 0)
                <div class="mb-4">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span :class="unlocked ? 'text-green-600 font-medium' : 'text-slate-500'">
                            <span x-show="!unlocked">Watched <span x-text="formatTime(watchedSeconds)"></span> of <span x-text="formatTime(requiredSeconds)"></span></span>
                            <span x-show="unlocked">✓ Watch time complete — you can continue below</span>
                        </span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 transition-all duration-1000" :style="`width: ${progressPct}%`"></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">
                        This is tracked from actual play time, not just having the page open — press play and let it run.
                    </p>
                </div>
            @endif
        @endif

        <div class="flex flex-wrap gap-2 mb-6">
            @if($product->youtube_video_url)
                <a href="{{ $product->youtube_video_url }}" target="_blank" rel="noopener noreferrer"
                   class="text-xs bg-red-50 text-red-700 border border-red-200 px-3 py-1.5 rounded-full hover:bg-red-100">
                    ▶ Open on YouTube
                </a>
            @endif
            @if($product->youtube_channel_url)
                <a href="{{ $product->youtube_channel_url }}" target="_blank" rel="noopener noreferrer"
                   class="text-xs bg-red-50 text-red-700 border border-red-200 px-3 py-1.5 rounded-full hover:bg-red-100">
                    🔔 Open channel to subscribe
                </a>
            @endif
        </div>

        @if($existing && $existing->status === 'pending')
            <div class="px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
                You already have a request pending review, submitted
                {{ $existing->created_at->diffForHumans() }}. We'll email you once it's checked.
            </div>
        @elseif($existing && $existing->status === 'rejected')
            <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm mb-4">
                Your previous request wasn't approved{{ $existing->admin_note ? ': ' . $existing->admin_note : '.' }}
                You're welcome to try again below.
            </div>
        @endif

        @if(! $existing || $existing->status === 'rejected')
            <form method="POST" action="{{ route('unlock.store', $product) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="watched_seconds" :value="Math.round(watchedSeconds)">

                <div class="border border-slate-200 rounded-lg divide-y divide-slate-100" :class="!unlocked && 'opacity-50 pointer-events-none'">
                    <template x-if="requiredSeconds > 0">
                        <div class="flex items-center gap-3 px-4 py-3 bg-slate-50">
                            <span x-show="watched" class="text-green-600">✓</span>
                            <span x-show="!watched">▶️</span>
                            <span class="text-sm" x-text="watched ? 'Watch time confirmed' : 'Keep watching...'"></span>
                        </div>
                    </template>
                    <input type="hidden" name="watched" :value="watched ? 1 : 0">

                    @foreach([
                        ['subscribed', '🔔', 'I subscribed to the channel'],
                        ['liked', '👍', 'I liked the video'],
                        ['commented', '💬', 'I left a genuine comment'],
                        ['shared', '🔁', 'I shared the video (repost, story, or sent to a friend)'],
                    ] as [$field, $emoji, $label])
                        <label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50">
                            <input type="checkbox" name="{{ $field }}" value="1" x-model="{{ $field }}"
                                   :disabled="!unlocked" class="w-4 h-4">
                            <span>{{ $emoji }}</span>
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div :class="!unlocked && 'opacity-50 pointer-events-none'">
                    <div class="space-y-4" :inert="!unlocked">
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                            <label class="block text-sm font-medium mb-1">Screenshot showing your subscribe, like, and comment</label>
                            <input type="file" name="proof_screenshot" accept="image/*" required
                                   class="w-full text-sm">
                            <p class="text-xs text-amber-800 mt-1">
                                One screenshot that shows all three — e.g. your comment on the video with the
                                "Subscribed" and liked-thumb states visible. This is what actually gets reviewed;
                                the checklist above is just your own confirmation.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Your YouTube name/handle</label>
                            <input type="text" name="youtube_handle" value="{{ old('youtube_handle') }}" required
                                   placeholder="@yourhandle"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            <p class="text-xs text-slate-400 mt-1">So we can find your comment/subscription to verify.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Anything else? (what your comment said, etc.)</label>
                            <textarea name="proof_note" rows="3" required minlength="10" maxlength="1000"
                                      placeholder="e.g. Commented 'Great starter kit, exactly what I needed!'"
                                      class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('proof_note') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Link to your comment (optional, speeds up approval)</label>
                            <input type="url" name="proof_url" value="{{ old('proof_url') }}"
                                   placeholder="https://youtube.com/watch?v=...&lc=..."
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="!allDone"
                        :class="allDone ? 'bg-green-600 hover:bg-green-700' : 'bg-slate-300 cursor-not-allowed'"
                        class="w-full text-white py-3 rounded-lg font-medium transition">
                    <template x-if="!unlocked">
                        <span>Finish watching to continue</span>
                    </template>
                    <template x-if="unlocked && !allDone">
                        <span>Complete all steps above first</span>
                    </template>
                    <template x-if="allDone">
                        <span>Submit for review</span>
                    </template>
                </button>
            </form>
        @endif
    </div>
</div>

@if($videoId && $requiredSeconds > 0)
    @push('scripts')
    <script>
        if (!window.__youtubeApiLoading) {
            window.__youtubeApiLoading = true;
            window.onYouTubeIframeAPIReady = function () {
                window.dispatchEvent(new Event('youtube-api-ready'));
            };
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        }
    </script>
    @endpush
@endif
@endsection

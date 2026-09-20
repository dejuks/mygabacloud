@extends('layouts.admin')
@section('title', 'Free unlocks')

@section('content')

<div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <h2 class="font-bold mb-1">Pending requests ({{ $pending->total() }})</h2>
    <p class="text-xs text-slate-500 mb-4">
        Free access via YouTube engagement. There's no API to verify a subscribe or a like —
        use your judgment on whether the description looks genuine.
    </p>

    @forelse($pending as $req)
        <div class="border-b border-slate-100 py-4 last:border-0">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex gap-4">
                    @if($req->proof_screenshot_path)
                        <div class="shrink-0">
                            <a href="{{ route('admin.engagement.proof', $req) }}" target="_blank"
                               class="block w-24 h-24 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 hover:border-indigo-400">
                                <img src="{{ route('admin.engagement.proof', $req) }}" class="w-full h-full object-cover" alt="Engagement proof screenshot">
                            </a>
                            <p class="text-[10px] text-slate-400 mt-1 w-24 truncate" title="{{ $req->proof_screenshot_original_name }}">
                                {{ $req->proof_screenshot_original_name ?? 'screenshot' }}
                            </p>
                        </div>
                    @endif
                    <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Free unlock</span>
                        <span class="font-semibold">{{ $req->product->title }}</span>
                    </div>
                    <p class="text-sm">{{ $req->user->name }} ({{ $req->user->email }})</p>
                    <p class="text-xs text-slate-500 mt-1">YouTube handle: <span class="font-mono">{{ $req->youtube_handle }}</span></p>
                    @if($req->watched_seconds !== null && $req->product->required_watch_seconds > 0)
                        <p class="text-xs text-slate-400">
                            Reported watch time: {{ gmdate('i:s', $req->watched_seconds) }}
                            of {{ gmdate('i:s', $req->product->required_watch_seconds) }} required
                            <span class="italic">(self-reported by the browser, not server-verified)</span>
                        </p>
                    @endif

                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach([
                            ['watched', '▶️ Watched'],
                            ['subscribed', '🔔 Subscribed'],
                            ['liked', '👍 Liked'],
                            ['commented', '💬 Commented'],
                            ['shared', '🔁 Shared'],
                        ] as [$field, $label])
                            <span class="text-xs px-2 py-1 rounded-full {{ $req->$field ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-slate-50 text-slate-400 border border-slate-200' }}">
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>

                    <p class="text-sm text-slate-600 mt-2 max-w-xl">{{ $req->proof_note }}</p>
                    @if($req->proof_url)
                        <a href="{{ $req->proof_url }}" target="_blank" rel="noopener noreferrer"
                           class="text-xs text-indigo-600 hover:underline mt-1 inline-block">{{ $req->proof_url }}</a>
                    @endif

                    <div class="flex gap-2 mt-2">
                        @if($req->product->youtube_video_url)
                            <a href="{{ $req->product->youtube_video_url }}" target="_blank" rel="noopener noreferrer"
                               class="text-xs text-red-600 hover:underline">▶ Open product video</a>
                        @endif
                        @if($req->product->youtube_channel_url)
                            <a href="{{ $req->product->youtube_channel_url }}" target="_blank" rel="noopener noreferrer"
                               class="text-xs text-red-600 hover:underline">🔔 Open channel</a>
                        @endif
                    </div>

                    <p class="text-xs text-slate-400 mt-1">Submitted {{ $req->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div class="shrink-0 flex flex-col gap-2 w-44">
                    <form method="POST" action="{{ route('admin.engagement.approve', $req) }}"
                          onsubmit="return confirm('Approve free access?\n\nProduct: {{ addslashes($req->product->title) }}\nBuyer: {{ addslashes($req->user->name) }} ({{ addslashes($req->user->email) }})\n\nThis issues a real license immediately — no undo.')">
                        @csrf
                        <button class="w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                            Approve — free access
                        </button>
                    </form>
                    <details>
                        <summary class="text-center border border-red-300 text-red-600 py-2 rounded-lg text-sm cursor-pointer hover:bg-red-50">
                            Reject
                        </summary>
                        <form method="POST" action="{{ route('admin.engagement.reject', $req) }}" class="mt-2 space-y-1">
                            @csrf
                            <textarea name="admin_note" rows="2" required placeholder="Reason (shown to buyer)"
                                      class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                            <button class="w-full bg-red-600 text-white py-1.5 rounded-lg text-xs hover:bg-red-700">Confirm reject</button>
                        </form>
                    </details>
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">Nothing waiting on review.</p>
    @endforelse

    <div class="mt-4">{{ $pending->links() }}</div>
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="font-bold mb-4">History</h2>
    @forelse($history as $req)
        <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0 text-sm">
            <div class="flex items-center gap-3">
                @if($req->proof_screenshot_path)
                    <a href="{{ route('admin.engagement.proof', $req) }}" target="_blank"
                       class="w-10 h-10 bg-slate-100 rounded overflow-hidden shrink-0 border border-slate-200 hover:border-indigo-400">
                        <img src="{{ route('admin.engagement.proof', $req) }}" class="w-full h-full object-cover" alt="Proof screenshot">
                    </a>
                @endif
                <div>
                    <span class="font-medium">{{ $req->product->title }}</span>
                    <span class="text-xs text-slate-400 ml-2">
                        {{ $req->user->name }} &middot; {{ $req->reviewed_at?->format('M j, Y') }}
                    </span>
                    @if($req->admin_note && $req->status !== 'approved')
                        <p class="text-xs text-slate-500 mt-0.5">{{ $req->admin_note }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs px-2 py-1 rounded-full {{ match($req->status) {
                    'approved' => 'bg-green-100 text-green-700',
                    'revoked' => 'bg-slate-200 text-slate-600',
                    default => 'bg-red-100 text-red-700',
                } }}">
                    {{ ucfirst($req->status) }}
                </span>
                @if($req->status === 'approved')
                    <details class="relative">
                        <summary class="text-xs text-red-600 hover:underline cursor-pointer list-none">Revoke</summary>
                        <form method="POST" action="{{ route('admin.engagement.revoke', $req) }}"
                              class="absolute right-0 mt-1 z-10 w-64 bg-white border border-slate-200 rounded-lg shadow-lg p-3 space-y-2"
                              onsubmit="return confirm('Revoke free access for {{ addslashes($req->user->name) }} on \'{{ addslashes($req->product->title) }}\'? Their license stops working immediately.')">
                            @csrf
                            <textarea name="admin_note" rows="2" required placeholder="Reason for revoking"
                                      class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                            <button class="w-full bg-red-600 text-white py-1.5 rounded-lg text-xs hover:bg-red-700">Confirm revoke</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">No history yet.</p>
    @endforelse
</div>
@endsection

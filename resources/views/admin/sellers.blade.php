@extends('layouts.admin')
@section('title', 'Sellers')

@section('content')
<div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <h2 class="font-bold mb-4">Pending applications ({{ $applications->total() }})</h2>

    @forelse($applications as $application)
        <div class="border-b border-slate-100 py-4 last:border-0">
            <div class="flex justify-between items-start gap-4">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $application->store_name }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $application->user->name }} &middot; {{ $application->user->email }}
                        &middot; applied {{ $application->created_at->diffForHumans() }}
                    </p>
                    <p class="text-sm text-slate-600 mt-2">{{ $application->description }}</p>
                    @if($application->website)
                        <a href="{{ $application->website }}" target="_blank" rel="noopener noreferrer"
                           class="text-xs text-indigo-600 hover:underline">{{ $application->website }}</a>
                    @endif
                </div>

                <div class="shrink-0 flex gap-2">
                    <form method="POST" action="{{ route('admin.sellers.approve', $application) }}">
                        @csrf
                        <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.sellers.reject', $application) }}">
                        @csrf
                        <input type="hidden" name="reason" value="Application did not meet our requirements.">
                        <button class="border border-red-300 text-red-600 px-4 py-2 rounded-lg text-sm hover:bg-red-50">Reject</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">No pending applications.</p>
    @endforelse
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="font-bold mb-4">Approved sellers</h2>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-500 border-b">
            <tr>
                <th class="text-left py-2">Store</th>
                <th class="text-left py-2">Status</th>
                <th class="text-left py-2">Sales</th>
                <th class="text-left py-2">Rating</th>
                <th class="text-left py-2">Commission</th>
                <th class="text-right py-2">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($approved as $seller)
            <tr>
                <td class="py-3">
                    <p class="font-medium">{{ $seller->store_name }}</p>
                    <p class="text-xs text-slate-400">{{ $seller->user->email }}</p>
                </td>
                <td class="py-3">
                    <span class="text-xs px-2 py-1 rounded-full {{ $seller->user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ ucfirst($seller->user->status) }}
                    </span>
                </td>
                <td class="py-3">{{ $seller->total_sales }}</td>
                <td class="py-3">{{ $seller->average_rating > 0 ? number_format($seller->average_rating, 1) : '—' }}</td>
                <td class="py-3">
                    <form method="POST" action="{{ route('admin.sellers.commission', $seller) }}" class="flex gap-2 items-center">
                        @csrf
                        <input type="number" step="0.01" name="custom_commission_rate"
                               value="{{ $seller->custom_commission_rate }}"
                               placeholder="{{ \App\Models\PlatformSetting::get('default_commission_rate', 30) }} (default)"
                               class="w-28 border border-slate-300 rounded px-2 py-1 text-xs">
                        <button class="text-xs text-indigo-600 hover:underline">Save</button>
                    </form>
                </td>
                <td class="py-3 text-right">
                    @if($seller->user->status === 'active')
                        <details class="relative inline-block text-left">
                            <summary class="text-xs text-red-600 hover:underline cursor-pointer list-none">Suspend</summary>
                            <form method="POST" action="{{ route('admin.sellers.suspend', $seller) }}"
                                  class="absolute right-0 mt-1 z-10 w-64 bg-white border border-slate-200 rounded-lg shadow-lg p-3 space-y-2"
                                  onsubmit="return confirm('Suspend {{ addslashes($seller->store_name) }}? They will lose access to their seller dashboard immediately. Existing buyers keep their downloads.')">
                                @csrf
                                <textarea name="admin_note" rows="2" required placeholder="Reason for suspending"
                                          class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                                <button class="w-full bg-red-600 text-white py-1.5 rounded-lg text-xs hover:bg-red-700">Confirm suspend</button>
                            </form>
                        </details>
                    @else
                        <form method="POST" action="{{ route('admin.sellers.reactivate', $seller) }}" class="inline">
                            @csrf
                            <button class="text-xs text-green-600 hover:underline">Reactivate</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-3 text-slate-500">No approved sellers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

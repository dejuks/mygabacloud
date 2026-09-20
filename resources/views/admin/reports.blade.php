@extends('layouts.admin')
@section('title', 'Reports')

@section('content')
<div x-data="{ tab: 'overview' }">

    <div class="flex gap-1 mb-6 border-b border-slate-200 overflow-x-auto">
        @foreach([
            ['overview', 'Overview'],
            ['products', 'Products'],
            ['sellers', 'Sellers'],
            ['users', 'Users'],
            ['activity', 'Activity Log'],
        ] as [$key, $label])
            <button @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    class="px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'overview'">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach([
                ['Total products', $productStats['total'], '📦'],
                ['Approved sellers', $sellerStats['total_approved'], '🏪'],
                ['Total users', $userStats['total'], '👥'],
                ['Commission collected', '$' . number_format($totalCommissionCollected, 2), '💰'],
                ['New users (7d)', $userStats['new_last_7_days'], '📈'],
                ['New users (30d)', $userStats['new_last_30_days'], '📈'],
                ['Seller earnings paid', '$' . number_format($totalSellerEarnings, 2), '🏦'],
                ['Deleted products', $productStats['deleted'], '🗑️'],
            ] as [$label, $value, $emoji])
                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <span class="text-lg">{{ $emoji }}</span>
                    <p class="text-2xl font-bold text-slate-900 mt-2">{{ $value }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold mb-4">Registrations, last 30 days</h2>
            @if($registrationTrend->isEmpty())
                <p class="text-sm text-slate-500">No new registrations in this period.</p>
            @else
                @php $max = $registrationTrend->max('count') ?: 1; @endphp
                <div class="flex items-end gap-1 h-32">
                    @foreach($registrationTrend as $day)
                        <div class="flex-1 bg-indigo-500 rounded-t hover:bg-indigo-600 transition"
                             style="height: {{ max(4, ($day->count / $max) * 100) }}%"
                             title="{{ \Carbon\Carbon::parse($day->date)->format('M j') }}: {{ $day->count }}"></div>
                    @endforeach
                </div>
                <p class="text-xs text-slate-400 mt-2">Hover a bar for the exact date and count.</p>
            @endif
        </div>
    </div>

    <div x-show="tab === 'products'" x-cloak class="space-y-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold mb-4">Products by status</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($productStats['by_status'] as $status => $count)
                    <div class="bg-slate-50 rounded-lg p-3">
                        <p class="text-lg font-bold">{{ $count }}</p>
                        <p class="text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $status)) }}</p>
                    </div>
                @endforeach
                <div class="bg-slate-50 rounded-lg p-3">
                    <p class="text-lg font-bold">{{ $productStats['deleted'] }}</p>
                    <p class="text-xs text-slate-500">Deleted</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="font-bold mb-4">Top products by sales count</h2>
                @forelse($topProductsBySales as $product)
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0 text-sm">
                        <span class="truncate">{{ $product->title }}</span>
                        <span class="font-semibold shrink-0 ml-3">{{ $product->sales_count }} sales</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No sales yet.</p>
                @endforelse
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="font-bold mb-4">Top products by revenue</h2>
                @forelse($topProductsByRevenue as $row)
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0 text-sm">
                        <span class="truncate">{{ $row->product->title ?? 'Deleted product' }}</span>
                        <span class="font-semibold shrink-0 ml-3">${{ number_format($row->revenue, 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No revenue yet.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold mb-4">Products per category</h2>
            @forelse($categoryPerformance as $cat)
                <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0 text-sm">
                    <span>{{ $cat->name }}</span>
                    <span class="font-semibold">{{ $cat->products_count }} live products</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No categories yet.</p>
            @endforelse
        </div>
    </div>

    <div x-show="tab === 'sellers'" x-cloak class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @foreach([
                ['Approved sellers', $sellerStats['total_approved']],
                ['Pending applications', $sellerStats['pending_applications']],
                ['Suspended sellers', $sellerStats['suspended']],
            ] as [$label, $value])
                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <p class="text-2xl font-bold">{{ $value }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold mb-4">Top sellers by total earnings</h2>
            @forelse($topSellersByEarnings as $sellerProfile)
                <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0 text-sm">
                    <div>
                        <span class="font-medium">{{ $sellerProfile->store_name }}</span>
                        <span class="text-xs text-slate-400 ml-2">{{ $sellerProfile->total_sales }} sales</span>
                    </div>
                    <span class="font-semibold">${{ number_format($sellerProfile->user->wallet->total_earned ?? 0, 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No seller earnings yet.</p>
            @endforelse
        </div>
    </div>

    <div x-show="tab === 'users'" x-cloak class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @foreach([
                ['Total users', $userStats['total']],
                ['Buyers only', $userStats['buyers_only']],
                ['Sellers', $userStats['sellers']],
                ['Admins', $userStats['admins']],
                ['Suspended', $userStats['suspended']],
                ['New (30 days)', $userStats['new_last_30_days']],
            ] as [$label, $value])
                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <p class="text-2xl font-bold">{{ $value }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div x-show="tab === 'activity'" x-cloak class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium mb-1">Action</label>
                    <select name="log_action" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All actions</option>
                        @foreach($distinctActions as $action)
                            <option value="{{ $action }}" @selected(request('log_action') === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">User</label>
                    <select name="log_user_id" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All users</option>
                        @foreach($logUsers as $logUser)
                            <option value="{{ $logUser->id }}" @selected((int) request('log_user_id') === $logUser->id)>{{ $logUser->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    Filter
                </button>
                @if(request('log_action') || request('log_user_id'))
                    <a href="{{ route('admin.reports.index') }}" class="text-sm text-slate-500 hover:underline">Clear</a>
                @endif
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-5 py-3">When</th>
                        <th class="text-left px-5 py-3">Who</th>
                        <th class="text-left px-5 py-3">Action</th>
                        <th class="text-left px-5 py-3">Description</th>
                        <th class="text-left px-5 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($activityLog as $entry)
                    <tr>
                        <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $entry->created_at->format('M j, g:ia') }}</td>
                        <td class="px-5 py-3">{{ $entry->user->name ?? 'System' }}</td>
                        <td class="px-5 py-3">
                            <span class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-600 font-mono">{{ $entry->action }}</span>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $entry->description }}</td>
                        <td class="px-5 py-3 text-xs text-slate-400">{{ $entry->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No activity recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div>{{ $activityLog->links() }}</div>
    </div>
</div>
@endsection

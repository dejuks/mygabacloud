<div class="flex gap-1 mb-6 border-b border-slate-200 overflow-x-auto">
    @php
        $tabs = [
            'admin.dashboard' => 'Overview',
            'admin.products.pending' => 'Product queue',
            'admin.sellers.index' => 'Sellers',
            'admin.payouts.index' => 'Payouts',
            'admin.settings.index' => 'Settings',
            'admin.app-download.edit' => 'App Download',
        ];
    @endphp
    @foreach($tabs as $route => $label)
        <a href="{{ route($route) }}"
           class="px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap
                  {{ request()->routeIs($route) ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

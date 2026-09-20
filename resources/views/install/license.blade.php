@extends('install.layout')

@section('content')
<h2 class="text-xl font-bold text-slate-800 mb-1">License this installation</h2>
<p class="text-slate-500 text-sm mb-6">
    A license key will be generated and locked to this server. Copying the code to a different
    server later will need a new license — this isn't unbreakable copy protection, it's a deterrent
    against casual re-use, same as every self-hosted script uses.
</p>

<form method="POST" action="{{ route('install.license.store') }}" class="space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium mb-1">Buyer / licensee name (optional)</label>
        <input type="text" name="buyer_name" value="{{ old('buyer_name') }}"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Purchase code (optional)</label>
        <input type="text" name="purchase_code" value="{{ old('purchase_code') }}"
               placeholder="If you have one, for your own records"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div class="flex justify-between pt-4">
        <a href="{{ route('install.admin') }}" class="text-slate-500 px-6 py-3 text-sm font-medium hover:text-slate-700">Back</a>
        <button class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Continue
        </button>
    </div>
</form>
@endsection

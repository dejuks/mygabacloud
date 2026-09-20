@extends('install.layout')

@section('content')
<h2 class="text-xl font-bold text-slate-800 mb-1">Database connection</h2>
<p class="text-slate-500 text-sm mb-6">Enter the credentials for a database that already exists.</p>

<form method="POST" action="{{ route('install.database.store') }}" class="space-y-4"
      x-data="{
          testing: false, tested: false, success: false, message: '',
          async testConnection() {
              this.testing = true; this.tested = false;
              const form = document.getElementById('db-form');
              const data = new FormData(form);
              try {
                  const res = await fetch('{{ route('install.database.test') }}', {
                      method: 'POST',
                      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                      body: data,
                  });
                  const json = await res.json();
                  this.success = json.success;
                  this.message = json.message;
              } catch (e) {
                  this.success = false;
                  this.message = 'Could not reach the server to test this.';
              }
              this.testing = false;
              this.tested = true;
          }
      }" id="db-form">
    @csrf

    <div>
        <label class="block text-sm font-medium mb-1">Database type</label>
        <select name="db_connection" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <option value="mysql" {{ old('db_connection', 'mysql') === 'mysql' ? 'selected' : '' }}>MySQL / MariaDB</option>
            <option value="pgsql" {{ old('db_connection') === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
        </select>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <div class="col-span-2">
            <label class="block text-sm font-medium mb-1">Host</label>
            <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Port</label>
            <input type="text" name="db_port" value="{{ old('db_port', '3306') }}" required
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Database name</label>
        <input type="text" name="db_database" value="{{ old('db_database') }}" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium mb-1">Username</label>
            <input type="text" name="db_username" value="{{ old('db_username') }}" required
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="db_password" value="{{ old('db_password') }}"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <button type="button" @click="testConnection()"
            class="w-full border-2 border-indigo-200 text-indigo-700 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-50">
        <span x-show="!testing">Test connection</span>
        <span x-show="testing">Testing...</span>
    </button>

    <div x-show="tested" x-cloak
         :class="success ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200'"
         class="px-4 py-3 rounded-lg text-sm border" x-text="message"></div>

    <div class="flex justify-between pt-4">
        <a href="{{ route('install.requirements') }}" class="text-slate-500 px-6 py-3 text-sm font-medium hover:text-slate-700">Back</a>
        <button class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Continue
        </button>
    </div>
</form>
@endsection

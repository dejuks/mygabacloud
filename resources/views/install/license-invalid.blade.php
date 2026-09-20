<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>License Invalid</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md text-center bg-white border border-slate-200 rounded-2xl shadow-lg p-8">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-lg font-bold text-slate-800 mb-2">License not valid for this server</h1>
        <p class="text-sm text-slate-500">
            This installation's license key doesn't match the server it's currently running on.
            If you moved this codebase to a new server or changed hosting, you'll need to re-license
            it there. Contact whoever you obtained this from for help.
        </p>
    </div>
</body>
</html>

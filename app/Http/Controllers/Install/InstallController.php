<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Services\EnvironmentWriter;
use App\Services\LicenseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PDO;
use PDOException;

class InstallController extends Controller
{
    protected array $steps = ['welcome', 'requirements', 'database', 'settings', 'admin', 'license', 'finish'];

    public function welcome()
    {
        return view('install.welcome', ['steps' => $this->steps, 'current' => 'welcome']);
    }

    public function requirements()
    {
        $checks = $this->runRequirementChecks();

        return view('install.requirements', [
            'steps' => $this->steps,
            'current' => 'requirements',
            'checks' => $checks,
            'allPassed' => collect($checks)->every(fn ($c) => $c['passed']),
        ]);
    }

    public function database()
    {
        return view('install.database', ['steps' => $this->steps, 'current' => 'database']);
    }

    public function testDatabase(Request $request)
    {
        $data = $request->validate([
            'db_connection' => ['required', 'in:mysql,pgsql'],
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        try {
            $dsn = $data['db_connection'] === 'pgsql'
                ? "pgsql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']}"
                : "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']}";

            new PDO($dsn, $data['db_username'], $data['db_password'] ?? '', [
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return response()->json(['success' => true, 'message' => 'Connected successfully.']);
        } catch (PDOException $e) {
            return response()->json(['success' => false, 'message' => $this->friendlyDbError($e->getMessage())]);
        }
    }

    public function storeDatabase(Request $request, EnvironmentWriter $env)
    {
        $data = $request->validate([
            'db_connection' => ['required', 'in:mysql,pgsql'],
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        try {
            $dsn = $data['db_connection'] === 'pgsql'
                ? "pgsql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']}"
                : "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']}";

            new PDO($dsn, $data['db_username'], $data['db_password'] ?? '', [PDO::ATTR_TIMEOUT => 5]);
        } catch (PDOException $e) {
            return back()->withInput()->with('error', $this->friendlyDbError($e->getMessage()));
        }

        $env->set([
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
        ]);

        return redirect()->route('install.settings');
    }

    public function settings()
    {
        return view('install.settings', ['steps' => $this->steps, 'current' => 'settings']);
    }

    public function storeSettings(Request $request, EnvironmentWriter $env)
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url'],
            'currency' => ['required', 'string', 'size:3'],
            'mail_enabled' => ['nullable', 'boolean'],
            'mail_host' => ['nullable', 'required_if:mail_enabled,1', 'string'],
            'mail_port' => ['nullable', 'required_if:mail_enabled,1', 'numeric'],
            'mail_username' => ['nullable', 'string'],
            'mail_password' => ['nullable', 'string'],
            'mail_encryption' => ['nullable', 'in:tls,ssl'],
            'mail_from_address' => ['nullable', 'email'],
        ]);

        $values = [
            'APP_NAME' => $data['app_name'],
            'APP_URL' => rtrim($data['app_url'], '/'),
            'MARKETPLACE_CURRENCY' => strtoupper($data['currency']),
        ];

        if ($request->boolean('mail_enabled')) {
            $values += [
                'MAIL_MAILER' => 'smtp',
                'MAIL_HOST' => $data['mail_host'],
                'MAIL_PORT' => $data['mail_port'],
                'MAIL_USERNAME' => $data['mail_username'] ?? '',
                'MAIL_PASSWORD' => $data['mail_password'] ?? '',
                'MAIL_ENCRYPTION' => $data['mail_encryption'] ?? 'tls',
                'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? $data['mail_username'] ?? '',
            ];
        } else {
            $values['MAIL_MAILER'] = 'log';
        }

        $env->set($values);

        return redirect()->route('install.admin');
    }

    public function admin()
    {
        return view('install.admin', ['steps' => $this->steps, 'current' => 'admin']);
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        session(['install.admin' => [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]]);

        return redirect()->route('install.license');
    }

    public function license()
    {
        return view('install.license', ['steps' => $this->steps, 'current' => 'license']);
    }

    public function storeLicense(Request $request)
    {
        $data = $request->validate([
            'buyer_name' => ['nullable', 'string', 'max:150'],
            'purchase_code' => ['nullable', 'string', 'max:100'],
        ]);

        session(['install.license' => $data]);

        return redirect()->route('install.finish');
    }

    public function finish()
    {
        if (! session()->has('install.admin')) {
            return redirect()->route('install.admin')
                ->with('error', 'Please complete the admin account step first.');
        }

        return view('install.finish', ['steps' => $this->steps, 'current' => 'finish']);
    }

    public function runInstall(EnvironmentWriter $env, LicenseManager $licenseManager)
    {
        if (! session()->has('install.admin')) {
            return response()->json(['success' => false, 'message' => 'Session expired — please start over.']);
        }

        try {
            if (! config('app.key')) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            Artisan::call('migrate', ['--force' => true]);

            \App\Models\PlatformSetting::set('default_commission_rate', '30');
            \App\Models\PlatformSetting::set('minimum_payout_amount', '50');
            \App\Models\PlatformSetting::set('payout_holding_days', '14');
            \App\Models\PlatformSetting::set('site_currency', config('marketplace.currency', 'USD'));

            $adminData = session('install.admin');

            $admin = \App\Models\User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($adminData['password']),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);

            $licenseData = session('install.license', []);
            $licenseManager->generate($licenseData['buyer_name'] ?? '', $licenseData['purchase_code'] ?? '');

            File::put(storage_path('installed.lock'), json_encode([
                'installed_at' => now()->toIso8601String(),
                'app_url' => config('app.url'),
            ], JSON_PRETTY_PRINT));

            session()->forget(['install.admin', 'install.license']);

            auth()->login($admin);

            return response()->json(['success' => true, 'redirect' => route('admin.dashboard')]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Installation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
            ]);
        }
    }

    protected function runRequirementChecks(): array
    {
        return [
            ['label' => 'PHP >= 8.2', 'passed' => version_compare(PHP_VERSION, '8.2.0', '>=')],
            ['label' => 'PDO extension', 'passed' => extension_loaded('pdo')],
            ['label' => 'Mbstring extension', 'passed' => extension_loaded('mbstring')],
            ['label' => 'OpenSSL extension', 'passed' => extension_loaded('openssl')],
            ['label' => 'Tokenizer extension', 'passed' => extension_loaded('tokenizer')],
            ['label' => 'XML extension', 'passed' => extension_loaded('xml')],
            ['label' => 'Ctype extension', 'passed' => extension_loaded('ctype')],
            ['label' => 'JSON extension', 'passed' => extension_loaded('json')],
            ['label' => 'BCMath extension', 'passed' => extension_loaded('bcmath')],
            ['label' => 'Fileinfo extension', 'passed' => extension_loaded('fileinfo')],
            ['label' => 'GD extension (thumbnails)', 'passed' => extension_loaded('gd')],
            ['label' => 'Zip extension', 'passed' => extension_loaded('zip')],
            ['label' => 'storage/ is writable', 'passed' => is_writable(storage_path())],
            ['label' => 'bootstrap/cache/ is writable', 'passed' => is_writable(base_path('bootstrap/cache'))],
            ['label' => '.env is writable', 'passed' => is_writable(base_path('.env')) || is_writable(base_path())],
        ];
    }

    protected function friendlyDbError(string $raw): string
    {
        return match (true) {
            str_contains($raw, 'Unknown database') => 'That database doesn\'t exist yet. Create it first, or check the name.',
            str_contains($raw, 'Access denied') || str_contains($raw, 'password authentication failed') => 'Access denied — check the username and password.',
            str_contains($raw, 'Connection refused') || str_contains($raw, "Name or service not known") => 'Could not reach that host/port — check they\'re correct and the database server is running.',
            default => $raw,
        };
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Reads and updates key=value pairs in the .env file. This only ever
 * touches lines matching KEY=..., appending new keys that don't exist yet —
 * it never rewrites the whole file blindly, so comments and unrelated
 * settings survive untouched.
 */
class EnvironmentWriter
{
    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? base_path('.env');
    }

    public function set(array $values): void
    {
        if (! File::exists($this->path)) {
            File::put($this->path, '');
        }

        $content = File::get($this->path);

        foreach ($values as $key => $value) {
            $line = $key . '=' . $this->formatValue($value);

            $pattern = "/^{$key}=.*/m";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content = rtrim($content) . "\n" . $line . "\n";
            }
        }

        File::put($this->path, $content);
    }

    protected function formatValue(mixed $value): string
    {
        $value = (string) $value;

        // Quote if it contains whitespace or a # (would otherwise be read
        // as a comment) — plain alphanumeric values stay unquoted, matching
        // how Laravel's own .env.example is formatted.
        if ($value === '' || preg_match('/\s|#/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }

        return $value;
    }
}

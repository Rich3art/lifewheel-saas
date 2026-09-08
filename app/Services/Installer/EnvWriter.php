<?php

namespace App\Services\Installer;

final class EnvWriter
{
    /**
     * @param  array<string, string>  $values
     */
    public function write(array $values): void
    {
        $path = base_path('.env');
        $contents = file_exists($path)
            ? file_get_contents($path)
            : (file_exists(base_path('.env.example')) ? file_get_contents(base_path('.env.example')) : '');

        $contents = (string) $contents;

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->format($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $line, $contents) ?? $contents;
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($path, rtrim($contents).PHP_EOL, LOCK_EX);
    }

    private function format(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|"|\'|=/', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }
}

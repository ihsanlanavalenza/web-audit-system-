<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        $uploadMaxFilesize = $this->parseIniSizeToBytes(ini_get('upload_max_filesize'));
        $postMaxSize = $this->parseIniSizeToBytes(ini_get('post_max_size'));
        $maxFileUploads = (int) ini_get('max_file_uploads');

        if ($uploadMaxFilesize > 0 && $uploadMaxFilesize < 10485760) {
            Log::warning('PHP upload_max_filesize terlalu kecil untuk kebutuhan upload aplikasi.', [
                'current' => ini_get('upload_max_filesize'),
                'recommended_min' => '10M',
            ]);
        }

        if ($postMaxSize > 0 && $postMaxSize < 20971520) {
            Log::warning('PHP post_max_size terlalu kecil untuk kebutuhan multi upload aplikasi.', [
                'current' => ini_get('post_max_size'),
                'recommended_min' => '20M',
            ]);
        }

        if ($maxFileUploads > 0 && $maxFileUploads < 10) {
            Log::warning('PHP max_file_uploads terlalu kecil untuk kebutuhan multi upload aplikasi.', [
                'current' => $maxFileUploads,
                'recommended_min' => 10,
            ]);
        }

        $livewireTmpPath = storage_path('framework/livewire-tmp');
        if (is_dir($livewireTmpPath) && !is_writable($livewireTmpPath)) {
            Log::warning('Direktori Livewire temporary upload tidak writable.', [
                'path' => $livewireTmpPath,
            ]);
        }
    }

    private function parseIniSizeToBytes(string|false $value): int
    {
        if ($value === false || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $unit = strtolower(substr($value, -1));
        $bytes = (float) $value;

        return match ($unit) {
            'g' => (int) ($bytes * 1024 * 1024 * 1024),
            'm' => (int) ($bytes * 1024 * 1024),
            'k' => (int) ($bytes * 1024),
            default => (int) $bytes,
        };
    }
}

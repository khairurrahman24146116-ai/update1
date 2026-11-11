<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ServesPrivateFiles
{
    protected function privateFileResponse(?string $path): StreamedResponse
    {
        if (! is_string($path) || trim($path) === '') {
            abort(404);
        }

        return Storage::disk($this->privateFileDisk($path))->response($path);
    }

    protected function deletePrivateFile(?string $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function privateFileDisk(string $path): string
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        abort(404);
    }
}

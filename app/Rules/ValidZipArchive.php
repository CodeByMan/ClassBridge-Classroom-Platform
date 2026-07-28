<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class ValidZipArchive implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        if (strtolower($value->getClientOriginalExtension()) !== 'zip') {
            $fail('The :attribute must use the .zip extension.');

            return;
        }

        if (! class_exists(ZipArchive::class)) {
            return;
        }

        $archive = new ZipArchive;
        $result = $archive->open($value->getRealPath(), ZipArchive::CHECKCONS);

        if ($result !== true) {
            $fail('The :attribute must be a readable ZIP archive.');

            return;
        }

        $archive->close();
    }
}

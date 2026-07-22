<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;
use Override;
use Spatie\LaravelData\Attributes\Validation\File;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Data;

class ImportData extends Data
{
    public function __construct(
        #[File]
        #[Max(102400)]
        #[Mimes(['csv', 'txt'])]
        public UploadedFile $file
    ) {}

    public static function attributes(...$args)
    {
        return [
            'file' => 'Файл'
        ];
    }
}

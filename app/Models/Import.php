<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Override;

class Import extends Model
{
    protected $guarded = [];

    #[Override]
    protected function casts()
    {
        return [
            'status' => Status::class
        ];
    }
}

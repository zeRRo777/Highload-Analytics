<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;

class Transaction extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    #[Override]
    protected function casts()
    {
        return [
            'transaction_date' => 'datetime'
        ];
    }
}

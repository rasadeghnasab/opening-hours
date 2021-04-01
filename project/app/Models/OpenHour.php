<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'day',
        'from',
        'to',
    ];

    public function timeable()
    {
        return $this->morphTo();
    }
}

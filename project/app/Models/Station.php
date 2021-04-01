<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    public function times()
    {
        return $this->morphMany(OpenHour::class, 'timeable');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}

<?php

namespace App\Models;

use App\Interfaces\TimeableInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Station extends Model implements TimeableInterface
{
    use HasFactory;

    public function times(): MorphMany
    {
        return $this->morphMany(OpenHour::class, 'timeable');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}

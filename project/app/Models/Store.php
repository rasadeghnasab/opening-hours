<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function stations()
    {
        return $this->hasMany(Station::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function times()
    {
        return $this->morphMany(OpenHour::class, 'timeable');
    }
}

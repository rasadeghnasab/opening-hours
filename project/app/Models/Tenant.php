<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function times()
    {
        return $this->morphMany(OpenHour::class, 'timeable');
    }
}

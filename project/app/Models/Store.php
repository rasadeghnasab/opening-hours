<?php

namespace App\Models;

use App\Interfaces\TimeableInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Store extends Model implements TimeableInterface
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

    public function times(): MorphMany
    {
        return $this->morphMany(OpenHour::class, 'timeable');
    }

    public function parent(): string
    {
        return 'tenant';
    }
}

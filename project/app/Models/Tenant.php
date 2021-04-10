<?php

namespace App\Models;

use App\Interfaces\TimeableInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tenant extends Model implements TimeableInterface
{
    use HasFactory;

    protected $fillable = ['name'];

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function times(): MorphMany
    {
        return $this->morphMany(OpenHour::class, 'timeable');
    }

    public function exceptionTimes(): MorphMany
    {
        return $this->morphMany(OpenHourException::class, 'timeable');
    }

    /**
     * It returns null since it has no parent
     * @return string
     */
    public function parent(): string
    {
        return '';
    }
}

<?php

namespace App\Models;

use App\Models\Scopes\TimeablesOrderByPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeablePriority extends Model
{
    use HasFactory;

    protected $table = 'timeables_priority';

    protected $fillable = ['name', 'priority'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new TimeablesOrderByPriority);
    }
}

<?php

namespace App\Models;

use App\Interfaces\OpenHourInterface;
use App\Traits\HoursScopeTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenHour extends Model implements OpenHourInterface
{
    use HasFactory, HoursScopeTrait;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $fillable = ['day', 'from', 'to'];

    protected $appends = ['status'];

    /**
     * Open hours status is always 1
     *
     * @return int
     */
    public function getStatusAttribute(): int
    {
        return 1;
    }

    /**
     * Retrieve any timeable entity
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function timeable()
    {
        return $this->morphTo();
    }

    public function convertTime(Carbon $date_time): string
    {
        return $date_time->toTimeString();
    }
}

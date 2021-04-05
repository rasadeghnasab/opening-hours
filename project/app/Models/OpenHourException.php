<?php

namespace App\Models;

use App\Interfaces\OpenHourInterface;
use App\Traits\HoursScopeTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenHourException extends Model implements OpenHourInterface
{
    use HasFactory, HoursScopeTrait;

    protected $fillable = ['from', 'to', 'status', 'comment'];

    protected $dates = [
        'from',
        'to'
    ];

    public function convertTime(Carbon $date_time): string
    {
        return $date_time->toDateTimeString();
    }
}

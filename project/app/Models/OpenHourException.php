<?php

namespace App\Models;

use App\Interfaces\OpenHourInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenHourException extends Model implements OpenHourInterface
{
    use HasFactory;

    protected $fillable = ['from', 'to', 'status', 'comment'];

    protected $dates = [
        'from',
        'to'
    ];
}

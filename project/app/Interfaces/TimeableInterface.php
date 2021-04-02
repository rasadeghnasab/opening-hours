<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface TimeableInterface
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function times(): MorphMany;
}

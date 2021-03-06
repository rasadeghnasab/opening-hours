<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface TimeableInterface
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function times(): MorphMany;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function exceptionTimes(): MorphMany;

    /**
     * Returns parent name as a string
     *
     * @return string
     */
    public function parent(): string;
}

<?php

namespace App\Interfaces;

interface IntersectionInterface
{
    /**
     * Returns a boolean to indicates that whether we should continue the loop or not
     *
     * @return bool
     */
    public function shouldContinue(): bool;

    /**
     * Returns a boolean to indicates that whether we should break the loop or not
     *
     * @return bool
     */
    public function shouldBreak(): bool;

    /**
     * Returns an array that we can push into a timeline
     * @return array
     */
    public function output(): array;

    /**
     * The next starting time
     * @return string
     */
    public function nextStart(): string;
}

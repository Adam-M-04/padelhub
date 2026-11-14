<?php

namespace App\Message;

class RoundCreated
{
    public function __construct(public readonly int $roundId)
    {
    }
}

<?php

namespace App\Message;

class PlayerPromoted
{
    public function __construct(public readonly int $registrationId)
    {
    }
}

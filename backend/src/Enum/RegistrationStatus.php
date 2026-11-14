<?php

namespace App\Enum;

enum RegistrationStatus: string
{
    case REGISTERED = 'REGISTERED';
    case RESERVE = 'RESERVE';
    case WITHDRAWN = 'WITHDRAWN';
}

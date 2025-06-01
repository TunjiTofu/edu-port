<?php

namespace App\Enums;

enum ProgramEnrollmentStatus: string
{
    case ACTIVE = 'Active';
    case COMPLETED = 'Completed';
    case PAUSED = 'Paused';
}

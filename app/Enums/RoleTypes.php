<?php

namespace App\Enums;

enum RoleTypes: string
{
    case ADMIN = 'Admin';
    case REVIEWER = 'Reviewer';
    case STUDENT = 'Student';
    case OBSERVER = 'Observer';
}

<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Instructor = 'instructor';
    case Student = 'student';
}

<?php

namespace App\Enums;

/**
 * Application roles, stored by spatie/laravel-permission.
 *
 * Use these cases instead of raw strings, e.g. $user->hasRole(Role::Teacher).
 */
enum Role: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return __('roles.'.$this->value);
    }
}

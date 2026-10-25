<?php

namespace App\Filament\Teacher\Resources\QuizAttempts\Pages;

use App\Filament\Teacher\Resources\QuizAttempts\QuizAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListQuizAttempts extends ListRecords
{
    protected static string $resource = QuizAttemptResource::class;
}

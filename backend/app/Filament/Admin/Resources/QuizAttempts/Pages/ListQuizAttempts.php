<?php

namespace App\Filament\Admin\Resources\QuizAttempts\Pages;

use App\Filament\Admin\Resources\QuizAttempts\QuizAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListQuizAttempts extends ListRecords
{
    protected static string $resource = QuizAttemptResource::class;
}

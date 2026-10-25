<?php

namespace App\Filament\Teacher\Resources\Quizzes\Pages;

use App\Filament\Teacher\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\ListRecords;

class ListQuizzes extends ListRecords
{
    protected static string $resource = QuizResource::class;
}

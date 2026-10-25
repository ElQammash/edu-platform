<?php

namespace App\Filament\Admin\Resources\Quizzes\Pages;

use App\Filament\Admin\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\ListRecords;

class ListQuizzes extends ListRecords
{
    protected static string $resource = QuizResource::class;
}

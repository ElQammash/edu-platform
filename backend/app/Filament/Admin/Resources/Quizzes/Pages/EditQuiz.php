<?php

namespace App\Filament\Admin\Resources\Quizzes\Pages;

use App\Filament\Admin\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;
}

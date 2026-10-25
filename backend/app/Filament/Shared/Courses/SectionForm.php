<?php

namespace App\Filament\Shared\Courses;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('courses.section.fields.title'))
                    ->required()
                    ->maxLength(255),
            ]);
    }
}

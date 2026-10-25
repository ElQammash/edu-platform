<?php

namespace App\Filament\Admin\Resources\Courses\Resources\Sections\Pages;

use App\Filament\Admin\Resources\Courses\Resources\Sections\SectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSection extends EditRecord
{
    protected static string $resource = SectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

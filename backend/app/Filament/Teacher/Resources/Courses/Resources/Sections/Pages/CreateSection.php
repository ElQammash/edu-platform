<?php

namespace App\Filament\Teacher\Resources\Courses\Resources\Sections\Pages;

use App\Filament\Shared\Courses\Concerns\CreatesSectionAtEnd;
use App\Filament\Teacher\Resources\Courses\Resources\Sections\SectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSection extends CreateRecord
{
    use CreatesSectionAtEnd;

    protected static string $resource = SectionResource::class;
}

<?php

namespace App\Filament\Admin\Resources\Courses\Resources\Sections\Pages;

use App\Filament\Admin\Resources\Courses\Resources\Sections\SectionResource;
use App\Filament\Shared\Courses\Concerns\CreatesSectionAtEnd;
use Filament\Resources\Pages\CreateRecord;

class CreateSection extends CreateRecord
{
    use CreatesSectionAtEnd;

    protected static string $resource = SectionResource::class;
}

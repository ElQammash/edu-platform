<?php

namespace App\Filament\Teacher\Resources\Courses\Resources\Sections\Pages;

use App\Filament\Shared\Courses\SectionsTable;
use App\Filament\Teacher\Resources\Courses\Resources\Sections\SectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSection extends CreateRecord
{
    protected static string $resource = SectionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // New sections go to the end of the course.
        $course = $this->getParentRecord();
        $data['order'] = $course ? SectionsTable::nextOrder((int) $course->getKey()) : 0;

        return $data;
    }

    /**
     * Continue to the section page, where lessons are added.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->getRecord(),
            'course' => $this->getParentRecord(),
        ]);
    }
}

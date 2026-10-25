<?php

namespace App\Filament\Shared\Courses\Concerns;

use App\Filament\Shared\Courses\SectionsTable;

/**
 * For the nested "create section" pages: append the section to the end of
 * the course, then continue to the section page to add lessons.
 */
trait CreatesSectionAtEnd
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $course = $this->getParentRecord();
        $data['order'] = $course ? SectionsTable::nextOrder((int) $course->getKey()) : 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->getRecord(),
            'course' => $this->getParentRecord(),
        ]);
    }
}

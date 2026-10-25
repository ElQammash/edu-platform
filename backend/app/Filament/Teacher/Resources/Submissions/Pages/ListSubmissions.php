<?php

namespace App\Filament\Teacher\Resources\Submissions\Pages;

use App\Filament\Teacher\Resources\Submissions\SubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;
}

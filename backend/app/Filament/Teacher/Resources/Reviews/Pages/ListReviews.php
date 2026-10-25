<?php

namespace App\Filament\Teacher\Resources\Reviews\Pages;

use App\Filament\Teacher\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
}

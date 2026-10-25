<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\RatingService;

/**
 * Any change to a review (new, edited, hidden, deleted) refreshes the
 * course's and teacher's cached rating, wherever it comes from (API or panel).
 */
class ReviewObserver
{
    public function __construct(private readonly RatingService $ratings) {}

    public function saved(Review $review): void
    {
        if ($review->wasRecentlyCreated || $review->wasChanged(['rating', 'hidden_at'])) {
            $this->ratings->refresh($review->course);
        }
    }

    public function deleted(Review $review): void
    {
        $this->ratings->refresh($review->course);
    }
}

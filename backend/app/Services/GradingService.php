<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionGraded;
use Illuminate\Validation\ValidationException;

class GradingService
{
    /**
     * Grade or re-grade a submission and notify the student. Authorization
     * (SubmissionPolicy::grade) is the caller's job.
     *
     * @throws ValidationException when the score is outside 0..max_score
     */
    public function grade(Submission $submission, User $grader, float $score, ?string $feedback = null): Submission
    {
        $max = $submission->assignment->max_score;

        if ($score < 0 || $score > $max) {
            throw ValidationException::withMessages(['score' => __('submissions.score_range', ['max' => $max])]);
        }

        $regraded = $submission->isGraded();

        $submission->forceFill([
            'score' => $score,
            'feedback' => filled($feedback) ? trim((string) $feedback) : null,
            'graded_by' => $grader->id,
            'graded_at' => now(),
        ])->save();

        $submission->student->notify(new SubmissionGraded($submission, $regraded));

        return $submission;
    }
}

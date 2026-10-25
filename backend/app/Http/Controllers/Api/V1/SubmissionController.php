<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Assignments\SubmitAssignmentRequest;
use App\Http\Resources\V1\SubmissionResource;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    /**
     * POST /assignments/{id}/submission: create the student's submission, or
     * update it while it is not graded (late submissions are accepted).
     */
    public function store(SubmitAssignmentRequest $request, Assignment $assignment, SubmissionService $submissions): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $assignment->load('lesson.section.course');

        Gate::authorize('submit', $assignment);

        $existing = $assignment->submissions()->where('student_id', $user->id)->first();
        if ($existing) {
            Gate::authorize('update', $existing);
        }

        $submission = $submissions->submit(
            $user,
            $assignment,
            $request->string('content')->toString(),
            $request->file('file'),
            $request->boolean('remove_file'),
        );

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode($existing ? 200 : 201);
    }

    /**
     * The submission's file, for its student, the course teacher and admins.
     * Used by the API (React) and by a web route (Filament panels).
     */
    public function file(Request $request, Submission $submission): StreamedResponse
    {
        $submission->load('assignment.lesson.section.course');

        Gate::authorize('view', $submission);

        return $this->streamFile($submission);
    }

    /**
     * The same file through a temporary signed link (handed out in
     * SubmissionResource to users who passed the policy check). The
     * "signed" middleware rejects expired or altered links.
     */
    public function signedFile(Submission $submission): StreamedResponse
    {
        return $this->streamFile($submission);
    }

    private function streamFile(Submission $submission): StreamedResponse
    {
        abort_unless($submission->hasFile() && Storage::disk(Submission::DISK)->exists((string) $submission->file_path), 404);

        /** @var list<string> $allowed */
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $mime = in_array($submission->file_mime, $allowed, true) ? (string) $submission->file_mime : 'application/octet-stream';

        return Storage::disk(Submission::DISK)->response(
            (string) $submission->file_path,
            $submission->file_name,
            [
                'Content-Type' => $mime,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
            // PDFs and images open in the browser; anything unexpected downloads.
            $mime === 'application/octet-stream' ? 'attachment' : 'inline',
        );
    }
}

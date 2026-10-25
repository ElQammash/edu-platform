<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    /**
     * Create or update the student's single submission for an assignment.
     * Authorization (AssignmentPolicy::submit, SubmissionPolicy::update) is
     * the caller's job.
     *
     * A new file replaces the old one; $removeFile drops it. The result must
     * keep an answer text or a file. Submitting after the deadline is allowed
     * and makes the submission late.
     *
     * @throws ValidationException when graded already or left empty
     */
    public function submit(User $student, Assignment $assignment, ?string $content, ?UploadedFile $file = null, bool $removeFile = false): Submission
    {
        $submission = Submission::firstOrNew([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
        ]);

        if ($submission->isGraded()) {
            throw ValidationException::withMessages(['submission' => __('submissions.already_graded')]);
        }

        $content = filled($content) ? trim($content) : null;
        $keepsFile = $submission->hasFile() && ! $removeFile;

        if ($content === null && $file === null && ! $keepsFile) {
            throw ValidationException::withMessages(['content' => __('submissions.empty')]);
        }

        $disk = Storage::disk(Submission::DISK);
        $oldPath = $submission->file_path;

        if ($file) {
            $path = $file->storeAs(
                "submissions/{$assignment->id}",
                Str::uuid().'.'.strtolower($file->extension()),
                Submission::DISK,
            );

            $submission->fill([
                'file_path' => $path ?: null,
                'file_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'file_mime' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        } elseif ($removeFile) {
            $submission->fill(['file_path' => null, 'file_name' => null, 'file_mime' => null, 'file_size' => null]);
        }

        $submission->content = $content;
        $submission->submitted_at = now();

        try {
            DB::transaction(fn () => $submission->save());
        } catch (\Throwable $e) {
            // Do not leave an orphan upload behind if saving failed.
            if ($file && $submission->file_path) {
                $disk->delete($submission->file_path);
            }

            throw $e;
        }

        if ($oldPath && $oldPath !== $submission->file_path) {
            $disk->delete($oldPath);
        }

        return $submission->setRelation('assignment', $assignment);
    }
}

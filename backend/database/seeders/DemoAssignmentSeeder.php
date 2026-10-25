<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Submission;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Sample assignments with one submission of each kind, so the teacher's
 * grading queue and the student's notifications have something to show:
 * graded (with a notification), waiting for grading, late, and with a file.
 * Lessons that already have an assignment are skipped.
 */
class DemoAssignmentSeeder extends Seeder
{
    public function run(GradingService $grading): void
    {
        $student1 = User::where('email', 'student1@edu-platform.test')->firstOrFail();
        $student2 = User::where('email', 'student2@edu-platform.test')->firstOrFail();
        $student3 = User::where('email', 'student3@edu-platform.test')->firstOrFail();
        $ahmed = User::where('email', 'teacher1@edu-platform.test')->firstOrFail();

        // Physics: past deadline; student1 graded, student3 submitted late.
        $ohm = $this->assignment('physics-secondary-3', 'قانون أوم', [
            'title' => 'مسائل على قانون أوم',
            'instructions' => "حل المسائل من ١ إلى ٥ في آخر الفصل.\n\n- اكتب القانون المستخدم في كل مسألة\n- وضّح الوحدات",
            'due_at' => now()->subDays(3)->setTime(23, 59),
            'max_score' => 20,
        ]);
        if ($ohm) {
            $graded = $this->submission($ohm, $student1, 'الحل مرفق في الملف.', now()->subDays(4), withFile: true);
            $grading->grade($graded, $ahmed, 18, 'حل ممتاز، راجع وحدات المسألة الرابعة.');
            $this->submission($ohm, $student3, "المسألة ١: I = V / R = 12 / 4 = 3 A\nالمسألة ٢: ...", now()->subDays(1));
        }

        // Physics: open deadline; student1 waiting for grading.
        $field = $this->assignment('physics-secondary-3', 'المجال المغناطيسي لسلك مستقيم', [
            'title' => 'تقرير عن قاعدة اليد اليمنى',
            'instructions' => 'اكتب فقرة تشرح فيها قاعدة اليد اليمنى مع رسم توضيحي (صورة أو PDF).',
            'due_at' => now()->addDays(5)->setTime(23, 59),
            'max_score' => 10,
        ]);
        if ($field) {
            $this->submission($field, $student1, 'قاعدة اليد اليمنى بتحدد اتجاه المجال حوالين السلك: الإبهام في اتجاه التيار والأصابع في اتجاه المجال.', now()->subHours(5));
        }

        // Calculus: student2 waiting for grading.
        $derivatives = $this->assignment('calculus-secondary-3', 'قواعد الاشتقاق', [
            'title' => 'تمارين على قواعد الاشتقاق',
            'instructions' => 'أوجد مشتقة الدوال في التمرين (٣-٢) من ١ إلى ١٠.',
            'due_at' => now()->addWeek()->setTime(23, 59),
            'max_score' => 50,
        ]);
        if ($derivatives) {
            $this->submission($derivatives, $student2, null, now()->subHours(2), withFile: true);
        }

        // Algebra: no deadline and no submissions yet, for trying it out.
        $this->assignment('algebra-foundations', 'Linear equations', [
            'title' => 'Solve five linear equations',
            'instructions' => "Solve each equation and show your steps:\n\n1. 2x + 3 = 11\n2. 5x - 7 = 3x + 1\n3. 4(x - 2) = 12\n4. x/3 + 2 = 5\n5. 7 - 2x = x - 8",
            'due_at' => null,
            'max_score' => 25,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assignment(string $courseSlug, string $lessonTitle, array $attributes): ?Assignment
    {
        $course = Course::where('slug', $courseSlug)->firstOrFail();
        $lesson = Lesson::query()
            ->whereIn('section_id', $course->sections()->select('id'))
            ->where('title', $lessonTitle)
            ->firstOrFail();

        if ($lesson->assignment()->exists()) {
            return null;
        }

        return $lesson->assignment()->create($attributes);
    }

    private function submission(Assignment $assignment, User $student, ?string $content, \DateTimeInterface $submittedAt, bool $withFile = false): Submission
    {
        $file = [];

        if ($withFile) {
            $path = "submissions/{$assignment->id}/demo-{$student->id}.pdf";
            Storage::disk(Submission::DISK)->put($path, $this->samplePdf());
            $file = [
                'file_path' => $path,
                'file_name' => 'answer.pdf',
                'file_mime' => 'application/pdf',
                'file_size' => Storage::disk(Submission::DISK)->size($path),
            ];
        }

        return Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => $content,
            'submitted_at' => $submittedAt,
            ...$file,
        ]);
    }

    /**
     * A minimal one-page PDF, so the demo has a real file to open.
     */
    private function samplePdf(): string
    {
        $text = 'Demo submission - Edu Platform';
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}

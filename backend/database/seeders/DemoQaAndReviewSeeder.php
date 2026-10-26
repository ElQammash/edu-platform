<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\Review;
use App\Models\User;
use App\Services\LessonProgressService;
use App\Services\RatingService;
use Illuminate\Database\Seeder;

/**
 * Lesson questions in every state (answered and accepted, waiting for the
 * teacher, answered only by a classmate) and course reviews (one with a
 * teacher reply, one hidden by the admin). student1 has done 40% of the
 * physics course and has not reviewed it, so they can try writing one.
 * Skipped when the demo courses already have questions.
 */
class DemoQaAndReviewSeeder extends Seeder
{
    public function run(LessonProgressService $progress, RatingService $ratings): void
    {
        $physics = Course::where('slug', 'physics-secondary-3')->firstOrFail();
        $olympiad = Course::where('slug', 'physics-olympiad-preparation')->firstOrFail();

        if (LessonComment::whereIn('lesson_id', $physics->lessons()->pluck('lessons.id'))->exists()) {
            return;
        }

        $teacher = $physics->teacher;
        $admin = User::where('email', 'admin@edu-platform.test')->firstOrFail();
        [$omar, , $youssef, $salma, $karim] = User::where('email', 'like', 'student%@edu-platform.test')
            ->orderBy('email')
            ->get()
            ->all();

        // Two more physics students, far enough in to review the course.
        foreach ([$salma, $karim] as $student) {
            Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'course_id' => $physics->id],
                ['payment_status' => PaymentStatus::Free, 'enrolled_at' => now()->subWeeks(3), 'progress_percent' => 0],
            );
            foreach ($physics->lessons()->orderBy('lessons.id')->take(4)->get() as $lesson) {
                $progress->markComplete($student, $lesson);
            }
        }

        /** @var Lesson $first */
        $first = $physics->lessons()->orderBy('lessons.id')->firstOrFail();
        /** @var Lesson $second */
        $second = $physics->lessons()->orderBy('lessons.id')->skip(1)->firstOrFail();
        /** @var Lesson $olympiadLesson */
        $olympiadLesson = $olympiad->lessons()->orderBy('lessons.id')->firstOrFail();

        // Answered by the teacher, who accepted the answer: resolved.
        $answered = $this->post($first, $omar, 'ليه التيار ثابت في كل أجزاء الدائرة الموصلة على التوالي؟', hoursAgo: 50);
        $answer = $this->post($first, $teacher, 'لأن الشحنات مش بتتراكم في أي نقطة: كل اللي يدخل أي مقطع في الثانية يخرج منه. ارجع للمثال في الدقيقة 6 من الفيديو.', hoursAgo: 48, question: $answered);
        $answered->update(['accepted_reply_id' => $answer->id, 'resolved_at' => now()->subHours(47)]);

        // Waiting for the teacher (in the inbox, the widget and the digest).
        $this->post($first, $youssef, 'هل قانون أوم ينطبق على المصباح الكهربي لما يسخن؟', hoursAgo: 20);

        // A classmate replied, but the teacher hasn't yet: still unanswered.
        $classmate = $this->post($second, $salma, 'مش فاهمة إزاي نحسب المقاومة المكافئة لو فيه توصيل مختلط.', hoursAgo: 6);
        $this->post($second, $omar, 'ابدأي بالمقاومات اللي على التوازي وحوليها لمقاومة واحدة، وبعدين اجمعي على التوالي.', hoursAgo: 5, question: $classmate);

        $this->post($olympiadLesson, $youssef, 'In problem 3, why can we ignore air resistance?', hoursAgo: 3);

        // Reviews: Karim's one-star spam is hidden by the admin.
        $this->review($physics, $youssef, 5, 'شرح ممتاز ومنظم، والمسائل المحلولة فرقت معايا جداً.', reply: 'شكراً يا يوسف! بالتوفيق في الامتحان.');
        $this->review($physics, $salma, 4, 'الكورس كويس جداً، بس ياريت يكون فيه مسائل أكتر على الدوائر.');
        $this->review($physics, $karim, 1, 'اشترك في قناتي على يوتيوب!!!', hiddenBy: $admin);
        $this->review($olympiad, $youssef, 4, 'Challenging problems and clear solutions. The live problem nights are the best part.');

        // Model events are off while seeding, so refresh the cached ratings here.
        $ratings->refresh($physics);
        $ratings->refresh($olympiad);
    }

    private function post(Lesson $lesson, User $author, string $body, int $hoursAgo, ?LessonComment $question = null): LessonComment
    {
        $comment = LessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $author->id,
            'parent_id' => $question?->id,
            'body' => $body,
            'by_staff' => $author->isAdmin() || $author->teaches($lesson->course()),
        ]);
        $comment->forceFill(['created_at' => now()->subHours($hoursAgo), 'updated_at' => now()->subHours($hoursAgo)])->save();

        return $comment;
    }

    private function review(Course $course, User $student, int $rating, string $comment, ?string $reply = null, ?User $hiddenBy = null): void
    {
        Review::updateOrCreate(
            ['course_id' => $course->id, 'student_id' => $student->id],
            [
                'rating' => $rating,
                'comment' => $comment,
                'teacher_reply' => $reply,
                'teacher_replied_at' => $reply ? now()->subDay() : null,
                'hidden_at' => $hiddenBy ? now()->subHours(2) : null,
                'hidden_by' => $hiddenBy?->id,
            ],
        );
    }
}

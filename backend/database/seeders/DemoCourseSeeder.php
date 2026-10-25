<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use App\Enums\Language;
use App\Enums\LessonType;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\LessonProgressService;
use Illuminate\Database\Seeder;

/**
 * Sample courses for the demo teachers, with a few enrolled students and
 * some progress. Videos are freely licensed Blender open movies standing
 * in for real lessons. Courses that already exist (by slug) are skipped,
 * so the seeder can be re-run.
 */
class DemoCourseSeeder extends Seeder
{
    private const VIDEOS = [
        ['https://www.youtube.com/watch?v=aqz-KE-bpKQ', 635], // Big Buck Bunny
        ['https://www.youtube.com/watch?v=eRsGyueVLvQ', 888], // Sintel
        ['https://www.youtube.com/watch?v=R6MlUcmOul8', 734], // Tears of Steel
    ];

    public function run(LessonProgressService $progress): void
    {
        $ahmed = User::where('email', 'teacher1@edu-platform.test')->firstOrFail();
        $mona = User::where('email', 'teacher2@edu-platform.test')->firstOrFail();

        $physics = $this->course($ahmed, [
            'slug' => 'physics-secondary-3',
            'title' => 'الفيزياء للصف الثالث الثانوي',
            'description' => "شرح كامل لمنهج الفيزياء للصف الثالث الثانوي مع حل مسائل على كل درس.\n\n- الكهربية التيارية\n- التأثير المغناطيسي للتيار\n- الحث الكهرومغناطيسي",
            'price' => 0,
            'language' => Language::Arabic,
            'type' => CourseType::Recorded,
        ], [
            'الكهربية التيارية' => ['التيار الكهربي وشدته', 'قانون أوم', 'ملخص الفصل الأول'],
            'التأثير المغناطيسي للتيار' => ['المجال المغناطيسي لسلك مستقيم', 'الملف الدائري واللولبي', 'ملخص الفصل الثاني'],
        ]);

        $olympiad = $this->course($ahmed, [
            'slug' => 'physics-olympiad-preparation',
            'title' => 'Physics Olympiad Preparation',
            'description' => "Problem-solving sessions for students preparing for national and international physics olympiads.\n\nRecorded lessons plus weekly live problem nights.",
            'price' => 750,
            'language' => Language::English,
            'type' => CourseType::Hybrid,
        ], [
            'Mechanics' => ['Kinematics in two dimensions', 'Newton\'s laws in depth', 'Mechanics problem set'],
            'Electromagnetism' => ['Gauss\'s law', 'Circuits and RC networks'],
        ]);

        $calculus = $this->course($mona, [
            'slug' => 'calculus-secondary-3',
            'title' => 'الرياضيات البحتة - التفاضل والتكامل',
            'description' => "كورس التفاضل والتكامل لطلاب الثانوية العامة، من الأساسيات لحد مسائل الامتحان.\n\nكل درس فيه فيديو شرح وملخص مكتوب.",
            'price' => 500,
            'language' => Language::Arabic,
            'type' => CourseType::Recorded,
        ], [
            'التفاضل' => ['النهايات والاتصال', 'قواعد الاشتقاق', 'تطبيقات على التفاضل'],
            'التكامل' => ['التكامل غير المحدد', 'التكامل المحدد والمساحات'],
        ]);

        $algebra = $this->course($mona, [
            'slug' => 'algebra-foundations',
            'title' => 'Algebra Foundations',
            'description' => "A free course that rebuilds algebra from the ground up: expressions, equations and functions.\n\nGreat as a refresher before secondary school maths.",
            'price' => 0,
            'language' => Language::English,
            'type' => CourseType::Recorded,
        ], [
            'Expressions' => ['Variables and expressions', 'Simplifying expressions'],
            'Equations' => ['Linear equations', 'Systems of equations', 'Chapter summary'],
        ]);

        $this->course($mona, [
            'slug' => 'solid-geometry',
            'title' => 'الهندسة الفراغية (قريباً)',
            'description' => 'الكورس لسه بيتجهز.',
            'price' => 300,
            'language' => Language::Arabic,
            'type' => CourseType::Recorded,
            'status' => CourseStatus::Draft,
        ], [
            'مقدمة' => ['المستقيمات والمستويات في الفراغ'],
        ]);

        $admin = User::where('email', 'admin@edu-platform.test')->firstOrFail();
        $students = User::where('email', 'like', 'student%@edu-platform.test')->orderBy('email')->get()->values();

        // student1: free physics with half the lessons done, plus algebra.
        $this->enroll($students[0], $physics);
        $this->enroll($students[0], $algebra);
        foreach ($physics->lessons()->orderBy('lessons.id')->take(3)->get() as $lesson) {
            $progress->markComplete($students[0], $lesson);
        }

        // student2: paid calculus, enrolled by the admin after paying cash.
        $this->enroll($students[1], $calculus, $admin);
        $progress->recordWatch($students[1], $calculus->lessons()->orderBy('lessons.id')->firstOrFail(), 300);

        // student3: paid olympiad course and free physics.
        $this->enroll($students[2], $olympiad, $admin);
        $this->enroll($students[2], $physics);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, list<string>>  $curriculum  section title => lesson titles
     */
    private function course(User $teacher, array $attributes, array $curriculum): Course
    {
        $existing = Course::where('slug', $attributes['slug'])->first();
        if ($existing) {
            return $existing;
        }

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'status' => CourseStatus::Published,
            ...$attributes,
        ]);

        $videoIndex = 0;
        $sectionOrder = 1;

        foreach ($curriculum as $sectionTitle => $lessonTitles) {
            $section = $course->sections()->create(['title' => $sectionTitle, 'order' => $sectionOrder++]);

            foreach ($lessonTitles as $i => $lessonTitle) {
                // The last lesson of each section is a written summary.
                $isText = $i === count($lessonTitles) - 1 && count($lessonTitles) > 2;
                [$videoUrl, $duration] = self::VIDEOS[$videoIndex++ % count(self::VIDEOS)];

                $section->lessons()->create([
                    'title' => $lessonTitle,
                    'type' => $isText ? LessonType::Text : LessonType::Video,
                    'video_url' => $isText ? null : $videoUrl,
                    'duration_seconds' => $isText ? null : $duration,
                    'content' => $this->content($course->language, $lessonTitle, $isText),
                    'order' => $i + 1,
                    // The first lesson of the course is a free preview.
                    'is_free_preview' => $sectionOrder === 2 && $i === 0,
                ]);
            }
        }

        return $course;
    }

    private function enroll(User $student, Course $course, ?User $admin = null): void
    {
        Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            [
                'payment_status' => $admin ? PaymentStatus::Manual : PaymentStatus::Free,
                'enrolled_by' => $admin?->id,
                'enrolled_at' => now(),
                'progress_percent' => 0,
            ],
        );
    }

    private function content(Language $language, string $title, bool $isText): string
    {
        if ($language === Language::Arabic) {
            return $isText
                ? "## {$title}\n\nأهم النقاط في الفصل:\n\n1. راجع التعريفات والقوانين.\n2. حل المسائل المحلولة في الكتاب.\n3. **ركز** على المسائل اللي فيها رسم بياني.\n\n> نصيحة: اكتب القوانين في ورقة واحدة وراجعها قبل الامتحان."
                : "في الدرس ده هنشرح **{$title}** بالتفصيل مع أمثلة محلولة.\n\nبعد ما تخلص الفيديو حاول تحل المسائل في آخر الكتاب.";
        }

        return $isText
            ? "## {$title}\n\nKey points from this chapter:\n\n1. Review the definitions.\n2. Redo the worked examples.\n3. **Focus** on the problems with graphs.\n\n> Tip: keep a one-page formula sheet and review it before the exam."
            : "In this lesson we cover **{$title}** step by step, with worked examples.\n\nAfter the video, try the practice problems at the end of the chapter.";
    }
}

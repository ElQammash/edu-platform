<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\ShowAnswers;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuizAttemptService;
use Illuminate\Database\Seeder;

/**
 * Sample quizzes covering each case: auto-graded, essays waiting for the
 * teacher, past due (missing work counts as 0) and one nobody took yet.
 * Lessons that already have a quiz are skipped.
 */
class DemoQuizSeeder extends Seeder
{
    public function run(QuizAttemptService $attempts): void
    {
        $student1 = User::where('email', 'student1@edu-platform.test')->firstOrFail();
        $student3 = User::where('email', 'student3@edu-platform.test')->firstOrFail();

        // Physics: auto-graded; student1 took it (2 of 3 right).
        $current = $this->quiz('physics-secondary-3', 'التيار الكهربي وشدته', [
            'title' => 'اختبار سريع على التيار الكهربي',
            'description' => 'ثلاث أسئلة سريعة. عندك ١٠ دقايق.',
            'time_limit_minutes' => 10,
            'pass_score' => 60,
        ], [
            [QuestionType::Mcq, 'وحدة قياس شدة التيار الكهربي هي:', 2, ['الأمبير' => true, 'الفولت' => false, 'الأوم' => false, 'الوات' => false]],
            [QuestionType::Mcq, 'لو مرت شحنة ١٢ كولوم في ٤ ثواني، تبقى شدة التيار:', 2, ['٣ أمبير' => true, '٤٨ أمبير' => false, '٠٫٣٣ أمبير' => false]],
            [QuestionType::TrueFalse, 'التيار الكهربي بيمشي في الدائرة المفتوحة.', 1, false],
        ]);
        if ($current) {
            $attempt = $attempts->start($student1, $current);
            $questions = $current->questions()->with('options')->get();
            $attempts->saveAnswers($attempt, [
                ['question_id' => $questions[0]->id, 'option_id' => $this->option($questions[0], true)],
                ['question_id' => $questions[1]->id, 'option_id' => $this->option($questions[1], false)],
                ['question_id' => $questions[2]->id, 'option_id' => $this->option($questions[2], true)],
            ]);
            $attempts->submit($attempt);
        }

        // Physics: has an essay; student3 submitted, waiting for the teacher.
        // Answers show after the due date.
        $ohm = $this->quiz('physics-secondary-3', 'قانون أوم', [
            'title' => 'اختبار قانون أوم',
            'time_limit_minutes' => 20,
            'due_at' => now()->addDays(3)->setTime(23, 59),
            'show_answers' => ShowAnswers::AfterDueDate,
        ], [
            [QuestionType::Mcq, 'مقاومة ٤ أوم ومرّ فيها تيار ٣ أمبير، فرق الجهد يساوي:', 2, ['١٢ فولت' => true, '٧ فولت' => false, '١٫٣ فولت' => false]],
            [QuestionType::Essay, 'اشرح بأسلوبك العلاقة بين فرق الجهد وشدة التيار عند ثبوت درجة الحرارة.', 8, null],
        ]);
        if ($ohm) {
            $attempt = $attempts->start($student3, $ohm);
            $questions = $ohm->questions()->with('options')->get();
            $attempts->saveAnswers($attempt, [
                ['question_id' => $questions[0]->id, 'option_id' => $this->option($questions[0], true)],
                ['question_id' => $questions[1]->id, 'text_answer' => 'فرق الجهد بيتناسب طردياً مع شدة التيار طول ما المقاومة ودرجة الحرارة ثابتين.'],
            ]);
            $attempts->submit($attempt);
        }

        // Calculus: past due and student2 never took it, so it counts as 0.
        $this->quiz('calculus-secondary-3', 'النهايات والاتصال', [
            'title' => 'اختبار النهايات',
            'time_limit_minutes' => 15,
            'due_at' => now()->subDays(2)->setTime(23, 59),
        ], [
            [QuestionType::Mcq, 'نهاية (س² − ١) ÷ (س − ١) عندما س ← ١ تساوي:', 5, ['٢' => true, '٠' => false, '١' => false, 'غير موجودة' => false]],
            [QuestionType::TrueFalse, 'كل دالة متصلة عند نقطة لها نهاية عند هذه النقطة.', 5, true],
        ]);

        // Algebra: shuffled, two attempts, untimed, no due date. Nobody took it yet.
        $this->quiz('algebra-foundations', 'Variables and expressions', [
            'title' => 'Expressions check',
            'description' => 'Untimed. You have two attempts and your best one counts.',
            'time_limit_minutes' => null,
            'max_attempts' => 2,
            'shuffle' => true,
            'pass_score' => 70,
        ], [
            [QuestionType::Mcq, 'Simplify 3x + 2x.', 1, ['5x' => true, '6x' => false, '5x²' => false, 'x⁵' => false]],
            [QuestionType::Mcq, 'What is the value of 2a − b when a = 4 and b = 3?', 1, ['5' => true, '1' => false, '11' => false]],
            [QuestionType::TrueFalse, '4(x + 1) = 4x + 4', 1, true],
            [QuestionType::Essay, 'In one or two sentences, explain what a variable is.', 2, null],
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<array{QuestionType, string, int, array<array-key, bool>|bool|null}>  $questions  type, body, points, options (MCQ) / correct answer (true/false)
     */
    private function quiz(string $courseSlug, string $lessonTitle, array $settings, array $questions): ?Quiz
    {
        $course = Course::where('slug', $courseSlug)->firstOrFail();
        $lesson = Lesson::query()
            ->whereIn('section_id', $course->sections()->select('id'))
            ->where('title', $lessonTitle)
            ->firstOrFail();

        if ($lesson->quiz()->exists()) {
            return null;
        }

        $quiz = $lesson->quiz()->create($settings);

        foreach ($questions as $order => [$type, $body, $points, $answer]) {
            $question = $quiz->questions()->create(['type' => $type, 'body' => $body, 'points' => $points, 'order' => $order + 1]);

            $options = match ($type) {
                QuestionType::Mcq => is_array($answer) ? $answer : [],
                QuestionType::TrueFalse => [QuestionOption::TRUE => $answer === true, QuestionOption::FALSE => $answer === false],
                QuestionType::Essay => [],
            };

            $i = 0;
            foreach ($options as $optionBody => $isCorrect) {
                $question->options()->create(['body' => (string) $optionBody, 'is_correct' => $isCorrect, 'order' => $i++]);
            }
        }

        return $quiz;
    }

    private function option(Question $question, bool $correct): int
    {
        return (int) $question->options->firstWhere('is_correct', $correct)?->id;
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\GradeCalculator;
use App\Support\Grades\ComponentGrade;
use App\Support\Grades\CourseGrade;
use App\Support\Grades\LessonGrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradesController extends Controller
{
    /**
     * GET /me/grades: for each enrolled course, the lesson grades, the course
     * grade and how much assessed work is done.
     */
    public function index(Request $request, GradeCalculator $calculator, AttendanceService $attendance): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $courses = $user->enrolledCourses()->orderBy('enrollments.enrolled_at')->get()
            ->map(fn ($course) => [
                ...$this->course($calculator->forCourse($user, $course)),
                // Shown next to the grades; it does not change them.
                'attendance' => $attendance->summary($user, $course),
            ])
            ->values();

        return response()->json(['data' => $courses]);
    }

    /**
     * @return array<string, mixed>
     */
    private function course(CourseGrade $grade): array
    {
        return [
            'course' => ['id' => $grade->course->id, 'slug' => $grade->course->slug, 'title' => $grade->course->title],
            'percent' => $grade->percent,
            'done' => $grade->done,
            'total' => $grade->total,
            'lessons' => array_map(fn (LessonGrade $lesson) => [
                'lesson' => ['id' => $lesson->lesson->id, 'title' => $lesson->lesson->title],
                'percent' => $lesson->percent,
                'done' => $lesson->isDone(),
                'assignment' => $lesson->assignment ? $this->component($lesson->assignment, $lesson->lesson->assignment?->id, $lesson->lesson->assignment?->title) : null,
                'quiz' => $lesson->quiz ? $this->component($lesson->quiz, $lesson->lesson->quiz?->id, $lesson->lesson->quiz?->title) : null,
            ], $grade->lessons),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function component(ComponentGrade $grade, ?int $id, ?string $title): array
    {
        return ['id' => $id, 'title' => $title, ...$grade->toArray()];
    }
}

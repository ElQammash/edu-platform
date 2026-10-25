<?php

namespace App\Http\Resources\V1;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Support\VideoUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full lesson for the player. Only returned after LessonPolicy::view.
 *
 * @mixin Lesson
 */
class LessonResource extends JsonResource
{
    /**
     * @param  array{previous: int|null, next: int|null}  $neighbours
     */
    public function __construct(Lesson $lesson, private readonly array $neighbours = ['previous' => null, 'next' => null])
    {
        parent::__construct($lesson);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // A live lesson's recording plays like a video once the session has ended.
        $video = VideoUrl::parse($this->liveState() === Lesson::LIVE_ENDED ? $this->recording_url : $this->video_url);
        $course = $this->section->course;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'content' => $this->content,
            'video' => $video ? [
                'provider' => $video->provider,
                'video_id' => $video->videoId,
                'embed_url' => $video->embedUrl,
            ] : null,
            'duration_seconds' => $this->duration_seconds,
            'is_free_preview' => $this->is_free_preview,
            // Never the meeting link itself: that comes from POST /lessons/{id}/join,
            // inside the join window, so it cannot be copied from the page early.
            'live' => $this->isLive() ? [
                'starts_at' => $this->live_at?->toIso8601String(),
                'ends_at' => $this->liveEndsAt()?->toIso8601String(),
                'join_opens_at' => $this->joinOpensAt()?->toIso8601String(),
                'state' => $this->liveState(),
                'can_join' => $this->canJoinNow(),
                'has_recording' => filled($this->recording_url),
                'server_time' => now()->toIso8601String(),
            ] : null,
            'section' => ['id' => $this->section->id, 'title' => $this->section->title],
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title],
            'assignment' => $this->whenLoaded('assignment', fn () => $this->assignment
                ? new AssignmentSummaryResource($this->assignment)
                : null),
            'quiz' => $this->whenLoaded('quiz', fn () => $this->quiz
                ? new QuizSummaryResource($this->quiz)
                : null),
            'previous_lesson_id' => $this->neighbours['previous'],
            'next_lesson_id' => $this->neighbours['next'],
            'progress' => $this->whenLoaded('progress', function () {
                $progress = $this->progress->first();

                return $progress instanceof LessonProgress ? new LessonProgressResource($progress) : null;
            }),
        ];
    }
}

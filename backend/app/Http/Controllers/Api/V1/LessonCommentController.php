<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lessons\PostCommentRequest;
use App\Http\Resources\V1\LessonCommentResource;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use App\Services\LessonQaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * A lesson's Q&A: questions from enrolled students, replies from anyone in
 * the course, answers accepted by the asker or the teacher.
 */
class LessonCommentController extends Controller
{
    private const AUTHOR = ['user.teacherProfile', 'user.studentProfile'];

    public function __construct(private readonly LessonQaService $qa) {}

    /**
     * GET /lessons/{id}/comments?filter=unresolved|mine: questions, newest
     * first, each with all its replies.
     */
    public function index(Request $request, Lesson $lesson): AnonymousResourceCollection
    {
        Gate::authorize('participate', [LessonComment::class, $lesson]);

        $filter = $request->validate(['filter' => ['nullable', Rule::in(['unresolved', 'mine'])]])['filter'] ?? null;

        /** @var User $user */
        $user = $request->user();

        $questions = $lesson->comments()
            ->withTrashed()
            ->whereNull('parent_id')
            // A deleted question stays only while it has replies.
            ->where(fn (Builder $q) => $q->whereNull('deleted_at')->orWhereHas('replies'))
            ->when($filter === 'unresolved', fn (Builder $q) => $q->whereNull('resolved_at'))
            ->when($filter === 'mine', fn (Builder $q) => $q->where('user_id', $user->id))
            ->with([...self::AUTHOR, 'replies' => fn ($q) => $q->with(self::AUTHOR)])
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // The policy checks walk up to the course; share the loaded lesson.
        foreach ($questions as $question) {
            $this->attach($question, $lesson);
        }

        return LessonCommentResource::collection($questions)
            ->additional(['meta' => ['can_ask' => $user->can('ask', [LessonComment::class, $lesson])]]);
    }

    /**
     * POST /lessons/{id}/comments: ask a question.
     */
    public function store(PostCommentRequest $request, Lesson $lesson): JsonResponse
    {
        Gate::authorize('ask', [LessonComment::class, $lesson]);

        /** @var User $user */
        $user = $request->user();
        $question = $this->qa->ask($user, $lesson, $request->body());

        return $this->respond($question, $lesson, 201);
    }

    /**
     * POST /comments/{id}/replies: reply to a question.
     */
    public function reply(PostCommentRequest $request, LessonComment $comment): JsonResponse
    {
        abort_unless($comment->isQuestion(), 404);
        Gate::authorize('reply', $comment);

        /** @var User $user */
        $user = $request->user();
        $reply = $this->qa->reply($user, $comment, $request->body());
        $reply->setRelation('parent', $comment);

        return $this->respond($reply, $comment->lesson, 201);
    }

    /**
     * PATCH /comments/{id}: the author fixes their post.
     */
    public function update(PostCommentRequest $request, LessonComment $comment): JsonResponse
    {
        Gate::authorize('update', $comment);

        $this->qa->edit($comment, $request->body());

        return $this->respond($comment, $comment->lesson);
    }

    public function destroy(LessonComment $comment): Response
    {
        Gate::authorize('delete', $comment);

        $this->qa->delete($comment);

        return response()->noContent();
    }

    public function resolve(LessonComment $comment): JsonResponse
    {
        Gate::authorize('resolve', $comment);

        return $this->respond($this->qa->resolve($comment), $comment->lesson);
    }

    public function reopen(LessonComment $comment): JsonResponse
    {
        Gate::authorize('resolve', $comment);

        return $this->respond($this->qa->reopen($comment), $comment->lesson);
    }

    /**
     * POST /comments/{id}/accept: pick a reply as the answer. Returns the question.
     */
    public function accept(Request $request, LessonComment $comment): JsonResponse
    {
        Gate::authorize('accept', $comment);

        /** @var User $user */
        $user = $request->user();

        return $this->respond($this->qa->accept($user, $comment), $comment->lesson);
    }

    private function respond(LessonComment $comment, Lesson $lesson, int $status = 200): JsonResponse
    {
        $comment->load(self::AUTHOR);
        if ($comment->isQuestion()) {
            $comment->load(['replies' => fn ($q) => $q->with(self::AUTHOR)]);
        }

        $this->attach($comment, $lesson);

        return (new LessonCommentResource($comment))->response()->setStatusCode($status);
    }

    private function attach(LessonComment $comment, Lesson $lesson): void
    {
        $comment->setRelation('lesson', $lesson);

        if ($comment->relationLoaded('replies')) {
            foreach ($comment->replies as $reply) {
                $reply->setRelation('lesson', $lesson)->setRelation('parent', $comment);
            }
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NotificationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * GET /me/notifications: the latest 20, plus the unread count.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return NotificationResource::collection($user->notifications()->latest()->limit(20)->get())
            ->additional(['meta' => ['unread_count' => $user->unreadNotifications()->count()]]);
    }

    public function markRead(Request $request, string $notification): Response
    {
        /** @var User $user */
        $user = $request->user();

        // Scoped to the user's own notifications: someone else's id is a 404.
        $user->notifications()->findOrFail($notification)->markAsRead();

        return response()->noContent();
    }

    public function markAllRead(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}

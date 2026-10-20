<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The notifications dropdown. The list is a background JSON read, loaded when the dropdown opens;
 * opening one notification is a normal visit, since it leads to another page anyway.
 */
class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'notifications' => $this->notificationService->latest($userId),
            'unread' => $this->notificationService->unreadCount($userId),
        ]);
    }

    // Only opening a notification marks it read, not merely seeing it in the list.
    public function open(Request $request, string $notificationId): RedirectResponse
    {
        $url = $this->notificationService->open($request->user()->id, $notificationId);

        return $url ? redirect($url) : back();
    }

    public function readAll(Request $request): JsonResponse
    {
        $this->notificationService->markAllRead($request->user()->id);

        return response()->json(['unread' => 0]);
    }
}

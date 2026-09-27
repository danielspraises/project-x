<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function open(string $id): RedirectResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return redirect(
            $notification->data['target_url']
                ?? $notification->data['url']
                ?? route('notifications.index')
        );
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function markAllRead(): RedirectResponse
    {
        Auth::user()
            ->unreadNotifications
            ->markAsRead();

        return redirect()->back();
    }
}
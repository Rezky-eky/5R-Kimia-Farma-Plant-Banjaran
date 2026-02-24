<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $unreadNotificationsCount = 0;

        if ($user) {
            $unreadNotificationsCount = \App\Models\Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->only(['id', 'name', 'npp', 'role', 'bagian']), [
                    'points_balance' => (int) ($user->points_balance ?? 0),
                ]) : null,
            ],
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ];
    }
}

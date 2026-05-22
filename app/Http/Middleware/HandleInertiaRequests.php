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
                    'can_manage_go_check' => $user->canManageGoCheck(),
                    'can_go_check_finder' => $user->canActAsGoCheckFinder(),
                ]) : null,
            ],
            'unreadNotificationsCount' => $unreadNotificationsCount,
            // Flash session (redirect()->with('success'|'error')) agar toast Inertia tampil.
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}

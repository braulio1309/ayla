<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Inertia\Inertia;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(3);

        Inertia::share('auth', function () {
            $user = Auth::user();
            if (! $user) {
                return ['user' => null, 'notifications' => []];
            }

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'notifications' => $user->notifications->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'message' => $notification->data['message'] ?? '',
                    ];
                })->values()->all(),
            ];
        });
    }
}

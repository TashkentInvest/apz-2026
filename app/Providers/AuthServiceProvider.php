<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Application::class => ApplicationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Shartnoma, reja-jadval, talabnoma va ilovalarni tahrirlash faqat admin roliga ruxsat etiladi
        Gate::define('edit-apz-contract', fn (User $user) => $user->isAdmin());
    }
}

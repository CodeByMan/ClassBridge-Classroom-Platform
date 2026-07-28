<?php

namespace App\Providers;

use App\Models\PostUser;
use App\Policies\PostUserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        PostUser::class => PostUserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

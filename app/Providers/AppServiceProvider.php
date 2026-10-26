<?php

namespace App\Providers;

use App\Models\Classroom;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Observers\StudentEnrollmentObserver;
use App\Policies\AccountPolicy;
use App\Policies\ClassroomPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Classroom::class, ClassroomPolicy::class);
        Gate::policy(User::class, AccountPolicy::class);

        StudentEnrollment::observe(StudentEnrollmentObserver::class);
    }
}

<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(\App\Services\BranchContext::class);
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::ignoreRoutes();
        Passport::enablePasswordGrant();

        // Lifetimes live in config/passport.php (override in .env)
        $life = config('passport.lifetimes');
        Passport::tokensExpireIn(Carbon::now()->addMinutes($life['access_minutes'] ?? 60));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays($life['refresh_days'] ?? 30));
        Passport::personalAccessTokensExpireIn(Carbon::now()->addHours($life['personal_hours'] ?? 12));

        // Password rule for sign-up and "set a new password"
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        Builder::macro('getPaginate', function ($limit = 15, $columns = ['*'], $pageName = 'page', $page = null) {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            $limit = (int) ($limit ?: 15);

            if ($limit === -1) {
                // getCountForPagination() correctly computes totals on queries with groupBy()
                $total = (clone $this)->getCountForPagination();
                $perPage = max($total, 1);
            } else {
                $perPage = $limit > 0 ? $limit : 15;
            }

            return $this->paginate($perPage, $columns, $pageName, $page);
        });
    }
}

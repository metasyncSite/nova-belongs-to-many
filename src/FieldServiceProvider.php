<?php

declare(strict_types=1);

namespace MetasyncSite\NovaBelongsToMany;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Nova::serving(function (ServingNova $event) {
            Nova::script('has-many-searchable', __DIR__.'/../dist/js/field.js');
            Nova::style('has-many-searchable', __DIR__.'/../dist/css/field.css');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {}
}

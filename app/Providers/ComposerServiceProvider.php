<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\ViewComposers\CategoryComposer;
use App\Http\ViewComposers\UserComposer;
use App\Http\ViewComposers\ProvinceComposer;
use App\Http\ViewComposers\NodeComposer;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composers([
            CategoryComposer::class => [
                'index.index.index',
                'index.index-master'
            ],
            UserComposer::class => [
                'master',
                'index.master',
                'index.index.index',
                'index.index-top',
                'index.menu-master',
                'index.index-control',
                'index.personal.tabs'
            ],
            ProvinceComposer::class => [
                'index.index-top',
                'index.master'
            ],
            NodeComposer::class => [
                'admin.master'
            ]
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

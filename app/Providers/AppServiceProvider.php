<?php

namespace App\Providers;

use App\Services\AI\AiManager;
use App\Services\AI\AiProviderInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 註冊 AiManager 單例
        $this->app->singleton(AiManager::class, function () {
            return new AiManager();
        });

        // 當注入 AiProviderInterface 時，依據設定取得對應 AI Provider
        $this->app->bind(AiProviderInterface::class, function ($app) {
            return $app->make(AiManager::class)->driver();
        });
    }

    public function boot(): void
    {
        //
    }
}

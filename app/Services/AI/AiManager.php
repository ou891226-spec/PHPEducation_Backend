<?php

namespace App\Services\AI;

use App\Services\AI\AiProviderInterface;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAiProvider;
use InvalidArgumentException;

class AiManager
{
    private array $providers = [];

    /**
     * 取得指定的 AI Provider 實例（未傳入名稱時，預設讀取 config('ai.default')）
     */
    public function driver(?string $providerName = null): AiProviderInterface
    {
        $providerName = $providerName ?: config('ai.default', 'gemini');

        if (!isset($this->providers[$providerName])) {
            $this->providers[$providerName] = $this->createProvider($providerName);
        }

        return $this->providers[$providerName];
    }

    /**
     * 建立對應的 Provider 物件
     */
    public function createProvider(string $providerName): AiProviderInterface
    {
        return match ($providerName) {
            'gemini' => new GeminiProvider(
                apiKey: config('services.gemini.api_key', config('ai.providers.gemini.api_key')),
                model: config('ai.providers.gemini.model')
            ),
            'openai' => new OpenAiProvider(
                apiKey: config('ai.providers.openai.api_key'),
                baseUrl: config('ai.providers.openai.base_url'),
                model: config('ai.providers.openai.model')
            ),
            default => throw new InvalidArgumentException("Invalid AI provider: {$providerName}"),
        };
    }

    /**
     * 取得預設的 AI Provider 名稱
     */
    public function getDefaultProvider(): string
    {
        return config('ai.default', 'gemini');
    }
}

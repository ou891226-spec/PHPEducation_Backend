<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;
use App\Services\AI\Concerns\EvaluationPrompt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProviderInterface
{
    use EvaluationPrompt;

    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null, ?string $model = null)
    {
        $this->apiKey  = $apiKey ?: (string) config('ai.providers.openai.api_key', '');
        $this->baseUrl = rtrim($baseUrl ?: (string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/');
        $this->model   = $model ?: (string) config('ai.providers.openai.model', 'gpt-5.6-luna');
    }

    public function evaluate(
        string $question,
        string $rubric,
        string $referenceAnswer,
        string $studentAnswer,
        string $executionResult
    ): array {
        $prompt = $this->buildPrompt($question, $rubric, $referenceAnswer, $studentAnswer, $executionResult);

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => '你是一個專業的 PHP 程式設計課程批改助理，必須以嚴謹的 JSON 格式回傳評估結果。',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("OpenAI API 呼叫失敗: {$response->status()} - {$response->body()}");
        }

        $rawContent = $response->json('choices.0.message.content') ?? '';

        return $this->parseResponse($rawContent);
    }
}

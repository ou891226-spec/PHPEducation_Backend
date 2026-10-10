<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;
use App\Services\AI\Concerns\EvaluationPrompt;
use Gemini;

class GeminiProvider implements AiProviderInterface
{
    use EvaluationPrompt;

    private $client;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $apiKey = $apiKey ?: config('services.gemini.api_key', config('ai.providers.gemini.api_key'));
        $this->model = $model ?: config('ai.providers.gemini.model', 'gemini-2.5-flash');

        $this->client = Gemini::client($apiKey ?: 'dummy-key');
    }

    public function evaluate(
        string $question,
        string $rubric,
        string $referenceAnswer, 
        string $studentAnswer,
        string $executionResult,
    ): array {
        $prompt = $this->buildPrompt($question, $rubric, $referenceAnswer, $studentAnswer, $executionResult);

        $response = $this->client
            ->generativeModel(model: $this->model)
            ->generateContent($prompt);

        return $this->parseResponse($response->text());
    }
}

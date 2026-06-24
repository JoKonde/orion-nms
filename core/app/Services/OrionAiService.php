<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client OpenRouter — chat completions pour ORION AI.
 */
class OrionAiService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Tu es ORION AI, assistant du NMS ORION (supervision reseau).
Reponds toujours en francais, de facon claire et actionnable.
Utilise UNIQUEMENT les donnees du contexte JSON fourni — ne invente pas de metriques, IP ou statuts.
Structure tes reponses avec des sections courtes : Resume, Causes probables, Actions recommandees (etapes numerotees).
Si une information manque, dis-le explicitement.
PROMPT;

    public function isEnabled(): bool
    {
        return (bool) config('orion.ai.enabled')
            && ! empty(config('orion.ai.api_key'));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages): string
    {
        $this->assertEnabled();

        $payload = [
            'model' => config('orion.ai.model'),
            'messages' => array_merge(
                [['role' => 'system', 'content' => self::SYSTEM_PROMPT]],
                $messages,
            ),
            'max_tokens' => (int) config('orion.ai.max_tokens'),
            'temperature' => 0.3,
        ];

        return $this->requestWithResilience($payload);
    }

    public function chatWithContext(string $userMessage, array $context, ?array $history = null): string
    {
        $contextBlock = "Contexte ORION (JSON):\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $messages = [];

        if ($history) {
            foreach (array_slice($history, -8) as $turn) {
                if (! empty($turn['role']) && ! empty($turn['content'])) {
                    $messages[] = [
                        'role' => $turn['role'],
                        'content' => (string) $turn['content'],
                    ];
                }
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $contextBlock."\n\nQuestion utilisateur:\n".$userMessage,
        ];

        return $this->chat($messages);
    }

    public function analyzeAlert(array $context): string
    {
        return $this->chatWithContext(
            "Analyse cette alerte. Donne : 1) Resume 2) Causes probables 3) Actions concretes pour l'admin reseau.",
            $context,
        );
    }

    public function analyzeIncident(array $context): string
    {
        return $this->chatWithContext(
            "Analyse cet incident. Donne : 1) Resume 2) Causes probables 3) Plan d'intervention pas a pas 4) Priorite suggeree.",
            $context,
        );
    }

    public function proactiveAlertAnalysis(array $context): string
    {
        return $this->chatWithContext(
            "Alerte CRITIQUE detectee automatiquement. Produis un brief urgent pour l'admin : resume, gravite, 3 actions immediates.",
            $context,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requestWithResilience(array $payload): string
    {
        $primary = (string) ($payload['model'] ?? config('orion.ai.model'));
        $fallback = (string) config('orion.ai.fallback_model', 'openrouter/free');
        $models = array_values(array_unique(array_filter([$primary, $fallback])));

        $lastError = null;

        foreach ($models as $index => $model) {
            $attemptPayload = array_merge($payload, ['model' => $model]);

            for ($attempt = 0; $attempt < 2; $attempt++) {
                try {
                    return $this->sendRequest($attemptPayload);
                } catch (RuntimeException $e) {
                    $lastError = $e;

                    if ($attempt === 0 && $this->shouldRetry($e)) {
                        sleep($this->retryDelaySeconds($e));
                        continue;
                    }

                    break;
                }
            }
        }

        throw $lastError ?? new RuntimeException('OpenRouter: echec inconnu.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendRequest(array $payload): string
    {
        $endpoint = $this->resolveEndpoint();

        try {
            $response = Http::timeout(90)
                ->withHeaders([
                    'Authorization' => 'Bearer '.config('orion.ai.api_key'),
                    'HTTP-Referer' => config('app.url'),
                    'X-OpenRouter-Title' => config('app.name', 'ORION NMS'),
                ])
                ->post($endpoint, $payload)
                ->throw();
        } catch (RequestException $e) {
            $retryAfter = (int) ($e->response?->json('error.metadata.retry_after_seconds') ?? 0);
            throw new RuntimeException(
                'OpenRouter: '.$this->formatApiError($e->response),
                $retryAfter,
                $e,
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenRouter: reponse vide.');
        }

        return trim($content);
    }

    private function formatApiError(?Response $response): string
    {
        if (! $response) {
            return 'erreur reseau';
        }

        $body = $response->json();
        $error = $body['error'] ?? [];
        $metadata = $error['metadata'] ?? [];
        $raw = $metadata['raw'] ?? null;
        $provider = $metadata['provider_name'] ?? null;

        if (is_string($raw) && $raw !== '') {
            return $provider ? "[{$provider}] {$raw}" : $raw;
        }

        return (string) ($error['message'] ?? 'erreur '.$response->status());
    }

    private function shouldRetry(RuntimeException $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, 'rate-limited')
            || str_contains($msg, '429')
            || str_contains($msg, 'retry shortly');
    }

    private function retryDelaySeconds(RuntimeException $e): int
    {
        $code = $e->getCode();
        if ($code > 0) {
            return max(1, $code);
        }

        return 3;
    }

    private function resolveEndpoint(): string
    {
        $base = rtrim((string) config('orion.ai.base_url'), '/');

        if (str_ends_with($base, '/chat/completions')) {
            return $base;
        }

        return $base.'/chat/completions';
    }

    private function assertEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('ORION AI desactive ou cle OpenRouter manquante.');
        }
    }
}

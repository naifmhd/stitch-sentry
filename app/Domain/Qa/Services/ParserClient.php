<?php

namespace App\Domain\Qa\Services;

use DomainException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ParserClient
{
    /**
     * Tiny 1×1 transparent PNG used as placeholder in mock mode.
     * Raw binary of a valid minimal PNG file (68 bytes).
     *
     * @var string
     */
    private const MOCK_PNG = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde\x00\x00\x00\x0cIDATx\x9cc\xf8\x0f\x00\x00\x01\x01\x00\x05\x18\xd8N\x00\x00\x00\x00IEND\xaeB`\x82";

    /**
     * Parse an embroidery file and return metrics.
     *
     * @return array{
     *     width_mm: float,
     *     height_mm: float,
     *     stitch_count: int,
     *     color_changes: int,
     *     jump_count: int,
     *     longest_jump_mm: float,
     *     min_stitch_length_mm: float,
     *     max_stitch_length_mm: float,
     *     avg_stitch_length_mm: float,
     * }
     */
    public function parse(string $disk, string $storagePath): array
    {
        if (config('parser.mock_enabled')) {
            return $this->mockMetrics();
        }

        $response = $this->sendRequest('POST', '/parse', [
            'disk' => $disk,
            'path' => $storagePath,
        ]);

        return $response->json();
    }

    /**
     * Render a preview PNG for the given file.
     *
     * @return string Raw PNG bytes.
     */
    public function renderPreview(string $disk, string $storagePath): string
    {
        if (config('parser.mock_enabled')) {
            return self::MOCK_PNG;
        }

        return $this->sendRequest('POST', '/render/preview', [
            'disk' => $disk,
            'path' => $storagePath,
        ])->body();
    }

    /**
     * Render a density map PNG for the given file.
     *
     * @return string Raw PNG bytes.
     */
    public function renderDensity(string $disk, string $storagePath): string
    {
        if (config('parser.mock_enabled')) {
            return self::MOCK_PNG;
        }

        return $this->sendRequest('POST', '/render/density', [
            'disk' => $disk,
            'path' => $storagePath,
        ])->body();
    }

    /**
     * Render a jumps map PNG for the given file.
     *
     * @return string Raw PNG bytes.
     */
    public function renderJumps(string $disk, string $storagePath): string
    {
        if (config('parser.mock_enabled')) {
            return self::MOCK_PNG;
        }

        return $this->sendRequest('POST', '/render/jumps', [
            'disk' => $disk,
            'path' => $storagePath,
        ])->body();
    }

    /**
     * Build the HMAC-SHA256 signature string for a request.
     *
     * Signature covers: "{timestamp}.{METHOD}.{path}.{body_sha256}"
     */
    public function buildSignature(int $timestamp, string $method, string $path, string $body): string
    {
        $bodyHash = hash('sha256', $body);
        $message = implode('.', [$timestamp, strtoupper($method), $path, $bodyHash]);

        return hash_hmac('sha256', $message, config('parser.secret'));
    }

    /**
     * Send a signed JSON request to the parser service.
     *
     * @throws DomainException on non-2xx response.
     */
    private function sendRequest(string $method, string $path, array $payload): \Illuminate\Http\Client\Response
    {
        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DomainException('Failed to encode request payload.', 0, $e);
        }

        $timestamp = time();
        $signature = $this->buildSignature($timestamp, $method, $path, $body);

        $baseUrl = rtrim((string) config('parser.base_url'), '/');
        $url = $baseUrl.$path;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-SS-Timestamp' => (string) $timestamp,
            'X-SS-Signature' => $signature,
        ])
            ->timeout(config('parser.timeout_seconds'))
            ->connectTimeout(config('parser.connect_timeout_seconds'))
            ->retry(config('parser.retry_times'), config('parser.retry_sleep_ms'))
            ->withBody($body, 'application/json')
            ->send($method, $url);

        if (! $response->successful()) {
            Log::error('ParserClient: non-2xx response', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new DomainException('Parser service returned an error. Please try again later.');
        }

        return $response;
    }

    /**
     * Return deterministic fake metrics for mock mode.
     *
     * @return array{
     *     width_mm: float,
     *     height_mm: float,
     *     stitch_count: int,
     *     color_changes: int,
     *     jump_count: int,
     *     longest_jump_mm: float,
     *     min_stitch_length_mm: float,
     *     max_stitch_length_mm: float,
     *     avg_stitch_length_mm: float,
     * }
     */
    private function mockMetrics(): array
    {
        return [
            'width_mm' => 95.4,
            'height_mm' => 82.1,
            'stitch_count' => 12450,
            'color_changes' => 5,
            'jump_count' => 87,
            'longest_jump_mm' => 9.2,
            'min_stitch_length_mm' => 0.4,
            'max_stitch_length_mm' => 12.0,
            'avg_stitch_length_mm' => 3.1,
        ];
    }
}

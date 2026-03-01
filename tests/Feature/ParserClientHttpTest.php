<?php

use App\Domain\Qa\Services\ParserClient;
use Illuminate\Support\Facades\Http;

// ──────────────────────────────────────────────
// ParserClient — HTTP mode (Http::fake)
// ──────────────────────────────────────────────

test('parse sends correct JSON payload with disk and path', function () {
    config([
        'parser.mock_enabled' => false,
        'parser.base_url' => 'https://parser.example.com',
        'parser.secret' => 'signing-secret',
        'parser.timeout_seconds' => 20,
        'parser.connect_timeout_seconds' => 5,
        'parser.retry_times' => 1,
        'parser.retry_sleep_ms' => 0,
    ]);

    Http::fake([
        'https://parser.example.com/parse' => Http::response([
            'width_mm' => 100.0,
            'height_mm' => 80.0,
            'stitch_count' => 5000,
            'color_changes' => 3,
            'jump_count' => 50,
            'longest_jump_mm' => 8.0,
            'min_stitch_length_mm' => 0.5,
            'max_stitch_length_mm' => 10.0,
            'avg_stitch_length_mm' => 3.0,
        ], 200),
    ]);

    $client = new ParserClient;
    $client->parse('s3', 'designs/file.dst');

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $request->url() === 'https://parser.example.com/parse'
            && $body['disk'] === 's3'
            && $body['path'] === 'designs/file.dst';
    });
});

test('parse sets X-SS-Timestamp and X-SS-Signature headers', function () {
    config([
        'parser.mock_enabled' => false,
        'parser.base_url' => 'https://parser.example.com',
        'parser.secret' => 'signing-secret',
        'parser.timeout_seconds' => 20,
        'parser.connect_timeout_seconds' => 5,
        'parser.retry_times' => 1,
        'parser.retry_sleep_ms' => 0,
    ]);

    Http::fake([
        'https://parser.example.com/parse' => Http::response(['width_mm' => 1.0], 200),
    ]);

    $client = new ParserClient;
    $client->parse('s3', 'designs/file.dst');

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-SS-Timestamp')
            && $request->hasHeader('X-SS-Signature')
            && is_numeric($request->header('X-SS-Timestamp')[0])
            && strlen($request->header('X-SS-Signature')[0]) === 64; // 64 hex chars = SHA256
    });
});

test('signature in header matches expected value for the request body', function () {
    config([
        'parser.mock_enabled' => false,
        'parser.base_url' => 'https://parser.example.com',
        'parser.secret' => 'my-secret',
        'parser.timeout_seconds' => 20,
        'parser.connect_timeout_seconds' => 5,
        'parser.retry_times' => 1,
        'parser.retry_sleep_ms' => 0,
    ]);

    Http::fake([
        'https://parser.example.com/parse' => Http::response(['width_mm' => 1.0], 200),
    ]);

    $client = new ParserClient;
    $client->parse('s3', 'designs/test.dst');

    Http::assertSent(function ($request) use ($client) {
        $timestamp = (int) $request->header('X-SS-Timestamp')[0];
        $signature = $request->header('X-SS-Signature')[0];
        $expected = $client->buildSignature($timestamp, 'POST', '/parse', $request->body());

        return $signature === $expected;
    });
});

test('parse throws DomainException on non-2xx response', function () {
    config([
        'parser.mock_enabled' => false,
        'parser.base_url' => 'https://parser.example.com',
        'parser.secret' => 'secret',
        'parser.timeout_seconds' => 20,
        'parser.connect_timeout_seconds' => 5,
        'parser.retry_times' => 1,
        'parser.retry_sleep_ms' => 0,
    ]);

    Http::fake([
        'https://parser.example.com/parse' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    $client = new ParserClient;

    expect(fn () => $client->parse('s3', 'designs/bad.dst'))
        ->toThrow(DomainException::class, 'Parser service returned an error');
});

test('renderPreview sends correct payload and returns body', function () {
    config([
        'parser.mock_enabled' => false,
        'parser.base_url' => 'https://parser.example.com',
        'parser.secret' => 'secret',
        'parser.timeout_seconds' => 20,
        'parser.connect_timeout_seconds' => 5,
        'parser.retry_times' => 1,
        'parser.retry_sleep_ms' => 0,
    ]);

    $pngBytes = "\x89PNG\r\n\x1a\nfake";

    Http::fake([
        'https://parser.example.com/render/preview' => Http::response($pngBytes, 200),
    ]);

    $client = new ParserClient;
    $result = $client->renderPreview('s3', 'designs/file.dst');

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return str_contains($request->url(), '/render/preview')
            && $body['disk'] === 's3'
            && $body['path'] === 'designs/file.dst';
    });

    expect($result)->toBe($pngBytes);
});

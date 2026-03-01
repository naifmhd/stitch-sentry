<?php

use App\Domain\Qa\Services\ParserClient;

uses(Tests\TestCase::class);

// ──────────────────────────────────────────────
// ParserClient — signature generation
// ──────────────────────────────────────────────

test('signature matches expected hmac for known inputs', function () {
    config(['parser.secret' => 'test-secret-key']);

    $client = new ParserClient;

    $timestamp = 1700000000;
    $method = 'POST';
    $path = '/parse';
    $body = '{"disk":"s3","path":"designs/file.dst"}';

    $bodyHash = hash('sha256', $body);
    $message = implode('.', [$timestamp, 'POST', '/parse', $bodyHash]);
    $expected = hash_hmac('sha256', $message, 'test-secret-key');

    expect($client->buildSignature($timestamp, $method, $path, $body))->toBe($expected);
});

test('signature method is uppercased automatically', function () {
    config(['parser.secret' => 'secret']);

    $client = new ParserClient;

    $timestamp = 1700000000;
    $body = '';

    $sigLower = $client->buildSignature($timestamp, 'post', '/parse', $body);
    $sigUpper = $client->buildSignature($timestamp, 'POST', '/parse', $body);

    expect($sigLower)->toBe($sigUpper);
});

test('body hash for empty body is sha256 of empty string', function () {
    config(['parser.secret' => 'secret']);

    $client = new ParserClient;

    $timestamp = 1700000000;
    $bodyHash = hash('sha256', '');
    $message = implode('.', [$timestamp, 'POST', '/parse', $bodyHash]);
    $expected = hash_hmac('sha256', $message, 'secret');

    expect($client->buildSignature($timestamp, 'POST', '/parse', ''))->toBe($expected);
});

test('body hash differs for different body content', function () {
    config(['parser.secret' => 'secret']);

    $client = new ParserClient;

    $timestamp = 1700000000;

    $sig1 = $client->buildSignature($timestamp, 'POST', '/parse', '{"disk":"s3","path":"a.dst"}');
    $sig2 = $client->buildSignature($timestamp, 'POST', '/parse', '{"disk":"s3","path":"b.dst"}');

    expect($sig1)->not->toBe($sig2);
});

// ──────────────────────────────────────────────
// ParserClient — mock mode
// ──────────────────────────────────────────────

test('parse returns expected keys and types in mock mode', function () {
    config(['parser.mock_enabled' => true]);

    $client = new ParserClient;
    $result = $client->parse('s3', 'designs/sample.dst');

    $floatKeys = ['width_mm', 'height_mm', 'longest_jump_mm', 'min_stitch_length_mm', 'max_stitch_length_mm', 'avg_stitch_length_mm'];
    $intKeys = ['stitch_count', 'color_changes', 'jump_count'];

    foreach ($floatKeys as $key) {
        expect($result)->toHaveKey($key)
            ->and($result[$key])->toBeFloat();
    }

    foreach ($intKeys as $key) {
        expect($result)->toHaveKey($key)
            ->and($result[$key])->toBeInt();
    }
});

test('parse returns deterministic values in mock mode', function () {
    config(['parser.mock_enabled' => true]);

    $client = new ParserClient;

    $result1 = $client->parse('s3', 'designs/file.dst');
    $result2 = $client->parse('local', 'designs/other.dst');

    expect($result1)->toBe($result2);
});

test('renderPreview returns non-empty string in mock mode', function () {
    config(['parser.mock_enabled' => true]);

    $client = new ParserClient;
    $png = $client->renderPreview('s3', 'designs/sample.dst');

    expect($png)->toBeString()
        ->and(strlen($png))->toBeGreaterThan(0)
        ->and(substr($png, 0, 4))->toBe("\x89PNG");
});

test('renderDensity returns non-empty string in mock mode', function () {
    config(['parser.mock_enabled' => true]);

    $client = new ParserClient;
    $png = $client->renderDensity('s3', 'designs/sample.dst');

    expect($png)->toBeString()
        ->and(strlen($png))->toBeGreaterThan(0)
        ->and(substr($png, 0, 4))->toBe("\x89PNG");
});

test('renderJumps returns non-empty string in mock mode', function () {
    config(['parser.mock_enabled' => true]);

    $client = new ParserClient;
    $png = $client->renderJumps('s3', 'designs/sample.dst');

    expect($png)->toBeString()
        ->and(strlen($png))->toBeGreaterThan(0)
        ->and(substr($png, 0, 4))->toBe("\x89PNG");
});

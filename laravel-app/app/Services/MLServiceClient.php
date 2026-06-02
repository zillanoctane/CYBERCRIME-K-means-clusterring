<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Klien HTTP untuk berkomunikasi dengan microservice FastAPI ML.
 *
 * Diinjeksikan via service container (lihat AppServiceProvider). Semua endpoint
 * mengembalikan array (decoded JSON) atau melempar ``MLServiceException``.
 */
class MLServiceClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 120,
    ) {}

    public function health(): array
    {
        return $this->http()->get('/health')->throw()->json();
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @param  array{numeric: list<string>, categorical: list<string>, scaler?: string}  $features
     */
    public function elbow(array $data, array $features, int $kMin = 2, int $kMax = 10): array
    {
        return $this->call('post', '/api/v1/elbow', [
            'data' => $data,
            'features' => $features,
            'k_min' => $kMin,
            'k_max' => $kMax,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @param  array{numeric: list<string>, categorical: list<string>, scaler?: string}  $features
     */
    public function cluster(array $data, array $features, int $nClusters, ?int $randomState = null): array
    {
        return $this->call('post', '/api/v1/cluster', [
            'data' => $data,
            'features' => $features,
            'n_clusters' => $nClusters,
            'random_state' => $randomState,
            'record_id_field' => 'id',
        ]);
    }

    private function call(string $method, string $path, array $payload): array
    {
        try {
            $response = $this->http()->{$method}($path, $payload);
        } catch (ConnectionException $e) {
            Log::error('ML service unreachable', ['error' => $e->getMessage(), 'path' => $path]);
            throw new RuntimeException(
                'Layanan analisis (ML service) tidak dapat dijangkau. Pastikan service FastAPI berjalan.',
                503,
                $e
            );
        }

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            Log::warning('ML service returned error', ['status' => $response->status(), 'detail' => $detail]);
            throw new RuntimeException(
                'Layanan analisis menolak request: '.(is_string($detail) ? $detail : json_encode($detail)),
                $response->status()
            );
        }

        return $response->json();
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'X-ML-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->retry(2, 1000, throw: false);
    }
}

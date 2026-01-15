<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FirebaseService {
    protected string $projectId;
    protected string $collectionPrefix;

    public function __construct() {
        $this->projectId = config('firebase.project_id');
        $this->collectionPrefix = config('firebase.collection_prefix', '');

        if (!$this->projectId) {
            throw new \Exception("Firebase configurações estão ausentes. Verifique seu .env e config/firebase.php");
        }
    }

    protected function buildUrl(string $path): string {
        return "https://{$this->projectId}-default-rtdb.firebaseio.com/{$path}.json";
    }

    public function updateLastModified(string $collection, string $empresaId): bool {
        $collection = $this->collectionPrefix . $collection;
        $url = $this->buildUrl("webhooks/{$collection}_{$empresaId}");
        $payload = ['last_updated' => now()->toIso8601String()];
        $response = Http::timeout(5)->patch($url, $payload);
        return $response->successful();
    }

    public function getLastModified(string $collection, string $empresaId): ?string {
        $collection = $this->collectionPrefix . $collection;
        $url = $this->buildUrl("webhooks/{$collection}_{$empresaId}");
        $response = Http::timeout(5)->get($url);
        $body = $response->json();
        if ($response->successful() && isset($body['last_updated'])) {
            return $body['last_updated'];
        }
        return null;
    }

    public function update(string $path, array $data): bool {
        $url = $this->buildUrl($path);
        $response = Http::timeout(5)->patch($url, $data);
        return $response->successful();
    }

    public function push(string $path, array $data): ?string {
        $url = $this->buildUrl($path);
        $response = Http::timeout(5)->post($url, $data);
        if ($response->successful()) {
            return $response->json()['name'] ?? null;
        }
        return null;
    }
}

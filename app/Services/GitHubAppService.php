<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

class GitHubAppService
{
    public function getInstallationToken(): string
    {
        $appId = config('services.github.app_id');
        $installationId = config('services.github.installation_id');
        $keyPath = base_path(config('services.github.private_key_path'));

        if (!file_exists($keyPath)) {
            throw new \Exception('Archivo PEM de GitHub App no encontrado');
        }

        $privateKey = file_get_contents($keyPath);

        $jwt = JWT::encode([
            'iat' => time() - 60,
            'exp' => time() + 600, // 10 minutos
            'iss' => $appId,
        ], $privateKey, 'RS256');

        $response = Http::withToken($jwt)
            ->acceptJson()
            ->post(
                "https://api.github.com/app/installations/{$installationId}/access_tokens"
            );

        if (!$response->successful()) {
            throw new \Exception('No se pudo obtener Installation Token de GitHub');
        }

        return $response->json('token');
    }
}

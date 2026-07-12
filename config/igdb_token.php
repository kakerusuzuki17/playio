<?php

function getIgdbAccessToken(): string
{
    $env = require __DIR__ . "/env.php";

    $clientId = $env["IGDB_CLIENT_ID"];
    $clientSecret = $env["IGDB_CLIENT_SECRET"];

    $cacheFile = __DIR__ . "/igdb_token_cache.json";

    // 既存トークンがまだ有効なら使う
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true);

        if (
            isset($cache["access_token"], $cache["expires_at"]) &&
            time() < $cache["expires_at"]
        ) {
            return $cache["access_token"];
        }
    }

    // 期限切れなら新しく取得
    $url = "https://id.twitch.tv/oauth2/token";

    $params = http_build_query([
        "client_id" => $clientId,
        "client_secret" => $clientSecret,
        "grant_type" => "client_credentials"
    ]);

    $ch = curl_init($url . "?" . $params);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data["access_token"])) {
        throw new Exception("IGDBアクセストークンの取得に失敗しました");
    }

    // 5分早めに期限切れ扱いにする
    $expiresAt = time() + $data["expires_in"] - 300;

    file_put_contents($cacheFile, json_encode([
        "access_token" => $data["access_token"],
        "expires_at" => $expiresAt
    ]));

    return $data["access_token"];
}
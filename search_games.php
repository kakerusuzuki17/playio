<?php
header('Content-Type: application/json; charset=utf-8');

$env = require __DIR__ . '/config/env.php';
$clientSecret = $env['IGDB_CLIENT_SECRET'];

$keyword = trim($_GET["q"] ?? "");
// 連続する半角・全角空白を1個の半角空白にする
$keyword = preg_replace('/[\s　]+/u', ' ', $keyword);

if (trim($keyword) === '') {
    echo json_encode([]);
    exit;
}

// アクセストークン取得
require __DIR__ . "/config/igdb_token.php";

$clientId = $env["IGDB_CLIENT_ID"];
$accessToken = getIgdbAccessToken();

$searchKeyword = json_encode(
    $keyword,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

// IGDB検索
$query = "
search {$searchKeyword};
fields name,cover.url,genres.name,first_release_date,category,version_parent;
limit 10;
";

$ch = curl_init('https://api.igdb.com/v4/games');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Client-ID: ' . $clientId,
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: text/plain'
    ],
    CURLOPT_POSTFIELDS => $query
]);

$response = curl_exec($ch);
curl_close($ch);

$games = json_decode($response, true);

// ゲーム一覧を返す
$result = [];

foreach ($games as $game) {

    // 通常ゲーム以外を除外
    if (($game["category"] ?? 0) != 0) {
        continue;
    }
    if (!empty($game["version_parent"])) {
        continue;
    }

    $image = null;

    if (isset($game['cover']['url'])) {
        $image = 'https:' . str_replace('t_thumb', 't_cover_big', $game['cover']['url']);
    }

    $genres = [];

    if (isset($game['genres'])) {
        foreach ($game['genres'] as $genre) {
            $genres[] = $genre['name'];
        }
    }

    $releaseDate = null;

    if (isset($game['first_release_date'])) {
        $released = date('Y-m-d', $game['first_release_date']);
    }

    $result[] = [
        'id' => $game['id'],
        'name' => $game['name'],
        'image' => $image,
        'genres' => $genres,
        'first_release_date' => $releaseDate
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
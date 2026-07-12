<?php
session_start();

// セッション変数をすべて削除
$_SESSION = [];

// セッションクッキーも削除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// セッションを破棄
session_destroy();

// ログイン画面へ
header("Location: login.php");
exit;
<?php
// ログイン処理
session_start();
require "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $account_id = $_POST["account_id"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE account_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$account_id]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        // セッション確認
        $_SESSION["id"] = $user["id"];                 // DBの主キー
        $_SESSION["account_id"] = $user["account_id"]; // ログイン用アカウントID
        $_SESSION["user_name"] = $user["user_name"];   // 表示名

        header("Location: ../home.php");
        exit;
    }

    $error = "ユーザーIDまたはパスワードが違います。";
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="login-container">

    <h1>Playio</h1>
    <p class="subtitle">ゲーム好きのためのSNS</p>

    <form action="" method="post">

        <input
            type="text"
            name="account_id"
            placeholder="アカウントID"
            pattern="[A-Za-z0-9_]+"
            title="半角英数字とアンダースコア(_)のみ使用できます。"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="パスワード"
            required
        >

        <button type="submit">
            ログイン
        </button>

    </form>

    <p>
        <a href="register.php">新規登録はこちら</a>
    </p>

</div>

</body>
</html>
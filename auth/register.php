<?php
// 新規登録処理
session_start();
require "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_name = trim($_POST["user_name"]);
    $account_id = trim($_POST["account_id"]);
    $password = $_POST["password"];

    // パスワードを暗号化
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{3,19}$/', $account_id)) {
        $error = "アカウントIDは英字で始まり、半角英数字とアンダースコア(_)のみ、4～20文字で入力してください。";
    }

    // アカウントIDの重複確認
    $sql = "SELECT id FROM users WHERE account_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$account_id]);

    if ($stmt->fetch()) {
        $error = "このアカウントIDは既に登録されています。";
    } else {

        // ユーザー登録
        $sql = "INSERT INTO users(user_name, account_id, password)
                VALUES(?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $user_name,
            $account_id,
            $hash
        ]);

        // 登録したユーザー情報を取得
        $user_id = $pdo->lastInsertId();

        // セッションに保存
        $_SESSION["account_id"] = $account_id;
        $_SESSION["user_name"] = $user_name;

        // ホームへ
        header("Location: ../home.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="login-container">

    <h1>Playio</h1>
    <p class="subtitle">ゲーム好きのためのSNS</p>

    <?php if (!empty($error)): ?>
    <p style="color:red;">
        <?= htmlspecialchars($error) ?> <!-- エラーメッセージ表示 -->
    </p>
    <?php endif; ?>

    <form action="" method="post">

        <input
            type="text"
            name="user_name"
            placeholder="ユーザー名"
            required
        >

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
            新規登録
        </button>

    </form>

</div>

</body>
</html>
<!-- 2271012 김다은 -->
<?php
include_once '../config.php';
session_start();

$error = "";

// 로그인 폼
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // username으로 유저 존재 여부 확인
    $stmt = $conn->prepare("SELECT * FROM User WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // username 존재
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['password'] === $password) {
            // 1) username O + password O → 로그인 성공
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: ../index.php");
            exit;

        } else {
            // 2) username O + password X → 비밀번호 틀림
            $error = "비밀번호가 틀렸습니다.";
        }

    } else {
        // 3) username X → 새로운 계정 생성 후, 자동 로그인
        $stmt_insert = $conn->prepare("INSERT INTO User (role, username, password) VALUES ('USER', ?, ?)");
        $stmt_insert->bind_param("ss", $username, $password);
        $stmt_insert->execute();

        $new_id = $conn->insert_id;
        $stmt_insert->close();

        $_SESSION['user_id'] = $new_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = "USER";

        header("Location: ../index.php");
        exit;
    }

    $stmt->close();
    }
$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
</head>
<style>
body {
    background: #ffffff;
    font-family: Arial, sans-serif;
}
.login-box {
    width: 400px;          
    margin: 40px auto;   
    padding: 40px;      
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    background: #fdf7ec;
}
.login-box input[type="text"],
.login-box input[type="password"] {
    width: 100%;
    padding: 14px;
    margin-bottom: 20px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 16px;
    box-sizing: border-box;
}
.login-box label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 16px;
}
.login-box input[type="submit"] {
    width: 100%;
    padding: 14px;
    border: none;
    background: #F47320;
    color: white;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}
.login-box input[type="submit"]:hover {
    background: #e0661b;
}
.error {
    color: #e0661b;
    margin-bottom: 50px;
    font-size: 20px;
    font-weight: 600;
    text-align: center;
}
</style>
</head>
<body>

<?php include '../components/header.php'; ?>

<div class="login-box">

    <?php if(!empty($error)) echo "<p class='error'>{$error}</p>"; ?>

    <form action="" method="post">
        <div>
            <label for="username">아이디</label>
            <input type="text" id="username" name="username" placeholder="아이디를 입력하세요" required>
        </div>
        <div>
            <label for="password">비밀번호</label>
            <input type="password" id="password" name="password" placeholder="비밀번호를 입력하세요" required>
        </div>
        <input type="submit" value="로그인">
    </form>
</div>

</body>
</html>
<?php
$mysqli = new mysqli("localhost", "team03", "team03", "team03");
if ($mysqli->connect_error) {
    die("DB 연결 실패: " . $mysqli->connect_error);
}

// 로그인 폼
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 유저 존재 여부 확인
    $stmt = $mysqli->prepare("SELECT * FROM User WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 로그인 성공
        header("Location: /BDA_TEAM03/index.php"); // 메인화면으로 이동
        exit;
    } else {
        // 계정 없으면 생성 후 로그인
        $stmt_insert = $mysqli->prepare("INSERT INTO User (role, username, password) VALUES ('USER',?, ?)");
        $stmt_insert->bind_param("ss", $username, $password);
        $stmt_insert->execute();
        $stmt_insert->close();

        header("Location: /BDA_TEAM03/index.php"); // 생성 후 메인화면으로 이동
        exit;
    }

    $stmt->close();
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
</head>
<body>
    <!-- 로그인 박스 -->
    <div style="
    width: 350px;
    margin: 30px auto;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    background: #ffffff;">
    
    <form action="" method="post" style="display:flex; flex-direction:column; gap: 18px;">
        
        <div>
            <label for="username" style="font-weight:600;">아이디</label><br>
            <input type="text" id="username" name="username" required
                style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; font-size:16px;">
        </div>

        <div>
            <label for="password" style="font-weight:600;">비밀번호</label><br>
            <input type="password" id="password" name="password" required
                style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; font-size:16px;">
        </div>

        <input type="submit" value="로그인"
            style="
                padding:12px;
                border:none;
                background:#F47320;
                color:white;
                border-radius:8px;
                font-size:17px;
                font-weight:bold;
                cursor:pointer;
                transition:0.2s;">
    </form>
</div>
</body>
</html>

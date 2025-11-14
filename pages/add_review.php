<?php
include_once '../config.php';

session_start();

$user_id = $_SESSION['user_id'] ?? null; 

if (!$user_id) {
    echo "<script>
            alert('로그인이 필요합니다.');
            window.history.back(); 
          </script>";
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("올바른 식당 ID가 필요합니다.");
}
$restaurant_id = intval($_GET['id']);

$restaurant_name = "식당이름";
$restaurant_desc = "식당 설명";

$sql = "SELECT name, description FROM Restaurant WHERE restaurant_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $restaurant_name = $row['name'];
    $restaurant_desc = $row['description'];
} else {
    die("식당을 찾을 수 없습니다.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taste = intval($_POST['taste']);
    $cleanliness = intval($_POST['cleanliness']);
    $kindness = intval($_POST['kindness']);

    $check_sql = "SELECT * FROM Review WHERE restaurant_id=? AND user_id=?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $restaurant_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $error = "이미 리뷰를 작성했습니다.";
    } else {
        $insert_sql = "INSERT INTO Review (taste, cleanliness, kindness, user_id, restaurant_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        $stmt_insert->bind_param("iiiii", $taste, $cleanliness, $kindness, $user_id, $restaurant_id);
        if ($stmt_insert->execute()) {
            header("Location: view_restaurant.php?id=" . $restaurant_id);
            exit;
        } else {
            $error = "리뷰 등록 중 오류가 발생했습니다: " . $stmt_insert->error;
        }
    }
}
?>

<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>리뷰 등록</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
:root{--accent:#F47320;--muted:#9aa0a6;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{margin:0;background:#fff;color:#111;}

.content-container {
    padding:20px;
    display:flex;
    flex-direction:column;
    align-items:center; 
    text-align:center;
}

.header{
    text-align:center;
    margin-bottom:40px;
}
.restaurant-name{
    font-size:40px; 
    font-weight:900;
    margin:0 0 10px 0;
}
.restaurant-desc{
    font-size:16px;
    color:#555;
    max-width:600px;
    margin:0 auto;
    line-height:1.4;
}

form { 
    display: flex;
    flex-direction: column;
    align-items: center;
}

.review-item{
    display:flex;
    align-items:center;
    justify-content: flex-start; 
    margin:20px 0;
}

.review-label{
    font-size:20px;
    font-weight:600;
    margin-right:20px;
    width: 80px; 
    text-align: center;
    white-space: nowrap; 
}
.review-select{
    padding:5px 0; 
    font-size:20px;
    border:1px solid #ccc;
    border-radius:4px;
    width: 100px;
    text-align: center; 
    -webkit-appearance: none; 
    -moz-appearance: none;
    appearance: none;
    background-repeat: no-repeat;
    background-position: right 4px center;
    background-size: 16px;
    padding-right: 25px;
}

.submit-button{
    margin-top:50px; 
    padding:10px 20px;
    font-size:18px;
    font-weight:600;
    cursor:pointer;
    border:none;
    background-color:#e0e0e0;
    color:#000;
    border-radius:4px;
}
.error{color:red;margin-top:10px;}
</style>
</head>
<body>
<?php include '../menu.php'; ?>

<div class="content-container">
<div class="header">
    <h1 class="restaurant-name"><?php echo htmlspecialchars($restaurant_name); ?></h1>
    <p class="restaurant-desc"><?php echo htmlspecialchars($restaurant_desc); ?></p>
</div>

<?php if(!empty($error)) echo "<p class='error'>{$error}</p>"; ?>

<form method="post">
  
    <div class="review-item">
        <div class="review-label">친절도</div>
        <select name="kindness" class="review-select" required>
            <?php for($i=5;$i>=1;$i--): ?> 
                <option value="<?php echo $i; ?>" <?php echo ($i === 5) ? 'selected' : ''; ?>><?php echo $i; ?></option>             <?php endfor; ?>
        </select>
    </div>

    <div class="review-item">
        <div class="review-label">맛</div>
        <select name="taste" class="review-select" required>
            <?php for($i=5;$i>=1;$i--): ?>
                  <option value="<?php echo $i; ?>" <?php echo ($i === 5) ? 'selected' : ''; ?>><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="review-item">
        <div class="review-label">청결도</div>
        <select name="cleanliness" class="review-select" required>
            <?php for($i=5;$i>=1;$i--): ?>
                  <option value="<?php echo $i; ?>" <?php echo ($i === 5) ? 'selected' : ''; ?>><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>

  <button type="submit" class="submit-button">리뷰 등록</button>
</form>
</div>
</body>
</html>

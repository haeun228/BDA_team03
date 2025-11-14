<?php
include_once '../config.php';
session_start();

// 로그인 상태 확인
$user_id = $_SESSION['user_id'] ?? false;
if (!$user_id) {
    echo "<script>
            alert('로그인이 필요합니다.');
            window.history.back(); 
          </script>";
    exit;
}

// 북마크한 식당 목록 가져오기
$sql = "
SELECT r.restaurant_id, r.name, reg.region_name
FROM Bookmark b
JOIN Restaurant r ON b.restaurant_id = r.restaurant_id
JOIN Region reg ON r.region_id = reg.region_id
WHERE b.user_id = ?
ORDER BY b.bookmark_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookmarked_restaurants = $result->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>내 북마크 목록</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/bookmark.css">
<style>
:root{--accent:#F47320;--muted:#9aa0a6;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{margin:0;background:#fff;color:#111;}
.wrap{max-width:600px;margin:50px auto;background:#fff;}
h1{font-size:24px;padding:20px;border-bottom:1px solid #eee;margin:0;}

.restaurant-item{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:15px 20px;
  border-bottom:1px solid #eee;
  background:#fff;
}
.restaurant-info{
  flex-grow: 1;
  display: flex;
  align-items: center;
  gap: 10px;
}
.restaurant-name{
  font-size:18px;
  font-weight:600;
  color:#111;
  text-decoration:none;
}
.restaurant-region{
  font-size:16px;
  color:var(--muted);
}
</style>
</head>
<body>
<?php include '../menu.php'; ?>
<main class="wrap">
  <h1>내 북마크</h1>

  <?php if (empty($bookmarked_restaurants)): ?>
    <div style="padding:20px; text-align:center; color:var(--muted);">
      북마크한 식당이 없습니다.
    </div>
  <?php else: ?>
    <?php foreach ($bookmarked_restaurants as $restaurant): 
      $restaurant_id = $restaurant['restaurant_id'];
      $is_bookmarked = true;
      
      $handler_path = '../handler/handle_bookmark.php'; 
    ?>
      <div class="restaurant-item">
        <div class="restaurant-info">
          <a href="view_restaurant.php?restaurant_id=<?php echo $restaurant_id; ?>" class="restaurant-name">
            <?php echo htmlspecialchars($restaurant['name']); ?>
          </a>
          <span class="restaurant-region">
            <?php echo htmlspecialchars($restaurant['region_name']); ?>
          </span>
        </div>
        
        <?php include '../components/bookmark_button.php'; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</main>
</body>
</html>
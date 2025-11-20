<?php
session_start();

$recent_list = $_SESSION['recently_viewed'] ?? [];
$list_count = count($recent_list);
?>

<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>최근 조회한 식당</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<style>
:root{--accent:#F47320;--muted:#9aa0a6;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{margin:0;background:#fff;color:#111;}
.wrap{max-width:600px;margin:50px auto;background:#fff;padding-top:20px;}
h1{font-size:24px;padding:0 20px 20px;border-bottom:1px solid #eee;margin:0;}
.list-container{padding:0 20px;}
.restaurant-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:15px 0;
    border-bottom:1px solid #eee;
}
.restaurant-info a{
    font-size:18px;
    font-weight:600;
    color:#111;
    text-decoration:none;
}
.restaurant-region{
    font-size:14px;
    color:var(--muted);
    margin-left: 10px;
}
</style>
</head>
<body>
<?php include '../menu.php'; ?>

<main class="wrap">
    <h1>최근 조회한 식당 (<?php echo $list_count; ?>개)</h1>

    <div class="list-container">
    <?php if ($list_count > 0): ?>
        <?php foreach ($recent_list as $restaurant): 
            $name = htmlspecialchars($restaurant['name']);
            $id = intval($restaurant['id']);
            $region_name = htmlspecialchars($restaurant['region_name'] ?? '지역 정보 없음');
        ?>
            <div class="restaurant-item">
                <div class="restaurant-info">
                    <a href="view_restaurant.php?restaurant_id=<?php echo $id; ?>">
                        <?php echo $name; ?>
                    </a>
                    <span class="restaurant-region">
                        <?php echo $region_name; ?>
                    </span>
                </div>
                </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="padding:40px 0; text-align:center; color:var(--muted);">
            최근에 조회한 식당 기록이 없습니다.
        </div>
    <?php endif; ?>
    </div>
</main>
</body>
</html>
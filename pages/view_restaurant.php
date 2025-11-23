<!--
2271018 조하은
-->
<?php
include_once '../config.php';
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("올바른 식당 ID가 필요합니다.");
}
$restaurant_id = intval($_GET['id']);

// 식당 정보 가져오기
$sql = "
SELECT r.*, c.category_name, reg.region_name
FROM Restaurant r
JOIN Category c ON r.category_id = c.category_id
JOIN Region reg ON r.region_id = reg.region_id
WHERE r.restaurant_id = ? AND r.is_active = 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
$restaurant = $result->fetch_assoc();
if (!$restaurant) { die("식당을 찾을 수 없습니다."); }


/* 최근 조회 식당 세션 저장 */
$current_restaurant = [
    'id' => $restaurant_id,
    'name' => $restaurant['name'],
    'region_name' => $restaurant['region_name']
];

if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}

$recently_viewed = $_SESSION['recently_viewed'];

$existing_keys = array_keys(array_column($recently_viewed, 'id'), $restaurant_id);
if (!empty($existing_keys)) {
    foreach ($existing_keys as $key) {
        unset($recently_viewed[$key]);
    }
    $recently_viewed = array_values($recently_viewed);
}

array_unshift($recently_viewed, $current_restaurant);

$max_items = 5; 
if (count($recently_viewed) > $max_items) {
    $recently_viewed = array_slice($recently_viewed, 0, $max_items);
}

$_SESSION['recently_viewed'] = $recently_viewed;


// 메뉴 가져오기
$sql_menu = "SELECT menu_name, price FROM Menu WHERE restaurant_id = ? AND is_active = 1";
$stmt_menu = $conn->prepare($sql_menu);
$stmt_menu->bind_param("i", $restaurant_id);
$stmt_menu->execute();
$result_menu = $stmt_menu->get_result();
$menus = $result_menu->fetch_all(MYSQLI_ASSOC);

// 리뷰 가져오기 (평균)
$sql_review = "
SELECT COUNT(*) AS review_count,
       AVG(taste) AS avg_taste,
       AVG(cleanliness) AS avg_cleanliness,
       AVG(kindness) AS avg_kindness
FROM Review
WHERE restaurant_id = ?
";
$stmt_review = $conn->prepare($sql_review);
$stmt_review->bind_param("i", $restaurant_id);
$stmt_review->execute();
$result_review = $stmt_review->get_result();
$review = $result_review->fetch_assoc();

// 휴무일 가져오기
$sql_closed = "SELECT day FROM Closed_Days WHERE restaurant_id = ?";
$stmt_closed = $conn->prepare($sql_closed);
$stmt_closed->bind_param("i", $restaurant_id);
$stmt_closed->execute();
$result_closed = $stmt_closed->get_result();
$closed_days = [];
while ($row = $result_closed->fetch_assoc()) { $closed_days[] = $row['day']; }

$open_time = substr($restaurant['open_time'], 0, 5);
$close_time = substr($restaurant['close_time'], 0, 5);

$day_kor = ['MON'=>'월','TUE'=>'화','WED'=>'수','THU'=>'목','FRI'=>'금','SAT'=>'토','SUN'=>'일'];
$closed_days_kor = [];
foreach ($closed_days as $day) {
    if(isset($day_kor[$day])) $closed_days_kor[] = $day_kor[$day];
}
$closed_days_str = '';
if(!empty($closed_days_kor)) {
    $closed_days_str = '('.implode(', ', $closed_days_kor).' 휴무)';
} else {
    $closed_days_str = '(휴무일 없음)';
}

// 평균 점수 계산
$avg_score = 0;
if ($review['review_count'] > 0) {
    $avg_score = round(($review['avg_taste'] + $review['avg_cleanliness'] + $review['avg_kindness']) / 3, 1);
}

$user_id = $_SESSION['user_id'] ?? null;
$is_bookmarked = false;
if ($user_id) {
    $sql_bookmark = "SELECT COUNT(*) FROM Bookmark WHERE restaurant_id = ? AND user_id = ?";
    $stmt_bookmark = $conn->prepare($sql_bookmark);
    $stmt_bookmark->bind_param("ii", $restaurant_id, $user_id);
    $stmt_bookmark->execute();
    $stmt_bookmark->bind_result($count);
    $stmt_bookmark->fetch();
    $stmt_bookmark->close();
    
    $is_bookmarked = ($count > 0);
}

// 지역 내 랭킹
$sql_region_rank = "
SELECT r.restaurant_id, DENSE_RANK() OVER (PARTITION BY r.region_id ORDER BY AVG(rv.taste + rv.cleanliness + rv.kindness)/3 DESC) AS region_rank
FROM Restaurant r
LEFT JOIN Review rv ON r.restaurant_id = rv.restaurant_id
WHERE r.region_id = ?
GROUP BY r.restaurant_id
";
$stmt_region_rank = $conn->prepare($sql_region_rank);
$stmt_region_rank->bind_param("i", $restaurant['region_id']);
$stmt_region_rank->execute();
$result_region_rank = $stmt_region_rank->get_result();

$region_rank = null;
while($row = $result_region_rank->fetch_assoc()) {
    if($row['restaurant_id'] == $restaurant_id){
        $region_rank = $row['region_rank'];
        break;
    }
}
$stmt_region_rank->close();

// 카테고리 내 랭킹
$sql_category_rank = "
SELECT r.restaurant_id, DENSE_RANK() OVER (PARTITION BY r.category_id ORDER BY AVG(rv.taste + rv.cleanliness + rv.kindness)/3 DESC) AS category_rank
FROM Restaurant r
LEFT JOIN Review rv ON r.restaurant_id = rv.restaurant_id
WHERE r.category_id = ?
GROUP BY r.restaurant_id
";
$stmt_category_rank = $conn->prepare($sql_category_rank);
$stmt_category_rank->bind_param("i", $restaurant['category_id']);
$stmt_category_rank->execute();
$result_category_rank = $stmt_category_rank->get_result();

$category_rank = null;
while($row = $result_category_rank->fetch_assoc()) {
    if($row['restaurant_id'] == $restaurant_id){
        $category_rank = $row['category_rank'];
        break;
    }
}
$stmt_category_rank->close();

// WINDOWING 분석
$period_start = $_GET['start_date'] ?? date('Y-m-d');
$period_end = $_GET['end_date'] ?? date('Y-m-d');

// HTML 폼의 값 유지를 위한 변수 초기화 (경고 방지)
$start_date = $_GET['start_date'] ?? ''; 
$end_date = $_GET['end_date'] ?? ''; 

$windowing_results = [];

$bind_types_window = 'i';
$bind_params_window = [$restaurant_id];

$sql_review_window = "
SELECT
  DATE_FORMAT(visited_at, '%Y년 %m월') AS review_month,
  AVG(taste) AS avg_taste,
  AVG(cleanliness) AS avg_cleanliness,
  AVG(kindness) AS avg_kindness,
  AVG((taste + cleanliness + kindness)/3) AS avg_overall,
  AVG(AVG((taste + cleanliness + kindness)/3)) OVER w AS running_avg_overall
FROM Review
WHERE restaurant_id = ? 
  AND visited_at IS NOT NULL
";

if ($period_start && $period_end) {
  $sql_review_window .= " AND visited_at BETWEEN ? AND ? ";
  $bind_types_window .= 'ss';
  $bind_params_window[] = $period_start;
  $bind_params_window[] = $period_end;
}

$sql_review_window .= "
GROUP BY review_month
-- 윈도우 정의: review_month 순서대로 누적하여 running_avg를 계산합니다.
WINDOW w AS (ORDER BY review_month ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
ORDER BY review_month ASC
";

$stmt_review_window = $conn->prepare($sql_review_window);

if (!empty($bind_params_window)) {
  $stmt_review_window->bind_param($bind_types_window, ...$bind_params_window);
}

$stmt_review_window->execute();
$result_review_window = $stmt_review_window->get_result();

while ($row = $result_review_window->fetch_assoc()) {
  $windowing_results[] = [
    'review_month' => $row['review_month'],
    'avg_taste' => round($row['avg_taste'], 1),
    'avg_cleanliness' => round($row['avg_cleanliness'], 1),
    'avg_kindness' => round($row['avg_kindness'], 1),
    'avg_overall' => round($row['avg_overall'], 1),
    'running_avg_overall' => round($row['running_avg_overall'], 1),
  ];
}
$stmt_review_window->close();
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?php echo htmlspecialchars($restaurant['name']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/bookmark.css">
<style>
:root{--accent:#F47320;--muted:#9aa0a6;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{margin:0;background:#fff;color:#111;}
.wrap{max-width:980px;margin:30px auto;padding:0 36px;}
.top-row{display:flex;align-items:flex-start;justify-content:space-between;gap:24px;}
.meta{color:var(--muted);font-size:16px;margin-bottom:6px;}
h1.title{margin:0;font-size:36px;line-height:1;font-weight:800;}
.desc{color:#333;margin-top:10px;font-size:15px;max-width:68%;line-height:1.5;}
.location-row{margin-top:16px;color:var(--muted);font-size:14px;display:flex;gap:12px;flex-direction:column;}
.menu-section{margin-top:18px;}
.menu-section h3{margin:0 0 8px 0;font-size:18px;}
.menu-list{list-style:disc;padding-left:20px;margin:0;color:#222;}
.menu-list li{margin:6px 0;font-size:15px;}
.reviews{margin-top:28px;display:flex;gap:20px;align-items:flex-start;flex-direction:column}
.overall{min-width:150px;}
.rating-badge{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.big-score{font-size:28px;font-weight:800;}
.stars-inline{display:flex;gap:6px;align-items:center;}
.sub-ratings{display:flex;gap:28px;margin-top:8px;}
.sub{display:flex;flex-direction:column;align-items:flex-start;gap:6px;}
.sub .label{font-size:13px;color:var(--muted);}
.sub .val{display:flex;align-items:center;gap:4px;font-weight:700;}
.btn-wrap{margin-left:auto;display:flex;align-items:center;}
.rank {display:inline-flex;align-items:center;gap: 6px;padding:4px 8px;border-radius:12px;background-color:#F47320;color:#fff;font-size:13px;font-weight: 600;}
.rank + .rank {margin-left:8px;}
button.primary{background:#e0e0e0;border:0;padding:10px 14px;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;}
.star{width:22px;height:22px;fill:#ddd;}
.star.filled{fill:var(--accent);}
.small-star{width:16px;height:16px;fill:var(--accent);}
@media (max-width:760px){.wrap{padding:20px;margin:20px}h1.title{font-size:26px}.desc{max-width:100%}.reviews{flex-direction:column;gap:16px}.sub-ratings{gap:18px}.top-row{flex-direction:column;align-items:flex-start}}
</style>
<script src="https://use.fontawesome.com/releases/v5.2.0/js/all.js"></script>
</head>
<body>
<?php include '../menu.php'; ?>
<main class="wrap">
<div class="top-row">
  <div style="flex:1;">
    <div class="meta"><?php echo htmlspecialchars($restaurant['category_name']); ?></div>
    <h1 class="title"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
    <p class="desc"><?php echo htmlspecialchars($restaurant['description']); ?></p>
    <div class="location-row">
      <div><?php echo htmlspecialchars($restaurant['region_name']); ?></div>
      <div style="font-weight:600">
        <?php echo $open_time; ?> ~ <?php echo $close_time; ?> <?php echo $closed_days_str; ?>
      </div>
    </div>
    <section class="menu-section">
      <h3>메뉴</h3>
      <ul class="menu-list">
        <?php if(!empty($menus)): foreach($menus as $menu): ?>
          <li><?php echo htmlspecialchars($menu['menu_name']); ?> : <?php echo number_format($menu['price']); ?>원</li>
        <?php endforeach; else: ?>
          <li>메뉴가 없습니다.</li>
        <?php endif; ?>
      </ul>
    </section>
  </div>
  <?php include '../components/bookmark_button.php'; ?>
</div>
<br>
<div class="reviews">
  <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
    <div style="font-weight:700; font-size: 18px">리뷰 <span style="font-weight:500;color:var(--muted)">(<?php echo $review['review_count']; ?>)</span></div>
    <div class="btn-wrap">
      <a href="add_review.php?id=<?php echo $restaurant['restaurant_id']; ?>">
        <button class="primary">리뷰 등록</button>
      </a>
    </div>
  </div>
  <div class="overall">
    <div class="rating-badge">
      <div class="big-score">
        <?php echo $avg_score; ?> <span style="font-size:16px;color:var(--muted);font-weight:500;">평점</span>
      </div>
      <div class="stars-inline">
        <?php for($i=1;$i<=5;$i++): ?>
          <svg class="star <?php echo $i<=$avg_score?'filled':''; ?>" viewBox="0 0 24 24">
            <path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/>
          </svg>
        <?php endfor; ?>
      </div>
      <div style="width: 20px"></div>
      <div class="rank">
        <?php 
          echo htmlspecialchars($restaurant['region_name']); 
          echo $region_rank !== null ? " {$region_rank}위" : " (순위 없음)"; 
        ?>
      </div>
      <div class="rank">
        <?php 
          echo htmlspecialchars($restaurant['category_name']); 
          echo $category_rank !== null ? " {$category_rank}위" : " (순위 없음)"; 
        ?>
      </div>
    </div>
  </div>

  <div style="flex:1;">
    <div class="sub-ratings">
      <div class="sub">
        <div class="label">친절도</div>
        <div class="val">
          <svg class="small-star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
          <span><?php echo isset($review['avg_kindness']) ? number_format($review['avg_kindness'], 1) : '0.0'; ?></span>
        </div>
      </div>
      <div class="sub">
        <div class="label">맛</div>
        <div class="val">
          <svg class="small-star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
          <span><?php echo isset($review['avg_taste']) ? number_format($review['avg_taste'], 1) : '0.0'; ?></span>
        </div>
      </div>
      <div class="sub">
        <div class="label">청결도</div>
        <div class="val">
          <svg class="small-star" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.431L23.6 9.75l-5.6 5.456L19.335 24 12 19.897 4.665 24l1.335-8.794L.4 9.75l7.932-1.732L12 .587z"/></svg>
          <span><?php echo isset($review['avg_cleanliness']) ? number_format($review['avg_cleanliness'], 1) : '0.0'; ?></span>
        </div>
      </div>
    </div>
  </div>
</div>
<hr style="margin-top: 30px; border-color: #eee;">
<section style="margin-top: 30px;">
  <h4>기간별 평점 분석</h4>
  
  <form method="GET" style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($restaurant_id); ?>">
    
    <label for="start_date" style="font-size: 14px; font-weight: 600;">시작일:</label>
    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
    
    <span>~</span>
    
    <label for="end_date" style="font-size: 14px; font-weight: 600;">종료일:</label>
    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
    
    <button type="submit" style="padding: 8px 12px; background: #333; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
      기간 조회
    </button>
  </form>
  
  <?php if (!empty($windowing_results)): ?>
    <table style="width:100%; border-collapse: collapse; table-layout: fixed; font-size:14px; text-align:center; vertical-align:middle; margin-top:15px;">
      <thead>
        <tr style="background:#f8f8f8;">
          <th style="padding:10px; border:1px solid #ddd;">기간</th>
          <th style="padding:10px; border:1px solid #ddd;">맛</th>
          <th style="padding:10px; border:1px solid #ddd;">청결도</th>
          <th style="padding:10px; border:1px solid #ddd;">친절도</th>
          <th style="padding:10px; border:1px solid #ddd;">전체 평균</th>
          <th style="padding:10px; border:1px solid #ddd; background-color:#fce8d5;">누적 평균</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($windowing_results as $row): ?>
        <tr>
          <td style="padding:10px; border:1px solid #ddd;"><?php echo htmlspecialchars($row['review_month']); ?></td>
          <td style="padding:10px; border:1px solid #ddd;"><?php echo $row['avg_taste']; ?></td>
          <td style="padding:10px; border:1px solid #ddd;"><?php echo $row['avg_cleanliness']; ?></td>
          <td style="padding:10px; border:1px solid #ddd;"><?php echo $row['avg_kindness']; ?></td>
          <td style="padding:10px; border:1px solid #ddd; font-weight:600;"><?php echo $row['avg_overall']; ?></td>
          <td style="padding:10px; border:1px solid #ddd; font-weight:600; background-color:#fffaf0;"><?php echo $row['running_avg_overall']; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>선택한 기간 (<?php echo htmlspecialchars($start_date); ?> ~ <?php echo htmlspecialchars($end_date); ?>)에 분석할 데이터가 부족합니다.</p>
  <?php endif; ?>
</section>
</main>
</body>
</html>

<?php
// restaurants.php
// 기본 색 #f47320
// 서브 색 #fcc6a2
// 사용자 한 명이 작성한 리뷰 -> 한 리뷰의 점수 평균 -> 뷰 만들?말?
// 여러 리뷰의 평균 -> 식당별 최종 평점

/* 
뷰 집어넣을거면 create review 문 뒤에 넣어줄 것
CREATE VIEW Review_Score AS
SELECT
    review_id,
    restaurant_id,
    user_id,
    (taste + cleanliness + kindness) / 3 AS score
FROM Review;

*/


// 0. 공통 설정 ---------------------------------------------------
date_default_timezone_set('Asia/Seoul');
session_start();
require_once __DIR__ . '/../config.php';

// $mysqli = new mysqli($host, $user, $pass, $dbname);
// if ($mysqli->connect_errno) {
//     die('DB 연결 실패: ' . $mysqli->connect_error);
// }

// 1. 드롭다운 옵션 정의 ------------------------------------------
// 화면에 보여줄 이름 (url 에 보여줄 영어 키)
$regionOptions = [
    'all'      => '전체',
    'gangnam'  => '강남구',
    'gwangjin' => '광진구',
    'ddm'      => '동대문구',
    'mapo'     => '마포구',
    'sdm'      => '서대문구',
    'seocho'   => '서초구',
    'seongdong'=> '성동구',
    'songpa'   => '송파구',
    'yongsan'  => '용산구',
    'jongno'   => '종로구',
];

// 실제 Region 테이블의 region_id 매핑
$regionIdMap = [
    'gangnam'   => 1,
    'gwangjin'  => 2,
    'ddm'       => 3,
    'mapo'      => 4,
    'sdm'       => 5,
    'seocho'    => 6,
    'seongdong' => 7,
    'songpa'    => 8,
    'yongsan'   => 9,
    'jongno'    => 10,
];

$sortOptions = [
    'rating'   => '평점별',
    'bookmark' => '북마크 수',
];

// 2. GET 파라미터 읽기 (기본: 전체 + 평점별) -----------------------
$currentRegionKey = isset($_GET['region']) ? $_GET['region'] : 'all';
if (!isset($regionOptions[$currentRegionKey])) {
    $currentRegionKey = 'all';
}

$currentSortKey = isset($_GET['sort']) ? $_GET['sort'] : 'rating';
if (!isset($sortOptions[$currentSortKey])) {
    $currentSortKey = 'rating';
}

// 3. 오늘 요일 코드 (Closed_Days 반영) ------------------------------
// PHP 요일(월~일): Mon, Tue, Wed, Thu, Fri, Sat, Sun
$phpDay = date('D');
$dayMap = [
    'Mon' => 'MON',
    'Tue' => 'TUE',
    'Wed' => 'WED',
    'Thu' => 'THU',
    'Fri' => 'FRI',
    'Sat' => 'SAT',
    'Sun' => 'SUN',
];
$todayEnum = $dayMap[$phpDay];

// 4. SQL 만들기 ---------------------------------------------------
// - Restaurant + Region + Category
// - Review 로 평점 계산 : (맛 + 청결도 + 친절도)/3 를 평균
// - Bookmark 개수 계산
// - 오늘 요일이 휴무인지(Closed_Days)도 같이 가져오기
$sql = "
    SELECT
        r.restaurant_id,
        r.name,
        rg.region_name,
        c.category_name,
        r.open_time,
        r.close_time,
        r.is_active,
        IFNULL(AVG((rv.taste + rv.cleanliness + rv.kindness) / 3.0), 0) AS avg_rating,
        COUNT(DISTINCT bm.bookmark_id) AS bookmark_count,
        CASE WHEN cd.closed_days_id IS NOT NULL THEN 1 ELSE 0 END AS is_closed_today
    FROM Restaurant r
    JOIN Region   rg ON r.region_id = rg.region_id
    JOIN Category c  ON r.category_id = c.category_id
    LEFT JOIN Review   rv ON rv.restaurant_id   = r.restaurant_id
    LEFT JOIN Bookmark bm ON bm.restaurant_id   = r.restaurant_id
    LEFT JOIN Closed_Days cd 
           ON cd.restaurant_id = r.restaurant_id 
          AND cd.day = '$todayEnum'
    WHERE r.is_active = 1
";

// 지역 필터
if ($currentRegionKey !== 'all') {
    $regionId = (int) $regionIdMap[$currentRegionKey];
    $sql .= " AND r.region_id = $regionId ";
}

$sql .= " GROUP BY r.restaurant_id ";

// 정렬 기준
if ($currentSortKey === 'bookmark') {
    $sql .= " ORDER BY bookmark_count DESC, avg_rating DESC ";
} else { // rating
    $sql .= " ORDER BY avg_rating DESC, bookmark_count DESC ";
}

// 무한스크롤 하기 전까지는 일단 30개만
$sql .= " LIMIT 30 ";

$result = $conn->query($sql);
if (!$result) {
    die('쿼리 오류: ' . $conn->error);
}

// 현재 시간 (OPEN/CLOSED) 계산용
$now = date('H:i:s');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>My Seoul Food - 전체 맛집 조회</title>
    <style>
        .page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 30px; /* 좌우 패딩 */
            }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #f7f7f7;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 40px;
            background-color: #ffffff;
            border-bottom: 1px solid #eee;
        }
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #f47320;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .login-btn {
            padding: 8px 18px;
            border-radius: 4px;
            border: none;
            background-color: #f47320;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }

        /* 상단 필터 바 */
        .filter-bar {
            display: flex;
            justify-content: flex-end;
            gap: 16px;
            padding: 16px 40px;
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-toggle {
            padding: 10px 20px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #fff;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dropdown-toggle.active {
            background-color: #f47320;
            color: #fff;
            border-color: #f47320;
        }
        .dropdown-menu {
            position: absolute;
            top: 110%;
            left: 0;
            min-width: 140px;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
            z-index: 10;
            display: none;
        }
        .dropdown-menu a {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }
        .dropdown-menu a:hover {
            background-color: #f2f2f2;
        }
        .dropdown-menu a.active {
            background-color: #f47320;
            color: #fff;
        }

        /* 메인 리스트 */
        .content {
            padding: 40px 80px;
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 32px;
        }
        .restaurant-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .restaurant-item {
            display: grid;
            grid-template-columns: 80px 1.5fr 1fr 120px 120px;
            align-items: center;
            padding: 18px 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            cursor: pointer;              
            transition: background 0.15s;   

        }
        .restaurant-item:hover {
        background-color: #fdf4ec; 
        }

        .rank-num {
            font-size: 20px;
            font-weight: 700;
        }
        .res-name {
            font-size: 20px;
            font-weight: 600;
        }
        .res-meta {
            font-size: 14px;
            color: #777;
            margin-top: 4px;
        }
        .star-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .star-icon {
            font-size: 32px;
            color: #f47320;
        }
        .rating-badge {
            padding: 4px 8px;
            border-radius: 12px;
            background-color: #ffe1c4;
            color: #f47320;
            font-size: 12px;
            font-weight: 700;
        }
        .status-btn {
            width: 100px;
            text-align: center;
            padding: 10px 0;
            border-radius: 4px;
            font-weight: 700;
            border: none;
            cursor: default;
        }
        .status-open {
            background-color: #f47320;
            color: #fff;
        }
        .status-closed {
            background-color: #f8c1a5;
            color: #7b3b20;
        }
        .bookmark-info {
            text-align: center;
            font-size: 14px;
        }
        .bookmark-label {
            font-weight: 600;
        }
        .bookmark-count {
            margin-top: 6px;
            font-size: 18px;
            color: #f47320;
            font-weight: 700;
        }
        
    </style>
</head>
<body>

<?php include dirname(__DIR__) . '/menu.php'; ?>

<div class="page-wrapper">
    <!-- 지도 로고(지역) + 정렬 드롭다운 -->
<div class="filter-bar">
    <!-- 1) 지역 드롭다운 -->
    <div class="dropdown">
        <button class="dropdown-toggle <?php echo $currentRegionKey !== 'all' ? 'active' : ''; ?>">
            🗺️ <?php echo $regionOptions[$currentRegionKey]; ?>
        </button>
        <div class="dropdown-menu">
            <?php foreach ($regionOptions as $key => $label): ?>
                <?php
                    $url = '?region=' . $key . '&sort=' . $currentSortKey;
                    $activeClass = ($key === $currentRegionKey) ? 'active' : '';
                ?>
                <a href="<?php echo htmlspecialchars($url); ?>" class="<?php echo $activeClass; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 2) 정렬 기준 드롭다운 -->
    <div class="dropdown">
        <button class="dropdown-toggle active">
            <?php echo $sortOptions[$currentSortKey]; ?>
        </button>
        <div class="dropdown-menu">
            <?php foreach ($sortOptions as $key => $label): ?>
                <?php
                    $url = '?region=' . $currentRegionKey . '&sort=' . $key;
                    $activeClass = ($key === $currentSortKey) ? 'active' : '';
                ?>
                <a href="<?php echo htmlspecialchars($url); ?>" class="<?php echo $activeClass; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<main class="content">
    <div class="page-title">마이서울푸드 맛집 랭킹</div>

    <div class="restaurant-list">
        <?php
        $rank = 1;
        while ($row = $result->fetch_assoc()):
            // 휴무일이거나 is_active = 0 이면 무조건 CLOSED
            $isClosedToday = (int)$row['is_closed_today'] === 1;

            // 그 외에는 시간으로 OPEN/CLOSED 판별
            $openTime  = $row['open_time'];
            $closeTime = $row['close_time'];

            if ($isClosedToday) {
                $isOpen = false;
            } else {
                // 밤을 넘기지 않는 가게 기준 (예: 10:00~22:00)
                if ($openTime < $closeTime) {
                    $isOpen = ($now >= $openTime && $now <= $closeTime);
                } else {
                    // 새벽까지 하는 가게 (예: 18:00~02:00)
                    $isOpen = ($now >= $openTime || $now <= $closeTime);
                }
            }

            $statusClass = $isOpen ? 'status-open' : 'status-closed';
            $statusText  = $isOpen ? 'OPEN' : 'CLOSED';

            $avgRating     = number_format($row['avg_rating'], 1);
            $bookmarkCount = (int)$row['bookmark_count'];
            $detailUrl = 'view_restaurant.php?id=' . (int)$row['restaurant_id'];

        ?>
        <div class="restaurant-item" onclick="location.href='<?php echo $detailUrl; ?>'">
            <div class="rank-num"><?php echo $rank++; ?>.</div>

            <div>
                <div class="res-name"><?php echo htmlspecialchars($row['name']); ?></div>
                <div class="res-meta">
                    <?php echo htmlspecialchars($row['region_name']); ?>
                </div>
            </div>

            <div>
                <div class="res-meta"><?php echo htmlspecialchars($row['category_name']); ?></div>
            </div>

            <div class="star-wrapper">
                <div class="star-icon">★</div>
                <span class="rating-badge"><?php echo $avgRating; ?></span>
            </div>

            <div>
                <button class="status-btn <?php echo $statusClass; ?>">
                    <?php echo $statusText; ?>
                </button>
                <div class="bookmark-info">
                    <div class="bookmark-label">북마크 수</div>
                    <div class="bookmark-count"><?php echo $bookmarkCount; ?></div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</main>

<script>
    // 드롭다운 토글
    document.querySelectorAll('.dropdown').forEach(function (dd) {
        const btn = dd.querySelector('.dropdown-toggle');
        const menu = dd.querySelector('.dropdown-menu');

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = menu.style.display === 'block';
            document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
            menu.style.display = isOpen ? 'none' : 'block';
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
    });
</script>
</div>

</body>
</html>

<?php
// 2276278 정서윤
// analysis_review.php
// Rollup 및 Drill Down 분석을 통해 서울 10개 구의 맛집 평점 추이 분석 페이지
include_once '../config.php';
session_start();

$selected_year = isset($_GET['drill_year']) ? (int)$_GET['drill_year'] : null;
$start_year = isset($_GET['start_year']) ? (int)$_GET['start_year'] : 2023;
$end_year   = isset($_GET['end_year'])   ? (int)$_GET['end_year']   : 2025;

if ($start_year > $end_year) {
    // 혹시 사용자가 이상하게 넣었을 때 안전장치
    $tmp = $start_year;
    $start_year = $end_year;
    $end_year = $tmp;
}

$rollup_results = [];
$drilldown_results = [];

// --- 1) Drill Down (연도 선택 시: 구별 분기 평균) ---
if ($selected_year) {
    $sql = "
        SELECT 
            reg.region_name AS district,
            AVG(CASE WHEN QUARTER(r.visit_date) = 1 
                 THEN (r.taste + r.cleanliness + r.kindness) / 3 END) AS q1,
            AVG(CASE WHEN QUARTER(r.visit_date) = 2 
                 THEN (r.taste + r.cleanliness + r.kindness) / 3 END) AS q2,
            AVG(CASE WHEN QUARTER(r.visit_date) = 3 
                 THEN (r.taste + r.cleanliness + r.kindness) / 3 END) AS q3,
            AVG(CASE WHEN QUARTER(r.visit_date) = 4 
                 THEN (r.taste + r.cleanliness + r.kindness) / 3 END) AS q4
        FROM Review r
        JOIN Restaurant res ON r.restaurant_id = res.restaurant_id
        JOIN Region reg ON res.region_id = reg.region_id
        WHERE YEAR(r.visit_date) = ?
        GROUP BY reg.region_name
        ORDER BY reg.region_name
    ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $selected_year);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $drilldown_results[] = $row;
        }
        $stmt->close();
    }
// --- 2) Rollup (기간 설정만 했을 때: 연도별 평균 + 리뷰 수) ---
} else {
    $sql = "
        SELECT 
            YEAR(r.visit_date) AS year,
            AVG((r.taste + r.cleanliness + r.kindness) / 3) AS avg_score,
            COUNT(*) AS review_count
        FROM Review r
        WHERE YEAR(r.visit_date) BETWEEN ? AND ?
        GROUP BY YEAR(r.visit_date)
        ORDER BY year
    ";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ii', $start_year, $end_year);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $rollup_results[] = $row;
        }
        $stmt->close();
    }
}
$is_logged_in = $_SESSION['user_id'] ?? false;

?>

<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>서울 맛집 트렌드 분석</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--accent:#F47320;--muted:#9aa0a6;font-family:"Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{margin:0;background:#f4f4f4;color:#111;}
.wrap{max-width:900px;margin:50px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.1);}
h1{color:#111;margin-top:0;font-size:26px;}

/* 폼 스타일 */
.analysis-form{display:flex;gap:15px;align-items:center;margin-bottom:30px;padding:15px;border:1px solid #eee;border-radius:6px;background:#fafafa;}
.analysis-form input, .analysis-form button{padding:8px 12px;border:1px solid #ccc;border-radius:4px;font-size:14px;}
.analysis-form button{background:var(--accent);color:white;border:none;cursor:pointer;font-weight:600;}

/* 테이블 스타일 */
table{width:100%;border-collapse:collapse;margin-top:20px;}
th, td{padding:12px 15px;text-align:left;border:1px solid #ddd;font-size:15px;}
th{background:#eee;font-weight:700;}
.rollup-table a{color:var(--accent);text-decoration:none;font-weight:700;}
.rollup-table a:hover{text-decoration:underline;}
.drilldown-title{font-size:20px;font-weight:700;margin-bottom:15px;color:var(--accent);}
.highlight-col{background-color:#fff3e0;} /* 경쟁 분석 컬럼 강조 */
</style>
</head>
<body>

<?php include dirname(__DIR__) . '/menu.php'; ?>

<main class="wrap">
    <h1> 서울 맛집 트렌드 분석</h1>
    <p>서울 10개 구의 평점 추이를 연도별로 요약하고, 선택한 연도의 분기별 상세 변화를 확인합니다.<br>
          원하는 연도를 클릭하면 해당 연도의 분기별 상세 평점을 볼 수 있습니다.</p>

    <form method="GET" class="analysis-form">
        <label for="start_year">기간 설정:</label>
        <input type="number" name="start_year" value="<?php echo htmlspecialchars($start_year); ?>" min="2020" max="2030" required>
        <span>~</span>
        <input type="number" name="end_year" value="<?php echo htmlspecialchars($end_year); ?>" min="2020" max="2030" required>
        <button type="submit">결과 보기</button>
    </form>

    <?php if ($selected_year): ?>
        <div class="drilldown-title">
             <?php echo htmlspecialchars($selected_year); ?>년 분기별 평점 비교
        </div>
        <table class="drilldown-table">
            <thead>
                <tr>
                    <th>지역 (구)</th>
                    <th>1분기 평균</th>
                    <th>2분기 평균</th>
                    <th>3분기 평균</th>
                    <th>4분기 평균</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drilldown_results as $row): ?>
                 <tr>
                        <td><?php echo htmlspecialchars($row['district']); ?></td>
                        <td><?php echo $row['q1'] !== null ? number_format($row['q1'], 2) : '-'; ?></td>
                        <td><?php echo $row['q2'] !== null ? number_format($row['q2'], 2) : '-'; ?></td>
                        <td><?php echo $row['q3'] !== null ? number_format($row['q3'], 2) : '-'; ?></td>
                        <td><?php echo $row['q4'] !== null ? number_format($row['q4'], 2) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
         <p style="margin-top: 30px;">
            <a href="analysis_review.php?start_year=<?php echo htmlspecialchars($start_year); ?>&end_year=<?php echo htmlspecialchars($end_year); ?>" style="color:#007bff;">
                뒤로가기
            </a>
        </p>

    <?php else: ?>
        <?php if (empty($rollup_results)): ?>
            <p class="info-text">해당 기간에 대한 리뷰 데이터가 없습니다. 기간을 다시 설정해 보세요.</p>
        <?php else: ?>
        <table class="rollup-table">
            <thead>
                <tr>
                    <th>연도</th>
                    <th>서울 전체 평균 평점</th>
                    <th>리뷰 수</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rollup_results as $row): ?>
                <tr>
                    <td>
                            <a href="?drill_year=<?php echo htmlspecialchars($row['year']); ?>&start_year=<?php echo htmlspecialchars($start_year); ?>&end_year=<?php echo htmlspecialchars($end_year); ?>">
                                <?php echo htmlspecialchars($row['year']); ?>
                            </a>
                        </td>
                        <td><?php echo number_format($row['avg_score'], 2); ?></td>
                        <td><?php echo number_format($row['review_count']); ?>개</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
    
</main>
</body>
</html>
<!--
2271007 강민서
-->
<?php
include("../config.php");
include ("../menu.php");

// 1. GET 요청 변수 초기화 및 정리 (SQL 인젝션 방지 포함)
$default_sort_col = 'region_name';
$default_order = 'ASC';
$allowed_columns = ['region_name', 'max_price', 'min_price', 'total_stores', 'avg_rating'];

// 현재 정렬 컬럼 (Dynamic Query - ORDER BY)
$sort_column = $_GET['sort'] ?? $default_sort_col;
// 현재 정렬 순서 (Dynamic Query - ORDER BY)
$sort_order = strtoupper($_GET['order'] ?? $default_order);

// 필터링 값 설정 (Prepared Statement - HAVING)
// 사용자가 입력한 최소 평균 평점. 입력이 없으면 기본값 0.0
$min_avg_rating = $_GET['min_rating'] ?? 0.0;

// ORDER BY 보안 검증 (화이트리스트)
if (!in_array($sort_column, $allowed_columns) || !in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_column = $default_sort_col;
    $sort_order = $default_order;
}

// 2. SQL 쿼리 생성
// 🚨 IFNULL 함수를 사용하여 리뷰가 없는 경우(NULL) 평점을 0으로 처리합니다.
$sql = "SELECT
    r.region_name AS region_name,
    COUNT(t.restaurant_id) AS total_stores, 
    MAX(m.price) AS max_price,
    MIN(m.price) AS min_price,
    IFNULL(AVG(rev.avg_rating), 0) AS avg_rating  -- 1. SELECT 결과에서 NULL을 0으로 변환
    FROM 
        region r
    JOIN 
        restaurant t ON r.region_id = t.region_id
    LEFT JOIN 
        menu m ON t.restaurant_id = m.restaurant_id
    LEFT JOIN 
        (
            -- 가게별 평균 별점 계산을 위한 서브 쿼리
            SELECT 
                restaurant_id, 
                AVG(taste + cleanliness + kindness) / 3 AS avg_rating
            FROM 
                review
            GROUP BY 
                restaurant_id
        ) rev ON t.restaurant_id = rev.restaurant_id
    GROUP BY 
        r.region_id, r.region_name
    HAVING 
        IFNULL(AVG(rev.avg_rating), 0) >= ?  -- 2. HAVING 조건에서도 NULL을 0으로 변환하여 비교
    ORDER BY 
        {$sort_column} {$sort_order}"; // ORDER BY는 검증된 변수 직접 삽입

// 3. PreparedStatement 준비 및 실행
if (!$conn) {
    die("DB 연결 객체가 유효하지 않습니다.");
}

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    mysqli_close($conn);
    die("SQL 준비 실패: " . $conn->error . "<br>쿼리: " . $sql);
}

// 4. 바인딩: HAVING 절의 값을 안전하게 전달 (필수)
// 'd'는 $min_avg_rating이 실수(double/float)임을 나타냅니다.
$stmt->bind_param("d", $min_avg_rating);

// 5. 쿼리 실행 및 결과 처리
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();
?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <title>Team03 Project</title>
        <link rel="stylesheet" href="../css/stats_style.css">
    </head>
    <body>
    <div class="content-container">
        <h1>지역별 통계</h1>

        <div class="data-table-wrapper">
            <form class="input-form" method="get" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="filter-form">
                <label for="min_rating">최소 평균 별점:</label>
                <input type="number"
                       id="min_rating"
                       name="min_rating"
                       step="0.1"
                       min="0"
                       max="5"
                       value="<?= htmlspecialchars($_GET['min_rating'] ?? '0.0') ?>">
                <button type="submit">조회</button>

                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_column) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($sort_order) ?>">
            </form>

            <table class="data-table">
                <thead>
                <tr>
                    <?php
                    // 테이블 헤더 반복 출력 및 정렬 상태 표시
                    $columns = [
                            'region_name' => '지역명',
                            'max_price' => '최고 메뉴 가격',
                            'min_price' => '최소 메뉴 가격',
                            'total_stores' => '총 가게 수',
                            'avg_rating' => '평균 별점'
                    ];

                    foreach ($columns as $db_col => $display_name):
                        // 현재 정렬 대상 컬럼인지 확인
                        $is_current = ($sort_column === $db_col);
                        // 다음 순서 결정: 현재 ASC면 DESC, 아니면 ASC (JS에서 사용)
                        $next_order = ($is_order = $is_current && $sort_order === 'ASC') ? 'DESC' : 'ASC';

                        // 아이콘 설정
                        $icon = '▲▼';
                        if ($is_current) {
                            $icon = ($sort_order === 'ASC') ? '▲' : '▼';
                        }
                        // data-sort 속성에 현재 상태를 넣어 JS가 읽도록 합니다.
                        $current_data_sort = $is_current ? strtolower($sort_order) : 'none';
                        ?>
                        <th data-column="<?= $db_col ?>">
                            <?= $display_name ?>
                            <a href="#"
                               class="sort-button <?= $is_current ? 'active' : '' ?>"
                               data-sort="<?= $current_data_sort ?>"
                               data-col="<?= $db_col ?>"> <span class="sort-icon"><?= $icon ?></span>
                            </a>
                        </th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php
                // DB 결과 반복 출력 (PreparedStatement 결과는 fetch_assoc() 메서드로 접근)
                while ($row = $res->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['region_name']) . "</td>";
                    // 숫자 컬럼은 number_format으로 보기 좋게 출력
                    echo "<td>" . number_format($row['max_price']) . "</td>";
                    echo "<td>" . number_format($row['min_price']) . "</td>";
                    echo "<td>" . number_format($row['total_stores']) . "</td>";
                    echo "<td>" . number_format($row['avg_rating'], 1) . "</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sortButtons = document.querySelectorAll('.sort-button');

            // 현재 URL에서 필터링 값 (min_rating)을 가져옵니다.
            const urlParams = new URLSearchParams(window.location.search);
            // min_rating은 입력 폼에서 처리하므로 여기서 빈 값 체크는 생략

            sortButtons.forEach(button => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();

                    const currentOrder = button.getAttribute('data-sort');
                    const column = button.getAttribute('data-col'); // PHP에서 설정한 컬럼명

                    let nextOrder = '';

                    // 1. 다음 정렬 순서를 결정합니다.
                    if (currentOrder === 'desc') {
                        nextOrder = 'ASC';
                    } else {
                        nextOrder = 'DESC';
                    }

                    // 2. URL 객체를 사용하여 모든 파라미터를 유지하면서 정렬만 변경
                    urlParams.set('sort', column);
                    urlParams.set('order', nextOrder);

                    // min_rating 값이 비어 있으면 URL에서 제거하여 깔끔하게 만듭니다.
                    if (!urlParams.get('min_rating')) {
                        urlParams.delete('min_rating');
                    } else if (urlParams.get('min_rating') === '0.0' || urlParams.get('min_rating') === '0') {
                        // min_rating이 0.0일 경우, URL을 깔끔하게 유지하기 위해 제거합니다. (PHP에서 기본값 0.0으로 설정되어 있기 때문)
                        urlParams.delete('min_rating');
                    }

                    // 최종 URL로 페이지 이동
                    window.location.href = window.location.pathname + '?' + urlParams.toString();
                });
            });
        });
    </script>
    </body>
    </html>
<?php
mysqli_close($conn);
?>
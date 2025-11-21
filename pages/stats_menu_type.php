<?php
include("../config.php");
include ("../menu.php");

// 1. GET 요청 변수 초기화 및 정리 (SQL 인젝션 방지 포함)
$default_sort_col = 'category_name';
$default_order = 'ASC';
$allowed_columns = ['category_name', 'max_price', 'min_price', 'total_stores', 'avg_rating'];

// 현재 정렬 컬럼 (GET 요청이 없으면 기본값 사용)
$sort_column = $_GET['sort'] ?? $default_sort_col;
// 현재 정렬 순서 (GET 요청이 없으면 기본값 사용)
$sort_order = strtoupper($_GET['order'] ?? $default_order);

// 요청된 컬럼이 허용 목록에 없거나, 순서가 ASC/DESC가 아니면 기본값으로 재설정
if (!in_array($sort_column, $allowed_columns) || !in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_column = $default_sort_col;
    $sort_order = $default_order;
}

// 2. SQL 쿼리 생성 및 실행
$sql = "SELECT
    c.category_name,
    COUNT(t.restaurant_id) AS total_stores, 
    MAX(m.price) AS max_price,
    MIN(m.price) AS min_price,
    AVG(rev.avg_rating) AS avg_rating 
    FROM 
        category c
    JOIN 
        restaurant t ON c.category_id = t.category_id
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
        c.category_id, c.category_name
    ORDER BY 
        {$sort_column} {$sort_order};";

$res = mysqli_query($conn, $sql);

// 쿼리 실패 시 처리
if (!$res) {
    // DB 연결 문제일 가능성이 높습니다.
    die("쿼리 오류: " . mysqli_error($conn) . "<br>SQL: " . $sql);
}
?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <title>Team03 Project</title>
        <link rel="stylesheet" href="../css/stats_style.css">
    </head>
    <body>
    <div class="content-container">
        <h1>메뉴 유형별 통계</h1>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                <tr>
                    <?php
                    // 테이블 헤더 반복 출력 및 정렬 상태 표시
                    $columns = [
                            'category_name' => '메뉴 유형',
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
                // DB 결과 반복 출력
                while ($row = mysqli_fetch_assoc($res)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
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

            sortButtons.forEach(button => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();

                    const currentOrder = button.getAttribute('data-sort');
                    const column = button.getAttribute('data-col'); // PHP에서 설정한 컬럼명

                    let nextOrder = '';

                    // 1. 다음 정렬 순서를 결정합니다.
                    // 'desc' 상태에서 클릭 -> 'asc' (오름차순)
                    if (currentOrder === 'desc') {
                        nextOrder = 'ASC';
                    }
                    // 'none' 또는 'asc' 상태에서 클릭 -> 'desc' (내림차순)
                    else {
                        nextOrder = 'DESC';
                    }

                    // 2. 결정된 순서로 서버에 정렬 요청을 보냅니다. (페이지 새로고침)

                    // 🚨 문제 해결 부분: 쿼리 파라미터를 완전히 새로 구성하여 전달
                    const currentPath = window.location.pathname; // 현재 페이지 경로(/BDA_team03/pages/stats_region.php)

                    // 새 쿼리 문자열 생성
                    const newQuery = `?sort=${column}&order=${nextOrder}`;

                    // URL을 새로운 경로와 쿼리 문자열로 변경
                    window.location.href = currentPath + newQuery;
                    // 예시: /BDA_team03/pages/stats_region.php?sort=max_price&order=DESC
                });
            });
        });
    </script>
    </body>
    </html>
<?php
mysqli_close($conn);
?>
<?php
include_once '../config.php';
session_start();

// 로그인 체크 (관리자만 접근 가능)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    die("관리자만 접근할 수 있는 페이지입니다.");
}

// 필터 & 검색 입력 텍스트 가져오기
$filter_column = $_GET['filter_column'] ?? '';
$search_text = $_GET['search_text'] ?? '';

$sql = "SELECT r.restaurant_id, r.name AS restaurant_name, re.region_name, c.category_name, r.created_at, r.updated_at
        FROM restaurant r
        JOIN region re ON r.region_id = re.region_id
        JOIN category c ON r.category_id = c.category_id";

// 검색 조건 (입력 텍스트) sql문에 적용
$params = [];
$types = '';

if ($filter_column && $search_text) {
    $sql .= " WHERE ";
    if ($filter_column === 'name') {
        $sql .= "r.name LIKE ?";
    } elseif ($filter_column === 'region') {
        $sql .= "re.region_name LIKE ?";
    } elseif ($filter_column === 'category') {
        $sql .= "c.category_name LIKE ?";
    }
    $params[] = "%$search_text%";
    $types .= 's';
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 식당 삭제 처리 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_restaurant_id'])) {

    $restaurant_id = $_POST['delete_restaurant_id'];

    // 외래키 때문에 메뉴/휴무일 테이블에서 먼저 삭제
    $conn->query("DELETE FROM Menu WHERE restaurant_id = $restaurant_id");
    $conn->query("DELETE FROM Closed_Days WHERE restaurant_id = $restaurant_id");

    // 레스토랑 테이블에서 삭제
    $conn->query("DELETE FROM Restaurant WHERE restaurant_id = $restaurant_id");

    echo "<script>alert('식당이 삭제되었습니다.'); 
    location.href='admin_dashboard.php';</script>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>식당 관리 대시보드</title>
<style>
body {
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
    background: #ffffff;
    margin: 0;
    padding: 20px;
    color: #111;
}

.dashboard-container {
    max-width: 1000px;
    margin: 0 auto;
}

.dashboard-title {
    text-align: center;
    font-size: 32px;
    font-weight: 900;
    margin-top: 30px;
    margin-bottom: 50px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.dashboard-form {
    display: flex;
    gap: 10px;
}

.dashboard-form select,
.dashboard-form input[type="text"],
.dashboard-form input[type="submit"] {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 16px;
}

.dashboard-form input[type="submit"] {
    border: none;
    background-color: #F47320; 
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.btn-add {
    padding: 8px 16px;
    background-color: #20B2AA; 
    color: white;
    font-weight: bold;
    border-radius: 6px;
    text-decoration: none;
    transition: 0.2s;
}

.dashboard-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.dashboard-table th,
.dashboard-table td {
    padding: 12px 10px;
    text-align: center;
    border-bottom: 1px solid #ccc;
}

.dashboard-table th {
    font-weight: 700;
    background-color: #fdf7ec; 
}

.btn-edit, .btn-delete {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    background-color: #F47320; 
    color: white;
}
</style>

</head>
<body>

<?php include '../menu.php'; ?>

<div class="dashboard-container">
    <h1 class="dashboard-title">식당 관리</h1>

    <div class="dashboard-header">
        <form class="dashboard-form" method="get">
            <select name="filter_column">
                <option value="">-- 선택 --</option>
                <option value="name" <?= ($filter_column==='name')?'selected':'' ?>>식당 이름</option>
                <option value="region" <?= ($filter_column==='region')?'selected':'' ?>>지역</option>
                <option value="category" <?= ($filter_column==='category')?'selected':'' ?>>메뉴 유형</option>
            </select>

            <input type="text" name="search_text" placeholder="검색어 입력" value="<?= htmlspecialchars($search_text) ?>">
            <input type="submit" value="검색">
        </form>

        <a href="admin_add_restaurant.php" class="btn-add">새로운 식당 추가</a>
    </div>

    <table class="dashboard-table">
        <tr>
            <th>식당 이름</th>
            <th>지역</th>
            <th>메뉴 유형</th>
            <th>생성 시간</th>
            <th>수정 시간</th>
            <th>수정</th>
            <th>삭제</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['restaurant_name']) ?></td>
            <td><?= htmlspecialchars($row['region_name']) ?></td>
            <td><?= htmlspecialchars($row['category_name']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td><?= htmlspecialchars($row['updated_at']) ?></td>
            <td>
                <a href="admin_edit_restaurant.php?id=<?= $row['restaurant_id'] ?>">
                    <button type="button" class="btn-edit">수정</button>
                </a>
            </td>
            <td>
                <form method="post" onsubmit="return confirm('정말 이 식당을 삭제하시겠습니까?');">
                    <input type="hidden" name="delete_restaurant_id" value="<?= $row['restaurant_id'] ?>">
                    <button type="submit" class="btn-delete">삭제</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

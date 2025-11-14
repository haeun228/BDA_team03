<?php
$mysqli = new mysqli("localhost", "team03", "team03", "team03");
if ($mysqli->connect_error) {
    die("DB 연결 실패: " . $mysqli->connect_error);
}

// 필터 & 검색 입력 텍스트 가져오기
$filter_column = $_GET['filter_column'] ?? '';
$search_text = $_GET['search_text'] ?? '';

$sql = "SELECT r.name AS restaurant_name, re.region_name, c.category_name, r.created_at, r.updated_at
        FROM restaurant r
        JOIN region re ON r.region_id = re.region_id
        JOIN category c ON r.category_id = c.category_id";

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

$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>식당 관리 대시보드</title>
</head>
<body>

<h1>식당 관리</h1>

<form method="get">
    <label for="filter_column">필터:</label>
    <select name="filter_column" id="filter_column">
        <option value="">-- 선택 --</option>
        <option value="name" <?= ($filter_column==='name')?'selected':'' ?>>식당 이름</option>
        <option value="region" <?= ($filter_column==='region')?'selected':'' ?>>지역</option>
        <option value="category" <?= ($filter_column==='category')?'selected':'' ?>>메뉴 유형</option>
    </select>

    <input type="text" name="search_text" placeholder="검색어 입력" value="<?= htmlspecialchars($search_text) ?>">
    <input type="submit" value="검색">
</form>

<table border="1" cellspacing="0" cellpadding="5">
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
            버튼
        </td>
        <td>
            버튼
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>

<?php
$stmt->close();
$mysqli->close();
?>

<?php
include_once '../config.php';
session_start();

// 로그인 체크 (관리자만 접근 가능)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo "<p>관리자만 접근 가능합니다.</p>";
    exit;
}

// GET으로 전달된 특정 식당 ID
$restaurant_id = $_GET['id'] ?? null;
if (!$restaurant_id) {
    echo "<p>식당을 찾을 수 없습니다.</p>";
    exit;
}

// 지역, 메뉴 유형 가져오기
$regions = [];
$categories = [];

$region_result = $conn->query("SELECT region_id, region_name FROM region");
while ($row = $region_result->fetch_assoc()) {
    $regions[] = $row;
}

$category_result = $conn->query("SELECT category_id, category_name FROM category");
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}

// 식당 정보 가져오기
$stmt = $conn->prepare("SELECT * FROM restaurant WHERE restaurant_id = ?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
$restaurant = $result->fetch_assoc();
if (!$restaurant) {
    echo "<p>식당을 찾을 수 없습니다.</p>";
    exit;
}

// 메뉴 정보 가져오기
$menu_result = $conn->query("SELECT * FROM Menu WHERE restaurant_id = $restaurant_id");
$menus = [];
while ($row = $menu_result->fetch_assoc()) {
    $menus[] = $row;
}

// 휴무일 정보 가져오기
$closed_result = $conn->query("SELECT day FROM Closed_Days WHERE restaurant_id = $restaurant_id");
$closeds = [];
while ($row = $closed_result->fetch_assoc()) {
    $closeds[] = $row['day'];
}

// 식당 정보 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $region_id = $_POST['region_id'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $open_time = $_POST['start_time'];
    $close_time = $_POST['end_time'];
    $closed_days = $_POST['closed'] ?? []; // 배열 (휴무일 여러 요일 가능)

    // 1) 식당 테이블 내 정보 업데이트
    $sql = "UPDATE Restaurant SET name=?, description=?, region_id=?, category_id=?, open_time=?, close_time=? WHERE restaurant_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssi", $name, $description, $region_id, $category_id, $open_time, $close_time, $restaurant_id);
    $stmt->execute();

    // 2) 메뉴 테이블 내 정보 업데이트
    $menu_result = $conn->query("SELECT * FROM Menu WHERE restaurant_id=$restaurant_id ORDER BY menu_id");
    $existing_menus = $menu_result->fetch_all(MYSQLI_ASSOC);

    for ($i = 0; $i < 3; $i++) {
        $menu_name = $_POST["menu" . ($i+1)];
        $price = $_POST["price" . ($i+1)];
        if (!empty($menu_name) && !empty($price)) {
            $stmt = $conn->prepare("UPDATE Menu SET menu_name=?, price=? WHERE menu_id=?");
            $stmt->bind_param("sii", $menu_name, $price, $existing_menus[$i]['menu_id']);
            $stmt->execute();
        }
    }

    // 3) 휴무일 테이블 내 정보 업데이트 (삽입 또는 삭제)
    // 기존 휴무일
    $existing_days = [];
    $result = $conn->query("SELECT day FROM Closed_Days WHERE restaurant_id=$restaurant_id");
    while ($row = $result->fetch_assoc()) {
        $existing_days[] = $row['day'];
    }

    // 기존 휴무일 X, 체크 O -> 추가(INSERT)
    foreach ($closed_days as $day) {
        if (!in_array($day, $existing_days)) {
            $stmt = $conn->prepare("INSERT INTO Closed_Days (restaurant_id, day) VALUES (?, ?)");
            $stmt->bind_param("is", $restaurant_id, $day);
            $stmt->execute();
        }
    }

    // 기존 휴무일 O, 체크 X -> 삭제(DELETE)
    foreach ($existing_days as $day) {
        if (!in_array($day, $closed_days)) {
            $stmt = $conn->prepare("DELETE FROM Closed_Days WHERE restaurant_id=? AND day=?");
            $stmt->bind_param("is", $restaurant_id, $day);
            $stmt->execute();
        }
    }


    echo "<script>alert('식당 정보가 수정되었습니다.'); location.href='admin_dashboard.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>식당 수정</title>
</head>
<style>
body {
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial;
    background: #ffffff;
    padding: 30px;
}

.form-container {
    max-width: 600px;
    margin: 0 auto;
    background: #fdf7ec;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.form-container h2 {
    text-align: center;
    margin-bottom: 25px;
    font-size: 24px;
    font-weight: 900;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 6px;
}

/* 공통 input/select/textarea 스타일 */
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 14px;
    font-size: 16px;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial;
    border-radius: 6px;
    border: 1px solid #ccc;
}

/* 메뉴 이름 + 가격 가로 정렬 */
.menu-row {
    display: flex;
    gap: 10px;
}

.menu-row input {
    flex: 1;
}

.menu-row input.price {
    max-width: 120px;
}

/* 제출 버튼 */
.submit-btn {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    font-size: 18px;
    font-weight: bold;
    background: #F47320;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

/* 휴무일 체크박스 스타일 */
.closed-box {
    display: grid;
    grid-template-columns: repeat(7, 1fr); /* 7요일 한줄 */
    gap: 12px;
}

.closed-item {
    position: relative;
    padding-left: 30px;
    margin-top: 5px;
    cursor: pointer;
    user-select: none;
    font-size: 15px;
}

/* 실제 체크박스 숨기기 */
.closed-item input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

/* 커스텀 체크박스 */
.closed-item .checkmark {
    position: absolute;
    left: 0;
    top: 0;
    height: 22px;
    width: 22px;
    background-color: #fff;
    border: 2px solid #aaa;
    border-radius: 4px;
    transition: 0.2s;
}

/* 체크 시 스타일 */
.closed-item input:checked + .checkmark {
    background-color: #F47320;
    border-color: #F47320;
}

/* 체크 표시 */
.closed-item .checkmark::after {
    content: "";
    position: absolute;
    display: none;
}

.closed-item input:checked + .checkmark::after {
    display: block;
    left: 6px;
    top: 2px;
    width: 6px;
    height: 12px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
/* 가격 스피너 버튼 제거 */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>

<body>

<?php include '../menu.php'; ?>

<div class="form-container">
    <h2>식당 수정</h2>
    <form method="post">

        <div class="form-group">
            <label>식당 이름</label>
            <input type="text" name="name" value="<?= htmlspecialchars($restaurant['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>지역</label>
            <select name="region_id" required>
                <option value="">-- 선택 --</option>
                <?php foreach($regions as $r): ?>
                    <option value="<?= $r['region_id'] ?>" <?= $r['region_id']==$restaurant['region_id']?'selected':'' ?>>
                        <?= htmlspecialchars($r['region_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>메뉴 유형</label>
            <select name="category_id" required>
                <option value="">-- 선택 --</option>
                <?php foreach($categories as $c): ?>
                    <option value="<?= $c['category_id'] ?>" <?= $c['category_id']==$restaurant['category_id']?'selected':'' ?>>
                        <?= htmlspecialchars($c['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>식당 한줄 소개</label>
            <textarea name="description"><?= htmlspecialchars($restaurant['description']) ?></textarea>
        </div>

        <?php for($i=1; $i<=3; $i++):
            $menu = $menus[$i-1] ?? ['menu_name'=>'','price'=>''];
        ?>
        <div class="form-group">
            <label>메뉴 <?= $i ?></label>
            <div class="menu-row">
                <input type="text" name="menu<?= $i ?>" placeholder="메뉴 이름" value="<?= htmlspecialchars($menu['menu_name']) ?>">
                <input type="number" name="price<?= $i ?>" placeholder="가격" class="price" min="0" value="<?= htmlspecialchars($menu['price']) ?>">
            </div>
        </div>
        <?php endfor; ?>

        <div class="form-group">
            <label>시작 시간</label>
            <input type="time" name="start_time" value="<?= htmlspecialchars($restaurant['open_time']) ?>" required>
        </div>

        <div class="form-group">
            <label>종료 시간</label>
            <input type="time" name="end_time" value="<?= htmlspecialchars($restaurant['close_time']) ?>" required>
        </div>

        <div class="form-group">
            <label>휴무일</label>
            <div class="closed-box">
                <?php 
                $days = ['MON'=>'월요일','TUE'=>'화요일','WED'=>'수요일','THU'=>'목요일','FRI'=>'금요일','SAT'=>'토요일','SUN'=>'일요일'];
                foreach($days as $key=>$name): 
                ?>
                <label class="closed-item">
                    <input type="checkbox" name="closed[]" value="<?= $key ?>" <?= in_array($key, $closeds)?'checked':'' ?>>
                    <span class="checkmark"></span> <?= $name ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="submit-btn">수정 완료</button>
    </form>
</div>
</body>
</html>


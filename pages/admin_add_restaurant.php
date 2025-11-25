<!-- 2271012 김다은 -->
<?php
include_once '../config.php';
session_start();

// 로그인 체크 (관리자만 접근 가능)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    echo "<p>관리자만 접근 가능합니다.</p>";
    exit;
}

// 지역 목록 가져오기
$regions = [];
$region_result = $conn->query("SELECT region_id, region_name FROM region");
while ($row = $region_result->fetch_assoc()) {
    $regions[] = $row;
}

// 카테고리 목록 가져오기
$categories = [];
$category_result = $conn->query("SELECT category_id, category_name FROM category");
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}

// 식당 삽입(신규 생성) 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 트랜잭션 시작
    $conn->begin_transaction();

    try {
        $name = $_POST['name'];
        $region_id = $_POST['region_id'];
        $category_id = $_POST['category_id'];
        $description = $_POST['description'];
        $open_time = $_POST['start_time'];
        $close_time = $_POST['end_time'];
        $closed_days = $_POST['closed'] ?? []; // 휴무일이 둘 이상일 경우를 대비해서 배열로 처리

        // 1) 레스토랑 INSERT
        $sql = "INSERT INTO Restaurant (name, description, region_id, category_id, open_time, close_time)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisss", $name, $description, $region_id, $category_id, $open_time, $close_time);
        
        if (!$stmt->execute()) {
            throw new Exception("Restaurant INSERT failed: " . $stmt->error);
        }

        $restaurant_id = $conn->insert_id;
        $stmt->close();

        // 2) 메뉴 INSERT
        for ($i = 1; $i <= 3; $i++) {
            $menu_name = $_POST["menu$i"];
            $price = $_POST["price$i"];

            if (!empty($menu_name) && !empty($price)) {
                $sql = "INSERT INTO Menu (menu_name, price, restaurant_id) VALUES (?, ?, ?)";
                $stmt_menu = $conn->prepare($sql);
                $stmt_menu->bind_param("sii", $menu_name, $price, $restaurant_id);
                
                if (!$stmt_menu->execute()) {
                    throw new Exception("Menu INSERT failed: " . $stmt_menu->error);
                }
                $stmt_menu->close();
            }
        }

        // 3) 휴무일 INSERT
        if (!empty($closed_days)) {
            $sql = "INSERT INTO Closed_days (restaurant_id, day) VALUES (?, ?)";
            $stmt_closed = $conn->prepare($sql);

            foreach ($closed_days as $day) {
                $stmt_closed->bind_param("is", $restaurant_id, $day);
                
                if (!$stmt_closed->execute()) {
                    throw new Exception("Closed_days INSERT failed: " . $stmt_closed->error);
                }
            }
            $stmt_closed->close();
        }

        // 모든 작업 성공 시 트랜잭션 확정
        $conn->commit();
        echo "<script>alert('새로운 식당이 성공적으로 등록되었습니다.'); location.href='admin_dashboard.php';</script>";
        exit;

    } catch (Exception $e) {
        // 오류 발생 시 트랜잭션 롤백
        $conn->rollback();
        error_log("식당 등록 트랜잭션 실패: " . $e->getMessage());
        echo "<script>alert('식당 등록 중 오류가 발생했습니다. 식당이 등록되지 않았습니다.'); location.href='add_restaurant.php';</script>";
        exit;
    }
}
?>

<?php
if (isset($conn)) { $conn->close(); }
?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>새로운 식당 추가</title>
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
.holiday-box {
    display: grid;
    grid-template-columns: repeat(7, 1fr); /* 7요일 한줄 */
    gap: 12px;
}

.holiday-item {
    position: relative;
    padding-left: 30px;
    margin-top: 5px;
    cursor: pointer;
    user-select: none;
    font-size: 15px;
}

/* 실제 체크박스 숨기기 */
.holiday-item input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

/* 커스텀 체크박스 */
.holiday-item .checkmark {
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
.holiday-item input:checked + .checkmark {
    background-color: #F47320;
    border-color: #F47320;
}

/* 체크 표시 */
.holiday-item .checkmark::after {
    content: "";
    position: absolute;
    display: none;
}

.holiday-item input:checked + .checkmark::after {
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

</head>
<body>

<?php include '../menu.php'; ?>

<div class="form-container">
    <h2>식당 등록</h2>

    <form method="post">
        
        <div class="form-group">
            <label>식당 이름</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>지역</label>
            <select name="region_id" required>
                <option value="">-- 선택 --</option>
                <?php foreach($regions as $r): ?>
                <option value="<?= $r['region_id'] ?>"><?= htmlspecialchars($r['region_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>메뉴 유형</label>
            <select name="category_id" required>
                <option value="">-- 선택 --</option>
                <?php foreach($categories as $c): ?>
                <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>식당 간단 소개</label>
            <textarea name="description" rows="3" placeholder="간단한 소개를 입력하세요"></textarea>
        </div>

        <!-- 메뉴 1~3 -->
        <?php for($i=1; $i<=3; $i++): ?>
        <div class="form-group">
            <label>대표 메뉴 <?= $i ?></label>
            <div class="menu-row">
                <input type="text" name="menu<?= $i ?>" placeholder="메뉴 이름">
                <input type="number" name="price<?= $i ?>" placeholder="가격" class="price" min="0">
            </div>
        </div>
        <?php endfor; ?>

        <div class="form-group">
            <label>시작 시간</label>
            <input type="time" name="start_time" required>
        </div>

        <div class="form-group">
            <label>종료 시간</label>
            <input type="time" name="end_time" required>
        </div>

        <div class="form-group">
            <label>휴무일</label>
            <div class="holiday-box">
                <?php
                $days = [
                    "SUN" => "일요일",
                    "MON" => "월요일",
                    "TUE" => "화요일",
                    "WED" => "수요일",
                    "THU" => "목요일",
                    "FRI" => "금요일",
                    "SAT" => "토요일"
                ];

                foreach ($days as $value => $label): ?>
                    <label class="holiday-item">
                        <input type="checkbox" name="closed[]" value="<?= $value ?>">
                        <span class="checkmark"></span>
                        <?= $label ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="submit-btn">식당 생성</button>
    </form>
</div>


</body>
</html>

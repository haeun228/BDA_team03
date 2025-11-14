<?php
include_once '../config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$restaurant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';

$redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if (!$user_id) {
    echo "<script>
    alert('로그인이 필요합니다.');
    window.history.back(); 
    </script>";
    exit;
}

if ($restaurant_id <= 0 || !in_array($action, ['add', 'remove'])) {
    header("Location: index.php"); 
    exit;
}

try {
    if ($action === 'add') {
        $sql = "INSERT IGNORE INTO Bookmark (user_id, restaurant_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $restaurant_id);
        $stmt->execute();
    } elseif ($action === 'remove') {
        $sql = "DELETE FROM Bookmark WHERE user_id = ? AND restaurant_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $restaurant_id);
        $stmt->execute();
    }

    header("Location: ".$redirect_url);
    exit;

} catch (Exception $e) {
    error_log("Bookmark Error: " . $e->getMessage());
    header("Location: view_restaurant.php?id=" . $restaurant_id . "&error=db_failure");
    exit;
}
?>
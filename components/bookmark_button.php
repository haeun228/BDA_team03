<?php
// 유효성 검사
if (!isset($restaurant_id) || !is_numeric($restaurant_id) || $restaurant_id <= 0) {
    return;
}

$handler_path = '../handlers/handle_bookmark.php'; 
?>

<div class="bookmark">
  <button class="bookmark-btn" 
      onclick="location.href='<?php echo $handler_path; ?>?action=<?php echo $is_bookmarked ? 'remove' : 'add'; ?>&restaurant_id=<?php echo $restaurant_id; ?>'">
      
    <svg class="bookmark-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
      <path 
        d="M192 64C156.7 64 128 92.7 128 128L128 544C128 555.5 134.2 566.2 144.2 571.8C154.2 577.4 166.5 577.3 176.4 571.4L320 485.3L463.5 571.4C473.4 577.3 485.7 577.5 495.7 571.8C505.7 566.1 512 555.5 512 544L512 128C512 92.7 483.3 64 448 64L192 64z" 
        fill="<?php echo $is_bookmarked ? 'var(--accent)' : 'var(--muted)'; ?>" 
      />
    </svg>
  </button>
</div>
<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$input = '123456';
if (password_verify($input, $hash)) {
    echo "Password OK ✅";
} else {
    echo "Password FAIL ❌";
}
?>

<?php

$required = ['dom', 'xml', 'mbstring'];
$failed = false;

foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "[OK] {$ext}\n";
    } else {
        echo "[FAIL] {$ext} missing\n";
        $failed = true;
    }
}

echo $failed ? "Environment check FAILED\n" : "Environment check PASSED\n";

exit($failed ? 1 : 0);

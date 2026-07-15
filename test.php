<?php

require __DIR__ . '/vendor/autoload.php';

echo "<pre>";

echo "PHP Version: " . PHP_VERSION . "\n\n";

echo "Autoload exists: ";
echo file_exists(__DIR__ . '/vendor/autoload.php') ? "YES\n" : "NO\n";

echo "\nResend file:\n";
echo __DIR__ . "/vendor/resend/resend-php/src/Resend.php\n\n";

echo file_get_contents(__DIR__ . "/vendor/resend/resend-php/src/Resend.php");
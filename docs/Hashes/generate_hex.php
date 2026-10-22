<?php
$key = bin2hex(random_bytes(32));
$iv  = bin2hex(random_bytes(16));

echo "Key #1: {$key}\n";
echo "IV #1:  {$iv}\n";

file_put_contents(__DIR__ . '/key_hex.txt', $key . "\n");
file_put_contents(__DIR__ . '/iv_hex.txt', $iv . "\n");
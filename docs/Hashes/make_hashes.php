<?php
/**
 * Laboratory work #3 — Program and Data Security.
 * Builds the base string, pads it to 72 bytes (UTF-8), and produces Hashes #1..#6.
 *
 * Usage: php make_hashes.php
 *
 * bcrypt salts are random, so hash values differ on every run; the byte lengths,
 * the pairwise-verification result (task 7) and the algorithm behaviour do not.
 */

// --- Task 2: base string: [year]_[group]_[surname]_[name]_варіант:[variant]_ ---
$base = "2026_SE-23-1_Poberezhnyi_Mykola_варіант:12_";
$baseBytes = strlen($base);                 // strlen() counts bytes, not characters
echo "Base string:        {$base}\n";
echo "Length (chars):     " . mb_strlen($base, 'UTF-8') . "\n";
echo "Length (bytes):     {$baseBytes}\n";

// Pad with '_' up to exactly 72 bytes (each '_' is 1 byte in UTF-8)
$padLen = 72 - $baseBytes;
$s1 = $base . str_repeat('_', max(0, $padLen));
echo "Padded to bytes:    " . strlen($s1) . "\n";
echo "String #1 (72 B):   {$s1}\n\n";

// --- Tasks 4..6: three more strings derived from the 72-byte base ---
$s2 = $s1 . '1';                                        // task 4: + "1"  (73 bytes)
$rand10 = bin2hex(random_bytes(5));                     // task 5: 10 pseudo-random chars
$s3 = $s1 . $rand10;                                    // (82 bytes)
$s4 = substr($s1, 0, -1);                               // task 6: drop last byte (71 bytes)

$strings = [1 => $s1, 2 => $s2, 3 => $s3, 4 => $s4];

// --- Tasks 3..6: bcrypt hashes (default cost 10) ---
$hashes = [];
foreach ($strings as $i => $str) {
    $hashes[$i] = password_hash($str, PASSWORD_BCRYPT);
}

foreach ($strings as $i => $str) {
    echo "String #{$i} (" . strlen($str) . " B): {$str}\n";
    echo "Hash #{$i}:        {$hashes[$i]}\n";
}
echo "\n--- Task 7: pairwise verification (password_verify) ---\n";
echo "    " . implode("        ", array_map(fn($i) => "H#$i", array_keys($hashes))) . "\n";
foreach ($strings as $si => $str) {
    $row = "S#{$si} ";
    foreach ($hashes as $hi => $h) {
        $row .= "   " . (password_verify($str, $h) ? " OK " : "  . ") . "   ";
    }
    echo $row . "\n";
}

// --- Task 8: string "pass: [surname]" ---
$pass = "pass: Poberezhnyi";
echo "\nTask 8 string:      {$pass}\n";

// --- Task 10: SHA-256 -> Hash #5 ---
$hash5 = hash('sha256', $pass);
echo "Hash #5 (SHA-256):  {$hash5}\n";

// --- Task 11: bcrypt, 12 rounds -> Hash #6 ---
$hash6 = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Hash #6 (bcrypt-12):{$hash6}\n";

// Save the two targets so the brute-force scripts can read them
file_put_contents(__DIR__ . '/hash5_sha256.txt', $hash5 . "\n");
file_put_contents(__DIR__ . '/hash6_bcrypt12.txt', $hash6 . "\n");
echo "\nSaved hash5_sha256.txt and hash6_bcrypt12.txt for the brute-force step.\n";

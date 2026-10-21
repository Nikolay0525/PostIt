<?php
/**
 * Brute-force attack on a SHA-256 hash (task 9, 12).
 * Logic: for each candidate, compute its SHA-256 and strictly compare to the
 * reference hash; stop on the first match. Reports elapsed time in microseconds.
 *
 * Usage: php bruteforce_sha256.php [referenceHash]
 *        (defaults to the hash saved in hash5_sha256.txt)
 */
$reference = $argv[1] ?? trim(file_get_contents(__DIR__ . '/hash5_sha256.txt'));
$dictionary = require __DIR__ . '/dictionary.php';

echo "Algorithm:  SHA-256\n";
echo "Reference:  {$reference}\n";
echo "Candidates: " . count($dictionary) . "\n";

$start = hrtime(true);                       // fixed starting timestamp (nanoseconds)
$found = null;
$checked = 0;
foreach ($dictionary as $candidate) {
    $checked++;
    if (hash('sha256', $candidate) === $reference) {   // strict comparison of a freshly made hash
        $found = $candidate;
        break;
    }
}
$elapsedUs = (hrtime(true) - $start) / 1000; // nanoseconds -> microseconds

echo "Checked:    {$checked} candidate(s)\n";
echo $found !== null ? "FOUND:      {$found}\n" : "NOT FOUND\n";
echo "Elapsed:    " . number_format($elapsedUs, 1) . " microseconds\n";

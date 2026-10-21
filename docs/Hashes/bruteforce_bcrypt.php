<?php
/**
 * Brute-force attack on a bcrypt hash (task 9, 12).
 * Logic: for each candidate, call password_verify() against the reference hash
 * (bcrypt stores its own salt and cost, so no hash is re-created by hand);
 * stop on the first match. Reports elapsed time in microseconds.
 *
 * Usage: php bruteforce_bcrypt.php [referenceHash]
 *        (defaults to the hash saved in hash6_bcrypt12.txt)
 */
$reference = $argv[1] ?? trim(file_get_contents(__DIR__ . '/hash6_bcrypt12.txt'));
$dictionary = require __DIR__ . '/dictionary.php';

echo "Algorithm:  bcrypt (cost " . (explode('$', $reference)[2] ?? '?') . ")\n";
echo "Reference:  {$reference}\n";
echo "Candidates: " . count($dictionary) . "\n";

$start = hrtime(true);                       // fixed starting timestamp (nanoseconds)
$found = null;
$checked = 0;
foreach ($dictionary as $candidate) {
    $checked++;
    if (password_verify($candidate, $reference)) {     // bcrypt's own verification function
        $found = $candidate;
        break;
    }
}
$elapsedUs = (hrtime(true) - $start) / 1000; // nanoseconds -> microseconds

echo "Checked:    {$checked} candidate(s)\n";
echo $found !== null ? "FOUND:      {$found}\n" : "NOT FOUND\n";
echo "Elapsed:    " . number_format($elapsedUs, 1) . " microseconds\n";

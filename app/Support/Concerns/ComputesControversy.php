<?php

namespace App\Support\Concerns;

/**
 * Shared by PostResource and CommentResource: turns raw up/down vote counts into a rounded,
 * threshold-gated controversy score.
 *
 * Modelled on Reddit's own controversial-sort formula: `(upvotes + downvotes) ^ (min/max)`.
 * Total votes ("magnitude") is the base, so real engagement counts; the ratio between the
 * smaller and larger side is the exponent, so that base only counts in full when the votes
 * were actually split — a lopsided vote (balance near 0) collapses towards 1 no matter how
 * large the total was, while an even split (balance = 1) keeps the full magnitude. A raw vote
 * *sum* alone cannot do this job: it cannot tell "popular and loved" apart from "popular and
 * fought over", since both give the same sum.
 *
 * The result is intentionally unbounded, not a 0-100 percentage: a bigger, more contested item
 * reads as a bigger number, unlike a pure balance ratio, which would show the same value for a
 * 5/5 tie and a 500/500 tie.
 *
 * Rounding and the threshold are both load-bearing, not cosmetic:
 * - Below the threshold, null is returned so a freshly-voted item never shows a score
 *   indistinguishable from a genuinely contested one that nets to the same total.
 * - Above it, the value is rounded to 2 significant figures, because an exact value, combined
 *   with the already public net score (upvotes - downvotes), would let the exact vote split be
 *   reconstructed — exactly what keeping upvotes/downvotes private from each other guards against.
 */
trait ComputesControversy
{
    /** Minimum votes required on the smaller side before controversy is shown at all. */
    private const MIN_VOTES_PER_SIDE = 3;

    /** The displayed value is rounded to this many significant figures; never shown exactly. */
    private const SIGNIFICANT_FIGURES = 2;

    /**
     * @return int|null Rounded controversy score, or null when there are too few votes on
     *                  both sides for the number to mean anything.
     */
    protected function controversyScore(int $upvotes, int $downvotes): ?int
    {
        if (min($upvotes, $downvotes) < self::MIN_VOTES_PER_SIDE) {
            return null;
        }

        $magnitude = $upvotes + $downvotes;
        $balance = min($upvotes, $downvotes) / max($upvotes, $downvotes);

        return $this->roundToSignificantFigures($magnitude ** $balance, self::SIGNIFICANT_FIGURES);
    }

    private function roundToSignificantFigures(float $value, int $figures): int
    {
        if ($value <= 0) {
            return 0;
        }

        // Never scale below 1: the result must be a whole number, so a value under 100
        // (fewer significant digits than $figures) is simply rounded, not fractionally rescaled.
        $scale = max(1, 10 ** (floor(log10($value)) - $figures + 1));

        return (int) (round($value / $scale) * $scale);
    }
}

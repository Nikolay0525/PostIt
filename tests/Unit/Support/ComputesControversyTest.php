<?php

namespace Tests\Unit\Support;

use App\Support\Concerns\ComputesControversy;
use PHPUnit\Framework\TestCase;

class ComputesControversyTest extends TestCase
{
    // The trait's method is protected (it's meant to be called from within a Resource/Controller
    // that `use`s it, not called on some object from outside), so a tiny anonymous class stands
    // in for "a class that uses this trait" and exposes it publicly for the test to call.
    private function score(int $upvotes, int $downvotes): ?int
    {
        $subject = new class
        {
            use ComputesControversy;

            public function score(int $upvotes, int $downvotes): ?int
            {
                return $this->controversyScore($upvotes, $downvotes);
            }
        };

        return $subject->score($upvotes, $downvotes);
    }

    public function test_it_is_hidden_below_the_minimum_votes_per_side(): void
    {
        $this->assertNull($this->score(2, 2));
        $this->assertNull($this->score(3, 0));
        $this->assertNull($this->score(100, 1));
    }

    public function test_it_is_hidden_regardless_of_which_side_is_too_small(): void
    {
        $this->assertNull($this->score(10, 2));
        $this->assertNull($this->score(2, 10));
    }

    public function test_it_is_shown_once_both_sides_reach_the_threshold(): void
    {
        $this->assertNotNull($this->score(3, 3));
    }

    public function test_an_even_split_keeps_the_full_magnitude(): void
    {
        // magnitude = 6, balance = 3/3 = 1 -> 6^1 = 6
        $this->assertSame(6, $this->score(3, 3));
    }

    public function test_a_lopsided_vote_collapses_towards_one_no_matter_the_total(): void
    {
        // magnitude = 53, balance = 3/50 = 0.06 -> 53^0.06 rounds down to 1
        $this->assertSame(1, $this->score(3, 50));
    }

    public function test_it_does_not_care_which_side_is_up_or_down(): void
    {
        $this->assertSame($this->score(3, 50), $this->score(50, 3));
    }

    public function test_a_bigger_contested_item_reads_as_a_bigger_number(): void
    {
        // Not a 0-100 ratio: two evenly-split items of different sizes must not collapse to the
        // same score, unlike a pure balance ratio, which would score both a 5/5 and 500/500 tie
        // identically.
        $small = $this->score(3, 3);
        $large = $this->score(500, 500);

        $this->assertLessThan($large, $small);
        $this->assertSame(1000, $large);
    }

    public function test_the_result_is_rounded_to_two_significant_figures(): void
    {
        // magnitude = 137, balance = 68/69 ≈ 0.985 -> raw ≈ 129.6, rounded to 2 sig figs -> 130
        $this->assertSame(130, $this->score(69, 68));
    }
}

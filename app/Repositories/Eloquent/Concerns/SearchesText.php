<?php

namespace App\Repositories\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Plain LIKE search, the same on MySQL and sqlite (the test driver). Fine at the current size;
 * the planned step up is Laravel Scout + Meilisearch (typo tolerance, word forms, speed), which
 * these repository methods can be swapped to without touching their callers.
 *
 * Case: MySQL's default collation ignores case for every alphabet; sqlite's LIKE only for ASCII,
 * so tests use matching case for Cyrillic.
 */
trait SearchesText
{
    // '!' rather than '\': a backslash in an ESCAPE clause is spelled differently on MySQL and
    // sqlite, '!' is the same on both.
    private const LIKE_ESCAPE = '!';

    // The term with LIKE's own wildcards escaped, so "50%" or "snake_case" mean themselves.
    private function escapeLike(string $term): string
    {
        return str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE.self::LIKE_ESCAPE, self::LIKE_ESCAPE.'%', self::LIKE_ESCAPE.'_'],
            $term
        );
    }

    /**
     * Rows where any of the columns contains the term.
     *
     * @param  list<string>  $columns
     */
    private function whereContains(Builder $query, array $columns, string $term): Builder
    {
        $pattern = '%'.$this->escapeLike($term).'%';

        return $query->where(function (Builder $any) use ($columns, $pattern) {
            foreach ($columns as $column) {
                $any->orWhereRaw($column.' like ? escape \''.self::LIKE_ESCAPE.'\'', [$pattern]);
            }
        });
    }

    // Best match first, judged on the main column (a group's name, a post's title): the whole
    // value, then "starts with", then "contains", then rows that matched only on another column
    // (a group's description, a post's text).
    private function orderByMatch(Builder $query, string $column, string $term): Builder
    {
        $escaped = $this->escapeLike($term);
        $like = $column.' like ? escape \''.self::LIKE_ESCAPE.'\'';

        return $query->orderByRaw(
            'case when '.$like.' then 0 when '.$like.' then 1 when '.$like.' then 2 else 3 end',
            [$escaped, $escaped.'%', '%'.$escaped.'%']
        );
    }
}

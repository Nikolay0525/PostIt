<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

/**
 * Turns a post's Markdown source into HTML for display, and into plain text for previews/excerpts.
 *
 * `html_input: strip` and `allow_unsafe_links: false` are load-bearing, not defaults left
 * untouched: this is the only place user-authored article text turns into markup a client
 * renders with `v-html`, so raw HTML or a `javascript:` link pasted into the Markdown source
 * must never survive into what another reader's browser executes.
 */
trait RendersMarkdown
{
    protected function markdownToHtml(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    protected function markdownToPlainText(string $markdown): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($this->markdownToHtml($markdown))));
    }
}

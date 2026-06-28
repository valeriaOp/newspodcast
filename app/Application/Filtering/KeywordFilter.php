<?php

declare(strict_types=1);

namespace App\Application\Filtering;

use App\Domain\Article;

final class KeywordFilter
{
    /**
     * Returns true if the article passes the filters.
     *
     * Rules:
     *   - If any exclude keyword matches title or description → reject.
     *   - If include keywords are empty → accept.
     *   - Otherwise → accept only if at least one include keyword matches.
     *
     * @param  list<string>  $includeKeywords
     * @param  list<string>  $excludeKeywords
     */
    public function matches(Article $article, array $includeKeywords, array $excludeKeywords): bool
    {
        $haystack = mb_strtolower($article->title.' '.$article->description);

        foreach ($excludeKeywords as $kw) {
            if ($kw !== '' && str_contains($haystack, mb_strtolower($kw))) {
                return false;
            }
        }

        if ($includeKeywords === []) {
            return true;
        }

        foreach ($includeKeywords as $kw) {
            if ($kw !== '' && str_contains($haystack, mb_strtolower($kw))) {
                return true;
            }
        }

        return false;
    }
}

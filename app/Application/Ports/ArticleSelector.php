<?php

declare(strict_types=1);

namespace App\Application\Ports;

use App\Domain\Article;

interface ArticleSelector
{
    /**
     * Select up to $max most important articles from the candidate list.
     *
     * @param  list<Article>  $candidates
     * @return list<Article>
     */
    public function select(array $candidates, int $max): array;
}

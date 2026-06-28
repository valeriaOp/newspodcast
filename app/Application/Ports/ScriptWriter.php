<?php

declare(strict_types=1);

namespace App\Application\Ports;

use App\Domain\Article;
use App\Domain\Script;

interface ScriptWriter
{
    /**
     * Write a podcast script in $language synthesizing the given articles.
     *
     * @param  list<Article>  $articles
     */
    public function write(array $articles, string $language, int $maxChars): Script;
}

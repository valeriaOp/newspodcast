<?php

declare(strict_types=1);

namespace App\Application\Ports;

use App\Domain\Episode;

interface EpisodePublisher
{
    public function publish(Episode $episode): void;
}

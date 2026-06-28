<?php

declare(strict_types=1);

namespace App\Application\Ports;

use App\Domain\AudioBlob;
use App\Domain\Script;

interface SpeechSynthesizer
{
    public function synthesize(Script $script): AudioBlob;
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Gemini;

use App\Application\Ports\SpeechSynthesizer;
use App\Domain\AudioBlob;
use App\Domain\Script;
use RuntimeException;

final class GeminiSpeechSynthesizer implements SpeechSynthesizer
{
    private const MODEL = 'gemini-2.5-flash-preview-tts';

    private const SAMPLE_RATE = 24000;

    public function __construct(
        private readonly GeminiHttpClient $client,
        private readonly string $voice1 = 'Puck',
        private readonly string $voice2 = 'Aoede',
        private readonly string $styleInstruction = '',
    ) {}

    public function synthesize(Script $script): AudioBlob
    {
        $text = $this->styleInstruction !== ''
            ? trim($this->styleInstruction)."\n\n".$script->text
            : $script->text;

        $response = $this->client->generateContent(self::MODEL, [
            'contents' => [['parts' => [['text' => $text]]]],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'speechConfig' => [
                    'multiSpeakerVoiceConfig' => [
                        'speakerVoiceConfigs' => [
                            [
                                'speaker' => 'Speaker1',
                                'voiceConfig' => [
                                    'prebuiltVoiceConfig' => ['voiceName' => $this->voice1],
                                ],
                            ],
                            [
                                'speaker' => 'Speaker2',
                                'voiceConfig' => [
                                    'prebuiltVoiceConfig' => ['voiceName' => $this->voice2],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], timeout: 300);

        $base64 = $response['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? null;
        if (! is_string($base64) || $base64 === '') {
            throw new RuntimeException('Gemini TTS returned no audio data');
        }

        $pcm = base64_decode($base64, strict: true);
        if ($pcm === false) {
            throw new RuntimeException('Failed to decode Gemini TTS base64 audio');
        }

        return new AudioBlob(
            bytes: $pcm,
            format: 'pcm',
            sampleRate: self::SAMPLE_RATE,
            channels: 1,
            bitsPerSample: 16,
        );
    }
}

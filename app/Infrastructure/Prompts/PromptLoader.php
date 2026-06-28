<?php

declare(strict_types=1);

namespace App\Infrastructure\Prompts;

use RuntimeException;

final class PromptLoader
{
    public function __construct(private readonly string $promptsDir) {}

    /**
     * Load a prompt template from {promptsDir}/{name}.txt and substitute
     * {{key}} placeholders with values from $vars.
     *
     * @param  array<string, string|int>  $vars
     */
    public function load(string $name, array $vars = []): string
    {
        $path = rtrim($this->promptsDir, '/').'/'.$name.'.txt';

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Prompt template not found or unreadable: {$path}");
        }

        $template = file_get_contents($path);
        if ($template === false) {
            throw new RuntimeException("Failed to read prompt template: {$path}");
        }

        $replacements = [];
        foreach ($vars as $key => $value) {
            $replacements['{{'.$key.'}}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }
}

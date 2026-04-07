<?php

namespace App\Services;

class EmailTemplateRenderer
{
    public function render(string $bodyHtml, array $variables = []): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            static function (array $matches) use ($variables): string {
                $key = $matches[1];
                $value = $variables[$key] ?? '';

                return e((string) $value);
            },
            $bodyHtml
        );
    }

    public function extractVariables(string $bodyHtml): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $bodyHtml, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}

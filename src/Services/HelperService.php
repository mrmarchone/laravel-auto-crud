<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Services;

use function Laravel\Prompts\multiselect;

class HelperService
{
    public static function formatArrayToPhpSyntax(array $data, bool $removeValuesQuotes = false, int $indentation = 12): string
    {
        $indent = str_repeat(' ', $indentation);
        $formattedArray = "[\n";

        foreach ($data as $key => $value) {
            if ($removeValuesQuotes) {
                $formattedArray .= $indent."'$key' => $value,\n";
            } else {
                $formattedArray .= $indent."'$key' => '$value',\n";
            }
        }

        $formattedArray .= str_repeat(' ', $indentation - 4).']';

        return $formattedArray;
    }

    public static function toSnakeCase(string $text): string
    {
        // Convert camelCase or PascalCase (ModelName -> model_name)
        $text = preg_replace('/([a-z])([A-Z])/', '$1_$2', $text);

        // Replace any non-alphanumeric characters (spaces, dashes, etc.) with underscores
        $text = preg_replace('/[^a-zA-Z0-9]+/', '_', $text);

        // Convert to lowercase
        return strtolower(trim($text, '_'));
    }

    public static function displaySignature(): void
    {
        $asciiArt = <<<ASCII
                _           _____ _____  _    _ _____
     /\        | |         / ____|  __ \| |  | |  __ \
    /  \  _   _| |_ ___   | |    | |__) | |  | | |  | |
   / /\ \| | | | __/ _ \  | |    |  _  /| |  | | |  | |
  / ____ \ |_| | || (_) | | |____| | \ \| |__| | |__| |
 /_/    \_\__,_|\__\___/   \_____|_|  \_\\____/|_____/
                                         Free Palestine
ASCII;

        echo "\n$asciiArt\n\n";
        echo "[+] Name: Abdelrahman Muhammed\n";
        echo "[+] Email: mrmarchone@gmail.com\n";
    }

    public static function askForType($inputType, ?array $types = []): void
    {
        $newTypes = count($types) > 0 ? $types : multiselect(
            label: 'Do you want to create an "api", "web" or "both" controller?',
            options: ['api', 'web'],
            default: ['api'],
            required: true,
            validate: function ($value) {
                if (empty(array_intersect($value, ['api', 'web']))) {
                    return 'Please enter a valid type api or web';
                }

                return null;
            },
            hint: 'Select api, web or both',
        );

        $types = array_map('strtolower', $newTypes);

        $inputType->setOption('type', $types);
    }
}

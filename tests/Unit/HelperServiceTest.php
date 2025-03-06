<?php

use Mrmarchone\LaravelAutoCrud\Services\HelperService;

it('displays signature with correct ASCII art and information', function () {
    // Arrange
    $expectedOutput = <<<EXPECTED

                _           _____ _____  _    _ _____
     /\        | |         / ____|  __ \| |  | |  __ \
    /  \  _   _| |_ ___   | |    | |__) | |  | | |  | |
   / /\ \| | | | __/ _ \  | |    |  _  /| |  | | |  | |
  / ____ \ |_| | || (_) | | |____| | \ \| |__| | |__| |
 /_/    \_\__,_|\__\___/   \_____|_|  \_\\____/|_____/
                                         Free Palestine

[+] Name: Abdelrahman Muhammed
[+] Email: mrmarchone@gmail.com

EXPECTED;

    // Capture output
    ob_start();
    // Act
    HelperService::displaySignature();
    $actualOutput = ob_get_clean();
    // Assert
    expect($actualOutput)->toBe($expectedOutput);
});

it('format array to php syntax', function () {
    $expected = HelperService::formatArrayToPhpSyntax(['testing' => 'me']);
    $indent = 12;
    $indentation = str_repeat(' ', $indent);
    $anotherIndent = str_repeat(' ', $indent - 4);
    expect($expected)->toBe("[\n{$indentation}'testing' => 'me',\n{$anotherIndent}]");
});

it('format array to php syntax with remove value quotes', function () {
    $expected = HelperService::formatArrayToPhpSyntax(['testing' => 'me'], true);
    $indent = 12;
    $indentation = str_repeat(' ', $indent);
    $anotherIndent = str_repeat(' ', $indent - 4);
    expect($expected)->toBe("[\n{$indentation}'testing' => me,\n{$anotherIndent}]");
});

it('convert to snake case', function () {
    $expected = HelperService::toSnakeCase('hello world');
    expect($expected)->toBe('hello_world');
});

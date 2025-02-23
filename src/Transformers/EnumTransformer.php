<?php

namespace Mrmarchone\LaravelAutoCrud\Transformers;

class EnumTransformer
{
    public static function convertDataToString(array $data): string
    {
        $string = '';
        $indent = str_repeat(' ', 4);
        foreach ($data as $value) {
            $string .= $indent.'case '.$value.' = '."'$value';"."\n";
        }

        return $string;
    }
}

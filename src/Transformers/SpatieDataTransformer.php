<?php

namespace Mrmarchone\LaravelAutoCrud\Transformers;

class SpatieDataTransformer
{
    public static function convertDataToString(array $data): string
    {
        $string = '';
        $indent = str_repeat(' ', 4);
        foreach ($data as $key => $value) {
            $string .= $indent.$value."\n".$indent.$key."\n";
        }

        return $string;
    }

    public static function convertNamespacesToString(array $data): string
    {
        $string = '';
        foreach ($data as $value) {
            $string .= $value."\n";
        }

        return $string;
    }
}

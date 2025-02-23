<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Mrmarchone\LaravelAutoCrud\Transformers\EnumTransformer;

class EnumBuilder extends BaseBuilder
{
    public function create(array $modelData, array $values, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'enum', 'Enums', 'Enum', $overwrite, function ($modelData) use ($values) {
            return [
                '{{ data }}' => EnumTransformer::convertDataToString($values),
            ];
        });
    }
}

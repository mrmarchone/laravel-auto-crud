<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Services;

use Mrmarchone\LaravelAutoCrud\Builders\DocumentationBuilders\CURLBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\DocumentationBuilders\PostmanBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\DocumentationBuilders\SwaggerAPIBuilder;

class DocumentationGenerator
{
    public function __construct(private PostmanBuilder $postmanBuilder,
        private SwaggerAPIBuilder $swaggerAPIBuilder,
        private CURLBuilder $CURLBuilder)
    {
        $this->CURLBuilder = new CURLBuilder;
        $this->postmanBuilder = new PostmanBuilder;
        $this->swaggerAPIBuilder = new SwaggerAPIBuilder;
    }

    public function generate(array $modelData, array $options, bool $multipleModels = false): void
    {
        if (in_array('api', $options['type'])) {
            $overwrite = $options['overwrite'];

            if ($multipleModels && $options['overwrite']) {
                $overwrite = false;
            }

            $this->generatePostman($modelData, $options['postman'], $overwrite);
            $this->generateCURL($modelData, $options['curl'], $overwrite);
            $this->generateSwaggerAPI($modelData, $options['swagger-api'], $overwrite);
        }
    }

    private function generatePostman(array $modelData, bool $generate, bool $overwrite): void
    {
        if ($generate) {
            $this->postmanBuilder->create($modelData, $overwrite);
        }
    }

    private function generateCURL(array $modelData, bool $generate, bool $overwrite): void
    {
        if ($generate) {
            $this->CURLBuilder->create($modelData, $overwrite);
        }
    }

    private function generateSwaggerAPI(array $modelData, bool $generate, bool $overwrite): void
    {
        if ($generate) {
            $this->swaggerAPIBuilder->create($modelData, $overwrite);
        }
    }
}

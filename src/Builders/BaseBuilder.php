<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Mrmarchone\LaravelAutoCrud\Services\FileService;

abstract class BaseBuilder
{
    protected FileService $fileService;

    public function __construct()
    {
        $this->fileService = new FileService();
    }
}

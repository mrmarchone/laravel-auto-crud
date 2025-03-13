<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;
use Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;
use Mrmarchone\LaravelAutoCrud\Traits\TableColumnsTrait;

use function Laravel\Prompts\info;

class ViewBuilder
{
    use TableColumnsTrait;

    public function __construct()
    {
        $this->modelService = new ModelService;
        $this->tableColumnsService = new TableColumnsService;
    }

    public function create(array $modelData, $overwrite = false): void
    {
        $data = $this->getAvailableColumns($modelData);
        $modelName = HelperService::toSnakeCase(Str::plural($modelData['modelName']));
        $viewPath = base_path("resources/views/{$modelName}");

        if (! is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        $modelName = $modelData['modelName'];
        $methods = [
            'index' => $this->generateIndexPage($modelName, $data),
            'show' => $this->generateViewPage($modelName, $data),
            'create' => $this->generateCreatePage($modelName, $data),
            'edit' => $this->generateEditPage($modelName, $data),
        ];

        foreach ($methods as $view => $data) {
            $filePath = "$viewPath/{$view}.blade.php";
            if (! file_exists($filePath)) {
                file_put_contents($filePath, $data);
                info('Created '.$filePath);
            } else {
                if ($overwrite) {
                    file_put_contents($filePath, $data);
                    info('Created '.$filePath);
                }
            }
        }
    }

    private function generateViewPage(string $modelName, array $data): string
    {
        $var = lcfirst($modelName);
        $html = '';
        foreach ($data as $item) {
            $html .= '<p><strong>'.$item['name'].':</strong> {{ $'.$var.' ->'.$item['name'].' }}</p>'."\n";
        }

        return <<<BLADE
<div class="container">
    <h2>$var Details</h2>
     $html
</div>
BLADE;
    }

    private function generateIndexPage(string $modelName, array $data): string
    {
        $var = HelperService::toSnakeCase(Str::plural($modelName));
        $header = '<tr>';
        $body = '';
        foreach ($data as $item) {
            $header .= '<th>'.$item['name'].'</th>';
            $body .= '<td>{{$item->'.$item['name'].'}}</td>'."\n";
        }
        $header .= '</tr>';

        return <<<BLADE
            <div class="container">
            <h2>$var List</h2>
            <a href="{{ route('$var.create') }}" class="btn btn-primary mb-3">Create $var</a>
            <table class="table">
                <thead>
                    $header
                </thead>
                <tbody>
                    @foreach (\$$var as \$item)
                            <tr>
                                $body<td>
                                    <a href="{{ route('$var.edit', \$item->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="{{ route('$var.destroy', \$item->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            BLADE;
    }

    private function generateCreatePage(string $modelName, array $data): string
    {
        $var = HelperService::toSnakeCase(Str::plural($modelName));
        $html = '';
        foreach ($data as $item) {
            $html .= '<div class="mb-3">
            <label for="'.$item['name'].'" class="form-label">'.$item['name'].'</label>
            <input type="text" class="form-control" name="'.$item['name'].'" value="{{old("'.$item['name'].'")}}">
            @error("'.$item['name'].'")
                <p>{{$message}}</p>
            @enderror
        </div>'."\n";
        }

        return <<<BLADE
<div class="container">
    <h2>Create $var</h2>
    <form action="{{ route('$var.store') }}" method="POST">
        @csrf
        $html
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
BLADE;
    }

    private function generateEditPage(string $modelName, array $data): string
    {
        $var = lcfirst($modelName);
        $plural = HelperService::toSnakeCase(Str::plural($modelName));
        $parameter = '$'.$var.'->id';
        $html = '';
        foreach ($data as $item) {
            $html .= '<div class="mb-3">
            <label for="'.$item['name'].'" class="form-label">'.$item['name'].'</label>
            <input type="text" class="form-control" name="'.$item['name'].'" value="{{old("'.$item['name'].'", $'.$var.'["'.$item['name'].'"])}}">
            @error("'.$item['name'].'")
                <p>{{$message}}</p>
            @enderror
        </div>'."\n";
        }

        return <<<BLADE
<div class="container">
    <h2>Edit $var</h2>
    <form action="{{ route('$plural.update', $parameter) }}" method="POST">
        @csrf
        @method("PATCH")
        $html
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
BLADE;
    }
}

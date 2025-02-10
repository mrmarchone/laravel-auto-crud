# Laravel Auto CRUD Generator

![Laravel Auto CRUD](images/laravel-auto-crud.png)

A Laravel package that automates the generation of CRUD (Create, Read, Update, Delete) operations for your models, making development faster and more efficient.

[Watch the Video on YouTube](https://www.youtube.com/watch?v=6IqRc3OgUIM)

## Installation

You can install the package via Composer:

```bash
composer require mrmarchone/laravel-auto-crud --dev
```

## Usage

To generate CRUD operations for a model, use the following Artisan command:

```bash
php artisan auto-crud:generate
```

### Example:

```bash
php artisan auto-crud:generate
```

This will generate:
- Controller
- Request
- Resource
- Routes
- Views (if applicable)
- CURL (if applicable)


## Requirements

- Laravel 10+
- PHP 8.0+

## Contributing

Contributions are welcome! Feel free to open an issue or submit a pull request.

## License

This package is open-source and available under the MIT License.


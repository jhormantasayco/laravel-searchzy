# Laravel Searchzy

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jhormantasayco/laravel-searchzy/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jhormantasayco/laravel-searchzy/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/jhormantasayco/laravel-searchzy/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jhormantasayco/laravel-searchzy/build-status/master)
[![Build Status](https://travis-ci.org/jhormantasayco/laravel-searchzy.svg?branch=master)](https://travis-ci.org/jhormantasayco/laravel-searchzy.svg?branch=master)
[![Latest Stable on Packagist](https://poser.pugx.org/jhormantasayco/laravel-searchzy/v/stable)](https://packagist.org/packages/jhormantasayco/laravel-searchzy)
[![Total Downloads](https://poser.pugx.org/jhormantasayco/laravel-searchzy/downloads)](https://packagist.org/packages/jhormantasayco/laravel-searchzy)
[![License](https://poser.pugx.org/jhormantasayco/laravel-searchzy/license)](https://packagist.org/packages/jhormantasayco/laravel-searchzy)
[![Sonar Cloud](https://sonarcloud.io/api/project_badges/measure?project=jhormantasayco_laravel-searchzy&metric=alert_status)](https://sonarcloud.io/dashboard?id=jhormantasayco_laravel-searchzy)

[![Quality gate](https://sonarcloud.io/api/project_badges/quality_gate?project=jhormantasayco_laravel-searchzy)](https://sonarcloud.io/dashboard?id=jhormantasayco_laravel-searchzy)

Simple and lightweight search to eloquent models.

## Instalación

Puedes instalar el package vía composer:

```bash
composer require jhormantasayco/laravel-searchzy
```

## Uso en los Models

Para añadir searchzy deberas de hace lo siguiente:

1. Usar el trait `Jhormantasayco\LaravelSearchzy\Searchzy` en tus modelos.
2. Especificar mediante un array asociativo que columnas serán usadas por searchzy para la búsqueda y filtrado (Las keys del array se refieren a los inputs del request y los values represetan las columnas - relaciones de búsqueda).

Aquí hay un ejemplo:

``` php
use Jhormantasayco\LaravelSearchzy\Searchzy;

class MyModel extends Model
{
    use Searchzy;

    /**
     * The attributes that are searchable.
     *
     * @var array
     */
    protected $searchable = [
        'nombre'      => 'name',
        'dni'         => 'code',
        'phone'       => 'phone',
        'email'       => 'email',
        'post'        => 'posts:title',
        'descripcion' => 'posts:description',
    ];

    /**
     * The attributes that are filterable.
     *
     * @var array
     */
    protected $filterable = [
        'rol_id' => 'role_id',
    ];
}
```
## Uso en los Controllers

Simplemente tienes que añadir el scope de `searchzy` en tus consultas y listo. El resultado filtratá los regitros según lo definido en el Model.

``` php
public function index(){

    // Obtiene los inputs del request y sus respectivos valores.
    $params = Usuario::searchzyInputs();

    // Realiza la consulta en la base de datos mediante el Model
	$oUsuarios = Usuario::withCount(['posts AS posts_count'])
                    ->with(['posts'])
                    ->searchzy()
                    ->orderBy('name')
                    ->paginate();

	return view('welcome.index', compact('params', 'oUsuarios'));
}
```

## Uso en las Views

Para implementar el campo de búsqueda en la vista deberás de hacer lo siguiente:

``` html
<input type="text" name="{{ config('searchzy.keyword') }}" value="{{ ${config('searchzy.keyword')} }}">
```

Sí usas el package de `laravelcollective/html` la implementación sería:

``` blade
{!! Form::text(config('searchzy.keyword'), ${config('searchzy.keyword')}, [
    'class'       => 'form-control ',
    'placeholder' => 'Buscar a un usuario por su nombre, dni, telefono, correo electrónico, titulo o descripción de sus posts'
]) !!}
```

Y Listo, ya puedes usar searchzy y filtrar tus registros.

### Demo

Puede ver una demo del package en [https://searchzy.tasayco.com](https://searchzy.tasayco.com) cuyo repositorio es [https://github.com/jhormantasayco/laravel-searchzy-demo](https://github.com/jhormantasayco/laravel-searchzy-demo)

### Testing

``` bash
./vendor/bin/phpunit
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email jhormantasayco@gmail.com instead of using the issue tracker.

## Credits

- [Jhorman Alexander Tasayco](https://github.com/jhormantasayco)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

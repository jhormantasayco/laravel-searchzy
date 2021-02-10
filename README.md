# Laravel Searchzy

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jhormantasayco/laravel-searchzy/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jhormantasayco/laravel-searchzy/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/jhormantasayco/laravel-searchzy/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jhormantasayco/laravel-searchzy/build-status/master)
[![Latest Stable on Packagist](https://poser.pugx.org/jhormantasayco/laravel-searchzy/v/stable)](https://packagist.org/packages/jhormantasayco/laravel-searchzy)
[![Total Downloads](https://poser.pugx.org/jhormantasayco/laravel-searchzy/downloads)](https://packagist.org/packages/jhormantasayco/laravel-searchzy)
[![License](https://poser.pugx.org/jhormantasayco/laravel-searchzy/license)](https://packagist.org/packages/jhormantasayco/laravel-searchzy)
[![Sonar Cloud](https://sonarcloud.io/api/project_badges/measure?project=jhormantasayco_laravel-searchzy&metric=alert_status)](https://sonarcloud.io/dashboard?id=jhormantasayco_laravel-searchzy)

[![Quality gate](https://sonarcloud.io/api/project_badges/quality_gate?project=jhormantasayco_laravel-searchzy)](https://sonarcloud.io/dashboard?id=jhormantasayco_laravel-searchzy)

El package te permite buscar y filtrar registros de Eloquent Models en Laravel de una manera simple y sencilla. Compatible con la versión 6, 7, 8.

## Instalación

Puedes instalar el package vía composer de la siguiente manera:

```bash
composer require jhormantasayco/laravel-searchzy
```

## Uso en los Models

Para añadir searchzy debes de hacer lo siguiente:

1. Usar el trait `Jhormantasayco\LaravelSearchzy\Searchzy` en tus Models.
2. Especificar mediante un array asociativo por medio de una propiedad o un método que retorne un array, donde se especifique las columnas que serán usadas por searchzy para la búsqueda y filtrado de los registros.
 Las keys del array representan el nombre de la variable que almacena la data del request y los values representan a las columnas o relaciones del Model. Para asociar una relación del Model se usa la siguiente nomenclatura `(relation:column)` como se describe en el siguiente ejemplo:


``` php
use Jhormantasayco\LaravelSearchzy\Searchzy;

class MyModel extends Model
{
    use Searchzy;

    /**
     * The attributes that are searchable via property.
     *
     * @var array
     */
    protected $searchable = [
        'nombre'      => 'name',
        'dni'         => 'code',
        'telefono'    => 'phone',
        'correo'      => 'email',
        'post'        => 'posts:title',
        'descripcion' => 'posts:description',
    ];

    /**
     * The attributes that are searchable via method.
     *
     * @return array
     */
    public function searchableInputs(){

        return [
            'nombre'      => 'name',
            'dni'         => 'code',
            'phone'       => 'phone',
            'email'       => 'email',
            'post'        => 'posts:title',
            'descripcion' => 'posts:description',
        ];
    }

    /**
     * The attributes that are filterable via property.
     *
     * @var array
     */
    protected $filterable = [
        'rol_id' => 'role_id',
    ];

    /**
     * The attributes that are filterable via method.
     *
     * @return array
     */
    public function filterableInputs(){

        return [
            'rol_id' => 'role_id',
        ];
    }
}
```
## ¿Propiedad o Método?

Puedes usar cualquiera de las vias que se presentan en el ejemplo, ya sea por medio de una propiedad o un método, todo funcionará sin problemas. Usa la forma con la cual te sientas más comodo.

## Uso en los Controllers

Simplemente tienes que añadir el scope de searchzy en tus consultas para que se pueda realizar la búsqueda y filtrado de registros según los datos enviados en el request. Searchzy sólo trabajará con los datos cuyos nombres coincidan con lo descrito en los arrays asociativos del Model.

``` php
public function index(){

    // Obtiene los inputs del request y sus respectivos valores.
    $params = Usuario::searchzyInputs();

    // Implementación de searchzy en la consulta donde puedes seguir usando los demás métodos del Model con total normalidad.
    $oUsuarios = Usuario::withCount(['posts AS posts_count'])
                    ->with(['posts'])
                    ->searchzy()
                    ->orderBy('name')
                    ->paginate();

    return view('welcome.index', compact('params', 'oUsuarios'));
}
```

## Uso en las Views

Para realizar la búsqueda de registros searchzy implementa una keyword que por defecto es `word`. La configuración se ubica en `config/searchzy.php` donde usted puede cambiar este valor según su necesidad.

La implementar del campo de búsqueda se realiza de la siguiente manera


``` html
<input type="text" name="{{ config('searchzy.keyword') }}" value="{{ ${config('searchzy.keyword')} }}" class="form-control" class="Buscar a un usuario por su nombre, dni, telefono, correo electrónico, titulo o descripción de sus posts">
```

Sí usas el package de `laravelcollective/html` la implementación sería la siguiente:

``` blade
{!! Form::text(config('searchzy.keyword'), ${config('searchzy.keyword')}, [
    'class'       => 'form-control',
    'placeholder' => 'Buscar a un usuario por su nombre, dni, telefono, correo electrónico, titulo o descripción de sus posts'
]) !!}
```

Con esto  ya podemos empezar a buscar nuestros registros con searchzy.

Para los campos que son `filterable` se recomienda implementar un select por cada elemento del array.

Ejemplo:

``` blade
{!! Form::select('rol_id', UtilEnum::$ARR_ROLES, $rol_id, [
    'class'    => 'form-control',
]) !!}
```
Nótese  que el nombre del select debe de ser igual al definido en la keys del array en el Model.

### ¿Qué hemos logramos con esto?

- Evitar la sobrecarga de consultas de búsqueda (orWhere o whereHas) en los Controllers o Models del proyecto.
- Dar un alias a las columnas y relaciones para realizar la búsqueda y filtrado de registros, evitando que cualquier persona que pudiera ver los parámetros en la url infiera el diseño y modelado de la base de datos.
- Optimizar la consulta de búsqueda mediante el uso de subqueries generadas por el package.

### Requerimientos
- PHP 7.2 o superior.
- Laravel 6.0 o superior.

### Demo

Puedes ver una demo del package en los siguientes enlaces:
- Demo: [https://searchzy.tasayco.com](https://searchzy.tasayco.com)
- Repositorio: [https://github.com/jhormantasayco/laravel-searchzy-demo](https://github.com/jhormantasayco/laravel-searchzy-demo)

### Testing

``` bash
./vendor/bin/phpunit
./vendor/bin/phpcpd src
./vendor/bin/phpcs src
./vendor/bin/phpcbf src
./vendor/bin/phpstan analyse src
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

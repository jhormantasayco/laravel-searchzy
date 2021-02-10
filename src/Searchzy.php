<?php

namespace Jhormantasayco\LaravelSearchzy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait Searchzy
{
    /**
     * Agrupa todas los closures de las relaciones del Modelo, en forma de árbol.
     *
     * @var array
     */
    private $relationConstraints = [];

    /**
     * Define el array de las relaciones y el query de las relaciones. En el query
     * ya se aplicaron las closures de cada relación.
     *
     * @var array
     */
    private $eagerRelationConstraints = [];

    /**
     * Define el array con todos los inputs searchable del Modelo.
     *
     * @var array
     */
    private $searchableInputs = [];

    /**
     * Define el array con todos los inputs filterable del Modelo.
     *
     * @var array
     */
    private $filterableInputs = [];

    /**
     * Define el array con todos los inputs adicionales del Modelo.
     *
     * @var array
     */
    private $aditionableInputs = [];

    /**
     * Define el valor de la 'keyword' de searchzy.
     *
     * @var array
     */
    private $searchableKeyword;

    /**
     * Define el request usado por searchzy.
     *
     * @var Request
     */
    private $currentRequest;

    /**
     * Scope que realiza una búsqueda searchzy.
     *
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchzy($query, $keyword = null, $request = null): Builder
    {
        $keyword = $keyword ?: config('searchzy.keyword');

        $this->currentRequest = $request ?: request();

        $this->searchableKeyword = $this->currentRequest->get($keyword, null);

        $this->searchableInputsKeyword = $this->getInputsKeyword();

        $this->searchableInputs = $this->getInputsFromRequest('searchable', 'searchableInputs');

        $this->filterableInputs = $this->getInputsFromRequest('filterable', 'filterableInputs');

        $query = $this->parseInputsKeywordConstraints($query);

        $query = $this->parseRelationConstraints($query);

        $query = $this->loadRelationContraints($query);

        return $query;
    }

    /**
     * Agrupa las relaciones del Modelo. Retorna un array 'arbol' de las relaciones y sus columnas.
     *
     * @param  array $arrInputs
     * @return array
     */
    private function parseRelationInputs($arrInputs): array
    {
        $relationInputs = [];

        foreach (array_keys($arrInputs) as $attribute) {

            if (Str::contains($attribute, ':')) {

                [$relation, $column] = explode(':', $attribute);

                $relationInputs[$relation][] = $column;
            }
        }

        return $relationInputs;
    }

    /**
     * Agrupas las columnas propias del Modelo.
     *
     * @param  array $arrInputs
     * @return array
     */
    private function parseModelInputs($arrInputs): array
    {
        $modelInputs = [];

        foreach (array_keys($arrInputs) as $attribute) {

            if (!Str::contains($attribute, ':')) {

                $modelInputs[] = $attribute;
            }
        }

        return $modelInputs;
    }

    /**
     * Parsea los inputs de búsqueda del Modelo.
     *
     * @param  Builder $query
     * @return Builder
     */
    private function parseInputsKeywordConstraints($query): Builder
    {
        // Aplicación del los where's de los atributos searchable propios del Modelo.

        $searchableModelInputs = $this->parseModelInputs($this->searchableInputsKeyword);

        $query = $query->where(function ($query) use ($searchableModelInputs) {

            // Aplicación de los where's en las columnas propias del Modelo, cuyo valor es el del 'keyword'.

            $query = $query->where(function ($query) use ($searchableModelInputs) {

                foreach ($searchableModelInputs as $attribute) {

                    $value = Arr::get($this->searchableInputsKeyword, $attribute, $this->searchableKeyword);

                    if ($value) {

                        $query->orWhere($attribute, 'LIKE', "%{$value}")
                            ->orWhere($attribute, 'LIKE', "{$value}%")
                            ->orWhere($attribute, 'LIKE', "%{$value}%");
                    }
                }
            });

            // Aplicación de los where's de las relaciones del Modelo, cuyo valor es el del 'keyword'.

            if ($this->searchableKeyword) {

                $searchableRelationInputs = $this->parseRelationInputs($this->searchableInputsKeyword);

                $searchableRelation = $this->parseRelationInputs($this->searchableInputs);

                foreach ($searchableRelationInputs as $attribute => $columns) {

                    if (!in_array($attribute, array_keys($searchableRelation))) {

                        $query->orWhereHas($attribute, function ($query) use ($attribute, $searchableRelationInputs) {

                            $query = $query->where(function ($query) use ($attribute, $searchableRelationInputs) {

                                $columns = $searchableRelationInputs[$attribute] ?? [];

                                foreach ($columns as $column) {

                                    $value = $this->searchableInputsKeyword["{$attribute}:{$column}"] ?? $this->searchableKeyword;

                                        $query->orWhere($column, 'LIKE', "%{$value}")
                                            ->orWhere($column, 'LIKE', "{$value}%")
                                            ->orWhere($column, 'LIKE', "%{$value}%");
                                }
                            });
                        });
                    }
                }
            }
        });

        // Aplicación del los where's de los atributos filterable propios del Modelo.

        $filterableModelInputs = $this->parseModelInputs($this->filterableInputs);

        $filterableModelInputs = Arr::only($this->filterableInputs, $filterableModelInputs);

        foreach ($filterableModelInputs as $column => $value) {

            $operator = is_array($value) ? 'whereIn' : 'where';

            $value    = is_array($value) ? array_filter($value, 'filter_nullables') : str_trimmer($value);

            if (is_array($value) and !count($value)) {
                break;
            }

            $query->{$operator}($column, $value);
        }

        // Se añade los constraints para las relaciones definidads en el searchable del Modelo.

        $searchableRelationInputs = $this->parseRelationInputs($this->searchableInputs);

        foreach ($searchableRelationInputs as $attribute => $columns) {

            $this->addRelationConstraints([$attribute => function ($query) use ($attribute, $searchableRelationInputs) {

                $columns = $searchableRelationInputs[$attribute] ?? [];

                foreach ($columns as $column) {

                    $value = Arr::get($this->searchableInputs, "{$attribute}:{$column}");

                    $query->where(function ($query) use ($column, $value) {

                        $query->orWhere($column, 'LIKE', "%{$value}")
                            ->orWhere($column, 'LIKE', "{$value}%")
                            ->orWhere($column, 'LIKE', "%{$value}%");
                    });
                }
            }]);
        }

        // Se añade los constraints de las relaciones definidads en el filterable del Modelo.

        $filterableRelationInputs = $this->parseRelationInputs($this->filterableInputs);

        foreach ($filterableRelationInputs as $attribute => $columns) {

            $this->addRelationConstraints([$attribute => function ($query) use ($attribute, $filterableRelationInputs) {

                $columns = $filterableRelationInputs[$attribute] ?? [];

                foreach ($columns as $column) {

                    $value = Arr::get($this->filterableInputs, "{$attribute}:{$column}");

                    $operator = is_array($value) ? 'whereIn' : 'where';

                    $value    = is_array($value) ? array_filter($value, 'filter_nullables') : str_trimmer($value);

                    if (is_array($value) and !count($value)) {
                        break;
                    }

                    $query->{$operator}($column, $value);
                }
            }]);
        }

        return $query;
    }

    /**
     * Agrupa las closures por cada relación en {relationConstraints}.
     *
     * @param  array  $relations
     * @return void
     */
    private function addRelationConstraints(array $relations): void
    {
        foreach ($relations as $name => $closure) {

            $this->relationConstraints[$name][] = $closure;
        }
    }

    /**
     * Sí hay closures en las relaciones, aplica al query y agrupalas
     * por cada la relación en {eagerRelationConstraints}.
     *
     * @param  Builder $query
     * @return Builder
     */
    private function parseRelationConstraints($query): Builder
    {
        if ($this->relationConstraints) {

            foreach ($this->relationConstraints as $relation => $constraints) {

                $this->eagerRelationConstraints[$relation] = function ($query) use ($constraints) {

                    foreach ($constraints as $constraint) {

                        $constraint($query);
                    }
                };
            }
        }

        return $query;
    }

    /**
     * Aplica los 'closures' que estan en {eagerRelationConstraints} por cada relación vía whereHas.
     *
     * @param  Builder $query
     * @return Builder
     */
    private function loadRelationContraints($query): Builder
    {
        if ($this->eagerRelationConstraints) {

            foreach ($this->eagerRelationConstraints as $relation => $closure) {

                $query->whereHas($relation, $closure);
            }
        }

        return $query;
    }

    /**
     * Retorna un array con los inputs 'searchables' cuyo valor será el ingresado en la 'keyword'.
     *
     * @return array
     */
    private function getInputsKeyword(): array
    {
        $arrInputs  = [];

        $searchableInputsFromModel = $this->getInputsFromModel('searchable', 'searchableInputs');

        if (count($searchableInputsFromModel)) {

            foreach (array_keys($searchableInputsFromModel) as $column) {

                $arrInputs[$column] = $this->searchableKeyword ?: $this->currentRequest->get($column, null);
            }

            $arrInputs = array_keys_replace($arrInputs, $searchableInputsFromModel);
        }

        return $arrInputs;
    }

    /**
     * Obtiene los inputs definidos en el Modelo y que se encuentran en el Request.
     *
     * @param  string $property
     * @param  string $method
     * @return array
     */
    private function getInputsFromRequest($property, $method): array
    {
        $inputsFromModel = $this->getInputsFromModel('filterable', 'filterableInputs');

        $filledInputs = array_filter_empty($this->currentRequest->only(array_keys($inputsFromModel)));

        $filledInputs = array_keys_replace($filledInputs, $inputsFromModel);

        $filledInputs = array_filter_recursive($filledInputs, 'filter_nullables');

        return $filledInputs;
    }

    /**
     * Obtiene los inputs definidos en el Model, tanto en la propiedad como método.
     *
     * @param  string $property
     * @param  string $method
     * @param  bool $keys
     * @return array
     */
    private function getInputsFromModel($property, $method, $keys = false): array
    {
        $inputs = [];

        $inputs = property_exists($this, $property) ? Arr::wrap($this->{$property}) : $inputs;

        $inputs = method_exists($this, $method) ? Arr::wrap($this->{$method}()) : $inputs;

        $inputs = $keys ? (Arr::isAssoc($inputs) ? array_keys($inputs) : $inputs) : $inputs;

        return $inputs;
    }

    /**
     * Obtiene los inputs de searchzy (keyword, searchzy, extra) cuyo valor será el de Request o el definido por defecto.
     *
     * @link    (https://timacdonald.me/query-scopes-meet-action-scopes/)
     * @param   Builder $query
     * @param   array   $extra
     * @param   string  $default
     * @return  array
     */
    public function scopeSearchzyInputs($query, $extra = [], $default = '', $request = null): array
    {
        $this->currentRequest = $request ?: request();

        $searchable  = $this->getInputsFromModel('searchable', 'searchableInputs', true);

        $filterable  = $this->getInputsFromModel('filterable', 'filterableInputs', true);

        $aditionable = $this->getInputsFromModel('aditionable', 'aditionableInputs', true);

        $extra   = Arr::wrap($extra);

        $keyword = Arr::wrap(config('searchzy.keyword'));

        $inputs = array_merge($searchable, $filterable, $aditionable, $keyword, $extra);

        $inputs = array_filler($this->currentRequest->all(), $inputs, $default);

        return $inputs;
    }
}

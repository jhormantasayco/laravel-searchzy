<?php

namespace Jhormantasayco\LaravelSearchzy;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait Searchzy {

    /**
     * Define el array con los constraints (closures) por cada relación del Modelo.
     *
     * @var array
     */
    protected $relationConstraints = [];

    /**
     * Define el array con los constraints (closures) por cada relación del Modelo.
     *
     * @var array
     */
    protected $eagerRelationConstraints = [];

    /**
     * Define el array con todos los inputs searchable del Modelo.
     *
     * @var array
     */
    protected $searchableInputs = [];

    /**
     * Define el array con todos los inputs filterable del Modelo.
     *
     * @var array
     */
    protected $filterableInputs = [];

    /**
     * Define el array con todos los inputs filterable del Modelo.
     *
     * @var array
     */
    protected $searchableKeyword;

    /**
     * Scope que realiza una busqueda con las relaciones del Modelo.
     *
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchzy($query, $keyword = NULL){

        $keyword = $keyword ?: config('searchzy.keyword');

        $this->searchableKeyword = request()->get($keyword, NULL);

        $this->searchableInputsKeyword = $this->getInputsKeyword();

        $this->filterableInputs = $this->getInputsRequest('filterable');

        $this->searchableInputs = $this->getInputsRequest('searchable');

        $query = $this->parseInputsKeywordConstraints($query);

        $query = $this->parseRelationConstraints($query);

        $query = $this->loadRelationContraints($query);

        return $query;
    }

    /**
     * Parsea los inputs de busqueda que tiene relación con el Modelo.
     *
     * @param  array $arrInputs
     * @return array
     */
    protected function parseRelationInputs($arrInputs){

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
     * Parsea los inputs de busqueda del Modelo.
     *
     * @param  array $arrInputs
     * @return array
     */
    protected function parseModelInputs($arrInputs){

        $modelInputs = [];

        foreach (array_keys($arrInputs) as $attribute) {

            if (!Str::contains($attribute, ':')) {

                $modelInputs[] = $attribute;
            }
        }

        return $modelInputs;
    }

    /**
     * Parsea los inputs de busqueda del Modelo.
     *
     * @param  Builder $query
     * @return Builder
     */
    protected function parseInputsKeywordConstraints($query){

        // Definición del where base de los atributos searchable.

        $searchableModelInputs = $this->parseModelInputs($this->searchableInputsKeyword);

        $query = $query->where(function($query) use ($searchableModelInputs){

            $query = $query->where(function($query) use ($searchableModelInputs){

                // Aplicación de los where's searchable del Modelo.

                foreach ($searchableModelInputs as $attribute) {

                    $value = Arr::get($this->searchableInputsKeyword, $attribute, $this->searchableKeyword);

                    if ($value) {

                        $query->orWhere($attribute, 'LIKE', "%{$value}")
                            ->orWhere($attribute, 'LIKE', "{$value}%")
                            ->orWhere($attribute, 'LIKE', "%{$value}%");
                    }
                }
            });

            // Aplicación de los where's de las relaciones del Modelo.

            if ($this->searchableKeyword) {

                $searchableRelationInputs = $this->parseRelationInputs($this->searchableInputsKeyword);

                $searchableRelation = $this->parseRelationInputs($this->searchableInputs);

                foreach ($searchableRelationInputs as $attribute => $columns) {

                    if (!in_array($attribute, array_keys($searchableRelation))) {

                        $query->orWhereHas($attribute, function ($query) use ($attribute, $searchableRelationInputs) {

                            $query = $query->where(function($query) use ($attribute, $searchableRelationInputs){

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


        // Aplicación del los where's de los atributos filterable.

        $filterableModelInputs = $this->parseModelInputs($this->filterableInputs);

        $filterableModelInputs = Arr::only($this->filterableInputs, $filterableModelInputs);

        foreach ($filterableModelInputs as $column => $value) {

            $query->where($column, '=', str_trimmer($value));
        }

        // Se añade los constraints para las relaciones vía searchable del Modelo.

        $searchableRelationInputs = $this->parseRelationInputs($this->searchableInputs);

        foreach ($searchableRelationInputs as $attribute => $columns) {

            $this->addRelationConstraints([$attribute => function ($query) use ($attribute, $searchableRelationInputs) {

                $columns = $searchableRelationInputs[$attribute] ?? [];

                foreach ($columns as $column) {

                    $value = Arr::get($this->searchableInputs, "{$attribute}:{$column}");

                    $query->where(function($query) use ($column, $value){

                        $query->orWhere($column, 'LIKE', "%{$value}")
                            ->orWhere($column, 'LIKE', "{$value}%")
                            ->orWhere($column, 'LIKE', "%{$value}%");
                    });
                }
            }]);
        }

        // Se añade los constraints para las relaciones vía filterable del Modelo.

        $filterableRelationInputs = $this->parseRelationInputs($this->filterableInputs);

        foreach ($filterableRelationInputs as $attribute => $columns) {

            $this->addRelationConstraints([$attribute => function ($query) use ($attribute, $filterableRelationInputs) {

                $columns = $filterableRelationInputs[$attribute] ?? [];

                foreach ($columns as $column) {

                    $value = Arr::get($this->filterableInputs, "{$attribute}:{$column}");

                    $query->where($column, '=', str_trimmer($value));
                }
            }]);
        }

        return $query;
    }

    /**
     * Parsea los constraints asociadas a una relación del Modelo.
     *
     * @param  array  $relations
     * @param  string $property
     * @return void
     */
    protected function addRelationConstraints(array $relations){

        foreach ($relations as $name => $closure) {

            $this->relationConstraints[$name][] = $closure;
        }
    }

    /**
     * Asocia y aplica los closures que fueron definidos para cada relación.
     *
     * @param  Builder $query
     * @return Builder
     */
    protected function parseRelationConstraints($query){

        if ($this->relationConstraints) {

            foreach ($this->relationConstraints as $relation => $constraints) {

                $this->eagerRelationConstraints[$relation] = function($query) use ($constraints) {

                    foreach ($constraints as $constraint) {

                        $constraint($query);
                    }
                };
            }
        }

        return $query;
    }

    /**
     * Carga los constraints de las relaciones del Modelo al query enviado.
     *
     * @param  Builder $query
     * @return Builder
     */
    protected function loadRelationContraints($query){

        if ($this->eagerRelationConstraints) {

            foreach ($this->eagerRelationConstraints as $relation => $closure) {

                $query->whereHas($relation, $closure);
            }
        }

        return $query;
    }

    /**
     * Parsea los inputs con el valor de la keyword.
     *
     * @param  string $keyword
     * @return array
     */
    protected function getInputsKeyword(){

        $arrInputs  = [];

        $searchable = Arr::wrap($this->searchable);

        $searchable = Arr::isAssoc($searchable) ? array_keys($searchable) : $searchable;

        foreach ($searchable as $column) {

            $arrInputs[$column] = $this->searchableKeyword ?: request()->get($column, NULL);
        }

        $arrInputs = array_keys_replace($arrInputs, Arr::wrap($this->searchable));

        return $arrInputs;
    }

    /**
     * Obtiene los 'inputs' definidos en el Modelo.
     *
     * @param  string $property
     * @return array
     */
    protected function getInputsRequest($property){

        $arrInputs = [];

        if (property_exists($this, $property)) {

            $arrInputs = Arr::wrap($this->{$property});

            $arrInputs = Arr::isAssoc($arrInputs) ? array_keys($arrInputs) : $arrInputs;

            $arrInputs = array_filter_empty(request()->only($arrInputs));

            $arrInputs = array_keys_replace($arrInputs, Arr::wrap($this->{$property}));
        }

        return $arrInputs;
    }

    /**
     * Obtiene los inputs del Modelo de la URL.
     *
     * @param   array $params
     * @return  array
     */
    public function paramsURL($extraParams = []){

        $searchable = property_exists($this, 'searchable') ? Arr::wrap($this->searchable) : [];

        $searchable = Arr::isAssoc($searchable) ? array_keys($searchable) : $searchable;

        $filterable = property_exists($this, 'filterable') ? Arr::wrap($this->filterable) : [];

        $filterable = Arr::isAssoc($filterable) ? array_keys($filterable) : $filterable;

        $extraParams = Arr::wrap($extraParams);

        $keyword = Arr::wrap(config('searchzy.keyword'));

        $params  = array_merge($searchable, $filterable, $keyword, $extraParams);

        $params  = array_only_filler(request()->all(), $params, '');

        return $params;
    }

    /**
     * Obtiene los inputs del Modelo de la URL.
     *
     * @link    (https://timacdonald.me/query-scopes-meet-action-scopes/)
     * @param   Builder $query
     * @param   array   $params
     * @return  array
     */
    public function scopeSearchzyInputs($query, $extraParams = [], $fillable = '') : array {

        $searchable = property_exists($this, 'searchable') ? Arr::wrap($this->searchable) : [];

        $searchable = Arr::isAssoc($searchable) ? array_keys($searchable) : $searchable;

        $filterable = property_exists($this, 'filterable') ? Arr::wrap($this->filterable) : [];

        $filterable = Arr::isAssoc($filterable) ? array_keys($filterable) : $filterable;

        $extraParams = Arr::wrap($extraParams);

        $keyword = Arr::wrap(config('searchzy.keyword'));

        $params  = array_merge($searchable, $filterable, $keyword, $extraParams);

        $params  = array_only_filler(request()->all(), $params, $fillable);

        return $params;
    }

}

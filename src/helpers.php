<?php

use Illuminate\Support\Arr;

if (!function_exists('str_trimmer')){

	/**
	 * Retorna un string sin espacios en blancos.
	 *
	 * @param  string $string
	 * @return string
	 */
	function str_trimmer($string){

		return is_string($string) ? preg_replace('/\s+/', ' ', trim($string)) : $string;
	}
}

if (!function_exists('array_filter_empty')){

	/**
	 * Filtra el array eliminando valores 'vacios'.
	 *
	 * @param  array $array
	 * @return array
	 */
	function array_filter_empty($array){

		$array = array_map('str_trimmer', $array);

		$array = array_filter($array, function($value) {

		    return ($value !== null && $value !== false && $value !== '');
		});

		return $array;
	}
}

if (! function_exists('array_only_filler')) {

    /**
     * Retorna un nuevo array con las array original. Sí el valor el valor no existe añade NULL.
     *
     * @param  array $array
     * @param  array $keys
     * @param  mixed $default
     * @param  array
     */
    function array_only_filler($array, $keys = [], $default = NULL){

    	$data = [];

        $keys = Arr::wrap($keys);

    	foreach ($keys as $key) {

    		$data[$key] = Arr::get($array, $key, $default);
    	}

    	return $data;
    }
}

if (! function_exists('array_is_assoc')) {

    /**
     * Verifica sí el array es asociativo.
     *
     * @param  array $array
     * @param  bool
     */
    function array_is_assoc($array){

    	if (array() === $array) return false;

    	return array_keys($array) !== range(0, count($array) - 1);
    }
}

if (! function_exists('array_keys_replace')) {

    /**
     * Reemplaza las keys del array original con los values de otro array por medio de las keys.
     *
     * @param  array $array
     * @param  array $replace
     * @param  array
     */
    function array_keys_replace($array, $replace){

    	if (!array_is_assoc($array) OR !array_is_assoc($replace)) {

    		return $array;
    	}

    	$data = [];

    	foreach ($array as $column => $value) {

    		if (Arr::has($replace, $column)) {

    			$key = Arr::get($replace, $column);

    			$data[$key] = $value;
    		}
    	}

    	return $data;
    }
}

if (!function_exists('array_range')){

    /**
     * Obtiene un rango de números representado por un array asociativo.
     *
     * @param  integer $from
     * @param  integer $to
     * @return array
     */
    function array_range($from, $to){

        return array_combine(range($from, $to), range($from, $to));
    }
}

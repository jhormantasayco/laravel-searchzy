<?php

namespace Jhormantasayco\LaravelSearchzy\Tests;

use Orchestra\Testbench\TestCase;
use Jhormantasayco\LaravelSearchzy\LaravelSearchzyServiceProvider;

class ExampleTest extends TestCase
{

    /** @test */
    public function true_is_true(){

        $this->assertTrue(true);
    }

    /** @test */
    public function array_is_associative(){

        $this->assertTrue(array_is_assoc(['name' => 'Jhorman']));
    }

    /** @test */
    public function array_equals_using_only_filter(){

        $this->assertEquals(
        	['name' => 'Jhorman', 'job' => NULL],
        	array_only_filler(
				['name' => 'Jhorman', 'lastname' => 'Tasayco'],
        		['name', 'job']
        	)
        );
    }
}

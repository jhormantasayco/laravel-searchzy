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
        	array_filler(
				['name' => 'Jhorman', 'lastname' => 'Tasayco'],
        		['name', 'job']
        	)
        );
    }

    /** @test */
    public function filter_null_string(){

        $this->assertTrue(filter_nullables('NULL'));
    }

    /** @test */
    public function filter_false_string(){

        $this->assertTrue(filter_nullables('FALSE'));
    }

    /** @test */
    public function filter_nulls(){

        $this->assertFalse(filter_nullables(NULL));
    }

    /** @test */
    public function filter_falses(){
        $this->assertFalse(filter_nullables(false));
    }

    /** @test */
    public function filter_strings_empty(){

        $this->assertFalse(filter_nullables(''));
    }

    /** @test */
    public function filter_strings_empty_b2(){

        $this->assertFalse(filter_nullables('        '));
    }
}

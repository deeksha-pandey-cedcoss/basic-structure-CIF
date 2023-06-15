<?php

namespace App\Core\Test;

/**
 * Class UnitTest
 */
class UnitTest extends \App\Core\Components\UnitTestApp
{

    public function testTestCase()
    {
       // print_r( (new \App\Core\Models\User)::find());die;
        //print_r($this->getDi()->getDb());die;
        $this->assertEquals(
            "works",
            "works",
            "This is OK"
        );

        $this->assertEquals(
            "works",
            "works",
            "This will fail"
        );
    }
}
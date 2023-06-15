<?php

namespace App\Connector\Test;


/**
 * Class UnitTest
 */
class UnitTest extends \App\Core\Components\UnitTestApp
{

    public function testTestCase()
    {
        //print_r( (new \App\Core\Models\User)::find());die;

       // print_r(\App\Core\Models\User::find());die;
        $this->assertEquals(
            "works",
            "works",
            "This is OK vin connecter"
        );

        $this->assertEquals(
            "works",
            "works",
            "This will fail connecotr"
        );
    }
}
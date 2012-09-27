<?php

namespace Stemming\Tests;

use Stemming\Stemm;

/**
*
*/
class StemmTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function vocals()
    {
        $pattern = Stemm::VOWEL;
        $vocals = "aeiouáéíóúü";

        for ($i=0; $i < strlen($vocals); $i++) {
            $this->assertTrue(preg_match($pattern, $vocals[$i], $matches) > 0);
            $this->assertEquals($matches[0], $vocals[$i]);
        }
    }

    /**
     * @test
     */
    public function consotants()
    {
        $pattern = Stemm::VOWEL;
        $consonantes = "bcdfghjklmnñpqrstvwxyz";

        for ($i=0; $i < strlen($consonantes); $i++) {
            $this->assertTrue(preg_match($pattern, $consonantes[$i], $matches) == 0);
        }
    }
}

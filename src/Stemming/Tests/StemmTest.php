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
    public function vocales()
    {
        $vocals = array('a','e','i','o','u','á','é','í','ó','ú','ü');
        $count = 0;
        $expected = 10;

        for ($i=0; $i < count($vocals); $i++) {
            $count += Stemm::isVowel($vocals[$i]);
        }

        $this->assertEquals(count($vocals), $count);
    }

    /**
     * @test
     */
    public function consonantes()
    {
        $consonants = array('b','c','d','f','g');

        $count = 0;
        $expected = 10;

        for ($i=0; $i < count($consonants); $i++) {
            $count += Stemm::isConsonant($consonants[$i]);
        }

        $this->assertEquals(count($consonants), $count);

    }

    /**
     * @return array|mixed
     */
    public static function provider()
    {
        return array(
            array('porque', 'porqué'),
            array('accion', "acción"),
            array('julian', 'júlian'),
            array('esdrujula', 'esdrújula')
        );
    }

    public static function corpus()
    {
        $archivo = __DIR__ . '/corpus.txt';
        $fhandle = fopen($archivo, "r");

        $corpus = array();

        while ($linea = fgetcsv($fhandle, 1000, " ")) {
            $corpus[] = $linea;
        }
        fclose($fhandle);

        return $corpus;
    }

    /**
     * @test
     * @dataProvider provider
     */
    public function removerAcento($expected, $actual)
    {
        $this->assertEquals($expected, Stemm::removeAccent($actual));
    }

    /**
     * @test
     * @dataProvider corpus
     */
    public function raizPalabras($palabra, $expected)
    {
        $this->assertEquals($expected, Stemm::stem($palabra));
    }

    /**
     * @test
     */
    public function raizUnaPalabra()
    {
        $palabra = 'abogadas';
        $expected = 'abog';

        $this->assertEquals($expected, Stemm::stem($palabra));
    }

    public static function terminaEnProvider()
    {
        return array(
            array('cantar', 'ar'),
            array('ver', 'er'),
            array('ir', ''),
            array('palabras', ''),
            array('calabazas', 'zas'),
        );
    }

    /**
     * @test
     * @dataProvider terminaEnProvider
     */
    public function terminaEn($value, $expected)
    {
        $terminaciones = array(
            'ar', 'er', 'oir', 'zas', 'za', 'tel'
        );

        $this->assertEquals($expected, Stemm::endsIn($value, $terminaciones));
    }
}

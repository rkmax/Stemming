<?php

namespace Stemming\Tests;

use Stemming\SpanishPorter;

/**
*
*/
class SpanishPorterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function vocales()
    {
        $vocals = array('a','e','i','o','u','á','é','í','ó','ú','ü');
        $count = 0;

        for ($i=0; $i < count($vocals); $i++) {
            $count += SpanishPorter::isVowel($vocals[$i]);
        }

        $this->assertEquals(count($vocals), $count);

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
        $this->assertEquals($expected, SpanishPorter::removeAccent($actual));
    }

    /**
     * @test
     * @dataProvider corpus
     */
    public function raizPalabras($palabra, $expected)
    {
        $this->assertEquals($expected, SpanishPorter::stem($palabra));
    }

    /**
     * @test
     */
    public function raizUnaPalabra()
    {
        $palabra = 'cariñoso';
        $expected = 'cariñ';

        $this->assertEquals($expected, SpanishPorter::stem($palabra, true));
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

        $this->assertEquals($expected, SpanishPorter::endsIn($value, $terminaciones));
    }

    public static function regionesProvider()
    {
        return array(
            // array(WORD, R1, R2, RV)
            array('calabazas', 'abazas', 'azas','abazas'),
            array('pelebas', 'ebas', 'as', 'ebas'),
            array('abrigo', 'rigo', 'o', 'go')
        );
    }

    /**
     * @test
     * @dataProvider regionesProvider
     */
    public function pruebaRegiones($word, $r1expected, $r2expected, $rvexpected)
    {

        $len= strlen($word);
        $r1 = SpanishPorter::R($word);
        $r2 = SpanishPorter::R($word, $r1);
        $rv = SpanishPorter::RV($word);

        $this->assertEquals($r1expected, substr($word, $r1));
        $this->assertEquals($r2expected, substr($word, $r2));
        $this->assertEquals($rvexpected, substr($word, $rv));
    }

    public static function stepsProvider()
    {
        return array(
            array('haciéndola', 'hac', 'hac'),
            array('construyendo', 'construyendo', 'construyendo'),
            array('definitivamente', 'definitivamente', 'definit'),
            array('narrativa', 'narrativa', 'narrat'),
            array('narrar', 'narrar', 'narrar')
        );
    }
}

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
        $r1 = Stemm::R1($word, $len);
        $r2 = Stemm::R2($word, $len, $r1);
        $rv = Stemm::RV($word, $len);

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

    /**
     * @test
     * @dataProvider stepsProvider
     */
    public function steps($word, $step0, $step1)
    {
        $len= strlen($word);
        $r1 = Stemm::R1($word, $len);
        $r2 = Stemm::R2($word, $len, $r1);
        $rv = Stemm::RV($word, $len);

        $word0 = Stemm::step0($word, $r1, $r2, $rv);
        $word1 = Stemm::step1($word0, $r1, $r2, $rv);
        $word2 = Stemm::step2($word1, $r1, $r2, $rv);

        $this->assertEquals($step0, $word0);
        $this->assertEquals($step1, $word1);
    }
}

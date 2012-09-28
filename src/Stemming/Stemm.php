<?php

namespace Stemming;

/**
*
*/
class Stemm
{
    const VOWEL = '/^[aeiouáéíóúü]/';

    public static function isVowel($letter)
    {
        return preg_match(self::VOWEL, $letter);
    }

    public static function getNextVowelPos($word, $start = 0) {
        $len = strlen($word);
        for ($i = $start; $i < $len; $i++)
            if (self::isVowel($word[$i])) return $i;
        return $len;
    }

    public static function getNextConsonantPos($word, $start = 0) {
        $len = strlen($word);
        for ($i = $start; $i < $len; $i++)
            if (!self::isVowel($word[$i])) return $i;
        return $len;
    }

    public static function removeAccent($word) {
        return str_replace(array('á','é','í','ó','ú'), array('a','e','i','o','u'), $word);
    }

    public static function endsIn($word, $suffix)
    {
        if (!is_string($suffix) && !is_array($suffix)) {
            throw new \InvalidArgumentException(sprintf(
                "Se esperaba un argumento tipo [array] o [string] pero se recibio [%s]"
                , gettype($suffix)
            ));
        }

        if(is_string($suffix)) {
            $suffix = array($suffix);
        }

        foreach ($suffix as $value) {
            if (preg_match("/${value}$/", $word)) {
                return $value;
            }
        }

        return '';
    }

    public static function stem($word, $debug = true)
    {
        $len = strlen($word);
        $word = mb_strtolower($word, 'UTF-8');

        if ($len <= 2) {
            return $word;
        }

        $r1 = self::R1($word, $len);
        $r2 = self::R2($word, $len, $r1);
        $rv = self::RV($word, $len);

        if ($debug) {
            print "\nRegion:\n";
            print "R1: " . substr($word, $r1) . "\n";
            print "R2: " . substr($word, $r2) . "\n";
            print "RV: " . substr($word, $rv) . "\n";
        }


        // Step 0: Attached pronoun
        $step0Word  = self::step0($word, $r1, $r2, $rv);
        if($debug) print "\n[0]: " . $step0Word;

        // Step 1
        $step1Word  = self::step1($step0Word, $r1, $r2, $rv);
        if($debug) print "\n[1]: " . $step1Word;

        // Step 2a
        $step2Word = self::step2a($step1Word, $r1, $r2, $rv);
        if($debug) print "\n[2a]: " . $step2Word;

        // Step 2b
        if($step1Word == $step2Word) {
            $step2Word = self::step2b($step2Word, $r1, $r2, $rv);
            if($debug) print "\n[2b]: " . $step2Word;
        }

        // Step 3
        $word = self::step2a($step2Word, $r1, $r2, $rv);
        if($debug) print "\n[3]: " . $word;

        return self::removeAccent($word);
    }

    public static function step0($word, $r1, $r2, $rv)
    {
        $rv_txt = substr($word,$rv);

        $ends0 = self::endsIn($word, self::$PRO_0_SUF);

        if (!empty($ends0)) {
            $ends1 = self::endsIn(substr($rv_txt, 0, -strlen($ends0)), self::$PRO_0_SUF1);
            if (!empty($ends1)) {
                $word = self::removeAccent(substr($word, 0, -strlen($ends0)));
            } else {
                $ends1 = self::endsIn(substr($rv_txt, 0, -strlen($ends0)), self::$PRO_0_SUF2);
                $yendo = self::endsIn($word, 'yendo');

                if(!empty($ends1)  ||
                   (!empty($yendo)) &&
                    (substr($word, -strlen($ends0)-6, 1) == 'u')) {
                    $word = substr($word, 0, -strlen($ends0));
                }
            }
        }

        return $word;
    }

    public static function step1($word, $r1, $r2, $rv)
    {
        $r1_txt = substr($word,$r1);
        $r2_txt = substr($word,$r2);

        // Regla [Sufijo(s), reemplazo]
        $replace = array(
            array(self::$_1_SUF1, '', "r2_txt"),
            array(self::$_1_SUF2, '', "r2_txt"),
            array(self::$_1_SUF_LOG, 'log', "r2_txt"),
            array(self::$_1_SUF_UC, 'u', "r2_txt"),
            array(self::$_1_SUF_ENC, 'ente', "r2_txt"),
            array(self::$_1_SUF_MEN_1, '', "r2_txt"),
            array(self::$_1_SUF_MEN_2, '', "r1_txt"),
            array(self::$_1_SUF_MEN_3, '', "r2_txt"),
            array(self::$_1_SUF_DA, '', "r2_txt"),
            array(self::$_1_SUF_I, '', "r2_txt"),
        );

        foreach ($replace as $value) {
            $ends = self::endsIn($$value[2], $value[0]);
            if (!empty($ends)) {
                $word = substr($word, 0, -strlen($ends)) . $value[1];
                break;
            }
        }

        $replace = null;

        return $word;
    }

    public static function step2a($word, $r1, $r2, $rv)
    {
        $rv_txt = substr($word,$rv);

        $ends = self::endsIn($rv_txt, self::$_2a_SUF);

        if (!empty($ends) && (substr($word,-strlen($ends)-1,1) == 'u')) {
            $word = substr($word, 0, -strlen($ends));
        }

        return $word;
    }

    public static function step2b($word, $r1, $r2, $rv)
    {
        $rv_txt = substr($word,$rv);

        if ('' != ($ends = self::endsIn($rv_txt, self::$_2b_SUF_1))) {
            $word = substr($word, 0, -strlen($ends));
            if('' != ($ends = self::endsIn($word, 'gu'))) {
                $word = substr($word, 0, -1);
            }
        } elseif ('' != ($ends = self::endsIn($rv_txt, self::$_2b_SUF_2))) {
            $word = substr($word, 0, -strlen($ends));
        }

        return $word;
    }

    public static function step3($word, $r1, $r2, $rv)
    {
        $rv_txt = substr($word,$rv);

        if ('' != ($ends = self::endsIn($rv_txt, self::$_3_SUF_1))) {
            $word = substr($word, 0, -strlen($ends));
        } elseif ('' != ($ends = self::endsIn($rv_txt, self::$_3_SUF_2))) {
            $word = substr($word, 0, -1);
            $rv_txt = substr($word,$rv);

            if(('' != ($ends = self::endsIn($word, 'u'))) &&
                ('' != ($ends = self::endsIn($word, 'gu')))) {
                $word = substr($word, 0, -1);
            }
        }

        return $word;
    }

    public static function R1($word, $len) {
        $r1 = $len;

        for ($i = 0; $i < ($len-1) && $r1 == $len; $i++) {
            if (self::isVowel($word[$i]) && !self::isVowel($word[$i+1])) {
                    $r1 = $i+2;
            }
        }

        return $r1;
    }

    public static function R2($word, $len, $r1)
    {
        $r2 = $len;
        for ($i = $r1; $i < ($len -1) && $r2 == $len; $i++) {
            if (self::isVowel($word[$i]) && !self::isVowel($word[$i+1])) {
                $r2 = $i+2;
            }
        }

        return $r2;
    }

    public static function RV($word, $len)
    {
        $rv = $len;

        if ($len > 3) {
            if (!self::isVowel($word[1])) {
                $rv = self::getNextVowelPos($word, 2) +1;
            } else if (self::isVowel($word[0]) && self::isVowel($word[1])) {
                $rv = self::getNextConsonantPos($word, 2) + 1;
            } else {
                $rv = 3;
            }
        }

        return $rv;
    }

    private static $PRO_0_SUF = array(
        'me', 'se', 'sela', 'selo', 'selas',
        'selos', 'la', 'le', 'lo', 'las',
        'les', 'los', 'nos'
    );

    private static $PRO_0_SUF1 = array(
        'éndo', 'ándo', 'ár', 'ér', 'ír'
    );

    private static $PRO_0_SUF2 = array(
        'ando', 'iendo', 'ar', 'er', 'ir'
    );

    private static $_1_SUF1 = array(
        'anza', 'anzas', 'ico', 'ica', 'icos',
        'icas', 'ismo', 'ismos', 'able', 'ables',
        'ible', 'ibles', 'ista', 'istas', 'oso',
        'osa', 'osos', 'osas', 'amiento', 'amientos',
        'imiento', 'imientos'
    );

    private static $_1_SUF2 = array(
        'icadora', 'icador', 'icación',
        'icadoras', 'icadores', 'icaciones',
        'icante', 'icantes', 'icancia', 'icancias',
        'adora', 'ador', 'ación', 'adoras',
        'adores', 'aciones', 'ante', 'antes',
        'ancia', 'ancias'
    );

    private static $_1_SUF_LOG = array(
        'logía', 'logías'
    );

    private static $_1_SUF_UC = array(
        'ución', 'uciones'
    );

    private static $_1_SUF_ENC = array(
        'encia', 'encias'
    );

    private static $_1_SUF_MEN_1 = array(
        'ativamente', 'ivamente', 'osamente', 'icamente', 'adamente'
    );

    private static $_1_SUF_MEN_2 = array(
        'amente'
    );

    private static $_1_SUF_MEN_3 = array(
        'antemente', 'ablemente', 'iblemente', 'mente'
    );

    private static $_1_SUF_DA = array(
        'abilidad', 'abilidades', 'icidad', 'icidades',
        'ividad', 'ividades', 'idad', 'idades'
    );

    private static $_1_SUF_I = array(
        'ativa', 'ativo', 'ativas', 'ativos',
        'iva', 'ivo', 'ivas', 'ivos'
    );

    private static $_2a_SUF = array(
        'ya', 'ye', 'yan', 'yen', 'yeron',
        'yendo', 'yo', 'yó', 'yas', 'yes',
        'yais', 'yamos'
    );

    private static $_2b_SUF_1 = array(
        'en', 'es', 'éis', 'emos'
    );

    private static $_2b_SUF_2 = array(
        'arían', 'arías', 'arán', 'arás', 'aríais',
        'aría', 'aréis', 'aríamos', 'aremos', 'ará',
        'aré', 'erían', 'erías', 'erán', 'erás', 'eríais',
        'ería', 'eréis', 'eríamos', 'eremos', 'erá', 'eré',
        'irían', 'irías', 'irán', 'irás', 'iríais', 'iría',
        'iréis', 'iríamos', 'iremos', 'irá', 'iré', 'aba',
        'ada', 'ida', 'ía', 'ara', 'iera', 'ad', 'ed',
        'id', 'ase', 'iese', 'aste', 'iste', 'an',
        'aban', 'ían', 'aran', 'ieran', 'asen',
        'iesen', 'aron', 'ieron', 'ado', 'ido',
        'ando', 'iendo', 'ió', 'ar', 'er', 'ir',
        'as', 'abas', 'adas', 'idas', 'ías', 'aras',
        'ieras', 'ases', 'ieses', 'ís', 'áis', 'abais',
        'íais', 'arais', 'ierais', '  aseis', 'ieseis',
        'asteis', 'isteis', 'ados', 'idos', 'amos', 'ábamos',
        'íamos', 'imos', 'áramos', 'iéramos', 'iésemos', 'ásemos'
    );

    private static $_3_SUF_1 = array(
        'os', 'a', 'o', 'á', 'í', 'ó'
    );

    private static $_3_SUF_2 = array(
        'e', 'é'
    );
}

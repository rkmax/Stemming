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

    public static function stem($word, $debug = false)
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

        // Step 2
        if ($step1Word == $step0Word) {
            // Step 2a
            $word = $step2Word = self::step2a($step1Word, $r1, $r2, $rv);
            if($debug) print "\n[2a]: " . $step2Word;

            // Step 2b
            if($step1Word == $step2Word) {
                $word = self::step2b($step2Word, $r1, $r2, $rv);
                if($debug) print "\n[2b]: " . $step2Word;
            }
        }

        // Step 3
        $word = self::step3($word, $r1, $r2, $rv);
        if($debug) print "\n[3]: " . $word;

        return self::removeAccent($word);
    }

    /**
     * Step 0: Attached pronoun
     * Elimina los pronombres que tenga la palabra al final
     * @param  string $word palabras
     * @param  int    $r1   Region 1
     * @param  int    $r2   Region 2
     * @param  int    $rv   Region V
     * @return string
     */
    public static function step0($word, $r1, $r2, $rv)
    {
        $pronoun = array(
            'me', 'se', 'sela', 'selo', 'selas',
            'selos', 'la', 'le', 'lo', 'las',
            'les', 'los', 'nos'
        );

        $suffix_a = array('iéndo','ándo','ár', 'ér', 'ír');
        $suffix_b = array('ando', 'iendo', 'ar', 'er', 'ir');

        $rv_txt = substr($word, $rv);

        if ('' != ($ends = self::endsIn($rv_txt, $pronoun))) {
            $word = substr($word, 0, -strlen($ends));
            $rv_txt = substr($word, $rv);
            if ('' != ($ends = self::endsIn($rv_txt, $suffix_a))) {
                $word = self::removeAccent(substr($word, 0, -strlen($ends)));
            } elseif (
                ('' != ($ends = self::endsIn($rv_txt, $suffix_b))) ||
                    (('' != ($ends = self::endsIn($rv_txt, 'yendo'))) &&
                        (substr($word, 0, -6, 1) == 'u'))
                ) {
                $word = substr($word, 0, -strlen($ends));
            }
        }

        return $word;
    }

    public static function step1($word, $r1, $r2, $rv)
    {
        $r1_txt = substr($word,$r1);
        $r2_txt = substr($word,$r2);

        $suffix = array(
            // A
            'anza', 'anzas', 'ico', 'ica', 'icos',
            'icas', 'ismo', 'ismos', 'able', 'ables',
            'ible', 'ibles', 'ista', 'istas', 'oso',
            'osa', 'osos', 'osas', 'amiento', 'amientos',
            'imiento', 'imientos',
            // B
            'icadora', 'icador', 'icación',
            'icadoras', 'icadores', 'icaciones',
            'icante', 'icantes', 'icancia', 'icancias',
            'adora', 'ador', 'ación', 'adoras',
            'adores', 'aciones', 'ante', 'antes',
            'ancia', 'ancias'
        );

        $suffix_r = array(
            'log'  => array('logía', 'logías'),
            'u'    => array('ución', 'uciones'),
            'ente' => array('encia', 'encias')
        );

        $suffix_amente = array(
            'ativamente', 'ivamente', 'osamente', 'icamente', 'adamente',
        );

        $suffix_remain = array(
            // mente
            'antemente', 'ablemente', 'iblemente', 'mente',
            // idad
            'abilidad', 'abilidades', 'icidad', 'icidades',
            'ividad', 'ividades', 'idad', 'idades',
            // iv
            'ativa', 'ativo', 'ativas', 'ativos', 'iva', 'ivo', 'ivas', 'ivos',
        );

        if ('' != ($ends = self::endsIn($r2_txt, $suffix))) {
            $word = substr($word, 0, -strlen($ends));
        } else {
            $ends = '';
            foreach ($suffix_r as $key => $value) {
                $ends = self::endsIn($r2_txt, $value);
                if(!empty($ends)) {
                    $word = substr($word, 0, -strlen($ends)) . $key;
                    break;
                }
            }
        }

        if(empty($ends)) {
            if('' != ($ends = self::endsIn($r2_txt, $suffix_amente))) {
                $word = substr($word, 0, -strlen($ends));
            } elseif('' != ($ends = self::endsIn($r1_txt, 'amente'))) {
                $word = substr($word, 0, -strlen($ends));
            } elseif('' != ($ends = self::endsIn($r2_txt, $suffix_remain))) {
                $word = substr($word, 0, -strlen($ends));
            }
        }

        return $word;
    }

    public static function step2a($word, $r1, $r2, $rv)
    {
        $suffix = array(
            'ya', 'ye', 'yan', 'yen', 'yeron',
            'yendo', 'yo', 'yó', 'yas', 'yes',
            'yais', 'yamos'
        );

        $rv_txt = substr($word,$rv);
        $ends = self::endsIn($rv_txt, $suffix);

        if (!empty($ends) && (substr($word,-strlen($ends)-1,1) == 'u')) {
            $word = substr($word, 0, -strlen($ends));
        }

        return $word;
    }

    public static function step2b($word, $r1, $r2, $rv)
    {

        $suffix_a = array(
            'en', 'es', 'éis', 'emos'
        );

        $suffix_b = array(
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
            'abas', 'adas', 'as', 'idas', 'ías', 'aras',
            'ieras', 'ases', 'ieses', 'ís', 'áis', 'abais',
            'íais', 'arais', 'ierais', '  aseis', 'ieseis',
            'asteis', 'isteis', 'ados', 'idos', 'amos', 'ábamos',
            'íamos', 'imos', 'áramos', 'iéramos', 'iésemos', 'ásemos'
        );

        $rv_txt = substr($word,$rv);

        if ('' != ($ends = self::endsIn($rv_txt, $suffix_a))) {
            $word = substr($word, 0, -strlen($ends));
            if('' != ($ends = self::endsIn($word, 'gu'))) {
                $word = substr($word, 0, -1);
            }
        } elseif ('' != ($ends = self::endsIn($rv_txt, $suffix_b))) {
            $word = substr($word, 0, -strlen($ends));
        }

        return $word;
    }

    public static function step3($word, $r1, $r2, $rv)
    {

        $suffix_a = array(
            'os', 'a', 'o', 'á', 'í', 'ó'
        );

        $suffix_b = array(
            'e', 'é'
        );

        $rv_txt = substr($word,$rv);

        if ('' != ($ends = self::endsIn($rv_txt, $suffix_a))) {
            $word = substr($word, 0, -strlen($ends));
        } elseif ('' != ($ends = self::endsIn($rv_txt, $suffix_b))) {
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
                $rv = self::getNextVowelPos($word, 2) + 1;
            } else if (self::isVowel($word[0]) && self::isVowel($word[1])) {
                $rv = self::getNextConsonantPos($word, 2) + 1;
            } else {
                $rv = 3;
            }
        }

        return $rv;
    }
}

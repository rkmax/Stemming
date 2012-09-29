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
        $len = mb_strlen($word);
        $letters = self::mb_str_split($word);
        for ($i = $start; $i < $len; $i++)
            if (self::isVowel($letters[$i])) return $i;
        return $len;
    }

    public static function getNextConsonantPos($word, $start = 0) {
        $len = mb_strlen($word);
        $letters = self::mb_str_split($word);
        for ($i = $start; $i < $len; $i++)
            if (!self::isVowel($letters[$i])) return $i;
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
        mb_internal_encoding("UTF-8");

        $len = mb_strlen($word);
        $word = $original = mb_strtolower($word, 'UTF-8');

        if ($len <= 2) {
            return $word;
        }

        $r1 = self::R1($word, $len);
        $r2 = self::R2($word, $len, $r1);
        $rv = self::RV($word, $len);

        // Step 0: Attached pronoun
        $word = $step0Word  = self::step0($word, $r1, $r2, $rv);

        // Step 1: Stadanrd Suffix removal
        $word = $step1Word  = self::step1($step0Word, $r1, $r2, $rv);

        // Step 2: Verb Suffix
        if ($step1Word == $step0Word) {
            // Step 2a
            $word = $step2aWord = self::step2a($step1Word, $r1, $r2, $rv);

            // Step 2b
            if($step1Word == $step2aWord) {
                $word = $step2bWord = self::step2b($step2aWord, $r1, $r2, $rv);
            }
        }

        // Step 3: Residual suffix
        $word = self::step3($word, $r1, $r2, $rv);

        if ($debug) {
            print "\nDebug\n";
            print "Internal ENCODING: " . mb_internal_encoding();
            print "\nLetter count : " . mb_strlen ($original) . "\n\n  ";
            print implode('  ', self::mb_str_split($original));
            print "\n\nR1[$r1]: " . mb_substr($original,$r1);
            print "\nR2[$r2]: " . mb_substr($original,$r2);
            print "\nRV[$rv]: " . mb_substr($original,$rv);
            print "\nSteps [0] $step0Word\n";
            print "Steps [1] $step1Word\n";
            if(isset($step2aWord)) print "Steps [2a] $step2aWord\n";
            if(isset($step2bWord)) print "Steps [2b] $step2bWord\n";
            print "Steps [3] $word\n";
        }

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

        $rv_txt = mb_substr($word, $rv);

        if ('' != ($ends0 = self::endsIn($rv_txt, $pronoun))) {
            $rv_txt = mb_substr($word, $rv);
            if ('' != ($ends = self::endsIn(mb_substr($rv_txt, 0, -mb_strlen($ends0)), $suffix_a))) {
                $word = self::removeAccent(mb_substr($word, 0, -mb_strlen($ends0)));
            } elseif (
                ('' != ($ends = self::endsIn(mb_substr($rv_txt, 0, -mb_strlen($ends0)), $suffix_b))) ||
                    (('' != ($ends = self::endsIn($rv_txt, 'yendo'))) &&
                        (mb_substr($word, 0, -6, 1) == 'u'))
                ) {
                $word = mb_substr($word, 0, -mb_strlen($ends0));
            }
        }

        return $word;
    }

    public static function debugWord($word, $value, $messaje = '')
    {
        if($word != $value) return;
        fwrite(STDOUT, "\n$messaje");

    }

    public static function step1($word, $r1, $r2, $rv)
    {
        $r1_txt = mb_substr($word,$r1);
        $r2_txt = mb_substr($word,$r2);

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
            $word = mb_substr($word, 0, -mb_strlen($ends));
        } else {
            $ends = '';
            foreach ($suffix_r as $replace => $suffix_) {
                $ends = self::endsIn($r2_txt, $suffix_);
                if(!empty($ends)) {
                    $word = mb_substr($word, 0, -mb_strlen($ends)) . $replace;
                    break;
                }
            }
        }

        if(empty($ends)) {
            if('' != ($ends = self::endsIn($r2_txt, $suffix_amente))) {
                $word = mb_substr($word, 0, -mb_strlen($ends));
            } elseif('' != ($ends = self::endsIn($r1_txt, 'amente'))) {
                $word = mb_substr($word, 0, -mb_strlen($ends));
            } elseif('' != ($ends = self::endsIn($r2_txt, $suffix_remain))) {
                $word = mb_substr($word, 0, -mb_strlen($ends));
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

        $rv_txt = mb_substr($word,$rv);
        $ends = self::endsIn($rv_txt, $suffix);

        if (!empty($ends) && (mb_substr($word,-mb_strlen($ends)-1,1) == 'u')) {
            $word = mb_substr($word, 0, -mb_strlen($ends));
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
            'id', 'ase', 'iese', 'aste', 'iste',
            'aban', 'ían', 'aran', 'ieran', 'an', 'asen',
            'iesen', 'aron', 'ieron', 'ado', 'ido',
            'ando', 'iendo', 'ió', 'ar', 'er', 'ir',
            'abas', 'adas', 'idas', 'ías', 'aras',
            'ieras', 'as', 'ases', 'ieses', 'ís', 'áis', 'abais',
            'íais', 'arais', 'ierais', '  aseis', 'ieseis',
            'asteis', 'isteis', 'ados', 'idos', 'amos', 'ábamos',
            'íamos', 'imos', 'áramos', 'iéramos', 'iésemos', 'ásemos'
        );

        $rv_txt = mb_substr($word,$rv);

        if ('' != ($ends = self::endsIn($rv_txt, $suffix_b))) {
            $word = mb_substr($word, 0, -mb_strlen($ends));
        } elseif ('' != ($ends = self::endsIn($rv_txt, $suffix_a))) {
            $word = mb_substr($word, 0, -mb_strlen($ends));
            if('' != ($ends = self::endsIn($word, 'gu'))) {
                $word = mb_substr($word, 0, -1);
            }
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

        $rv_txt = mb_substr($word,$rv);

        if ('' != ($ends = self::endsIn($rv_txt, $suffix_a))) {
            $word = mb_substr($word, 0, -mb_strlen($ends));
        } elseif ('' != ($ends = self::endsIn($rv_txt, $suffix_b))) {
            $word = rtrim($word, $ends);
            $rv_txt = mb_substr($word, $rv);

            if(('' != ($ends = self::endsIn($rv_txt, 'u'))) &&
                ('' != ($ends = self::endsIn($word, 'gu')))) {
                $word = mb_substr($word, 0, -1);
            }
        }

        return $word;
    }

    public static function R1($word, $len)
    {
        $letters = self::mb_str_split($word);
        $r1 = $len = count($letters);

        for ($i = 0; $i < ($len - 1) && $r1 == $len; $i++) {
            if (self::isVowel($letters[$i]) && !self::isVowel($letters[$i+1])) {
                    $r1 = $i + 2;
            }
        }

        return $r1;
    }

    public static function R2($word, $len, $r1)
    {
        $letters = self::mb_str_split($word);
        $r2 = $len = count($letters);

        for ($i = $r1; $i < ($len - 1) && $r2 == $len; $i++) {
            if (self::isVowel($letters[$i]) && !self::isVowel($letters[$i+1])) {
                $r2 = $i+2;
            }
        }

        return $r2;
    }

    public static function RV($word, $len)
    {
        $letters = self::mb_str_split($word);
        $rv = $len = count($letters);

        if ($len > 3) {
            if (!self::isVowel($letters[1])) {
                $rv = self::getNextVowelPos($word, 2) + 1;
            } else if (self::isVowel($letters[0]) && self::isVowel($letters[1])) {
                $rv = self::getNextConsonantPos($word, 2) + 1;
            } else {
                $rv = 3;
            }
        }

        return $rv;
    }

    public static function mb_str_split( $string ) {
        # Split at all position not after the start: ^
        # and not before the end: $
        return preg_split('/(?<!^)(?!$)/u', $string );
    }
}

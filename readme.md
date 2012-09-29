Stemming
========

Es una libreria basada en el **Porter Stemming Algorithm**, tiene pequeños
cambios en el orden de algunos sufijos respecto al documento original

soporta caracteres UTF-8 mediante el uso de las funciones
mb_* de php.

la función mb_str_split esta basada en el ejemplo de
boukeversteegh at gmail dot com y puede encontrarse en la pagina
http://php.net/manual/en/function.mb-split.php.

##modo de uso

    <?php

    //  ...

    use Stemming\SpanishPorter;

    // ...

    $word = "abarcarán";
    $stem = SpanishPorter::stem($word);

    // The stem of the word 'abarcarán' is 'abarc'
    print sprintf("The stem of the word '%s' is '%s'", $word, $stem);

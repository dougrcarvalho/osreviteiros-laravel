<?php

/**
 * Idiomas do site público (o painel /admin continua em português).
 * O padrão (português) não leva prefixo na URL; os demais usam /en, /fr, /es.
 * Os textos ficam em lang/{codigo}.json — a chave é a própria frase em português.
 */
return [
    'padrao' => 'pt_BR',

    'disponiveis' => [
        'pt_BR' => ['prefixo' => null, 'nome' => 'Português', 'curto' => 'PT', 'html' => 'pt-BR', 'og' => 'pt_BR'],
        'en'    => ['prefixo' => 'en', 'nome' => 'English',   'curto' => 'EN', 'html' => 'en',    'og' => 'en_US'],
        'fr'    => ['prefixo' => 'fr', 'nome' => 'Français',  'curto' => 'FR', 'html' => 'fr',    'og' => 'fr_FR'],
        'es'    => ['prefixo' => 'es', 'nome' => 'Español',   'curto' => 'ES', 'html' => 'es',    'og' => 'es_ES'],
    ],
];

<?php
use \pchevrel\Verif;

include 'src/pchevrel/Verif.php';

$paths_before = [
    ['?firefox_locales&channel=org.mozilla.fennec_aurora', 200],
    ['?firefox_locales&channel=org.mozilla.firefox_beta', 200],
    ['?firefox_locales&channel=org.mozilla.firefox', 200],
    ['?play_locales', 200],
    ['?locale_mapping', 200],
    ['?locale_mapping&reverse', 200],
    ['?done', 200],
    ['?locale=fr', 200],
    ['?foo', 400],
];

$paths_after = [
    ['google/firefoxlocales/release/', 200],
    ['google/storelocales/', 200],
    ['google/localesmapping/', 200],
    ['google/localesmapping/?reverse', 200],
    ['google/done/release/', 200],
    ['google/translation/release/fr/', 200],
    ['error/translation/release/fr/', 400],

];

$obj1 = new Verif('Old API');
$obj1
    ->setHost('l10n.mozilla-community.org')
    ->setPathPrefix('~pascalc/google_play_description/api/');

$obj2 = new Verif('New API');
$obj2
    ->setHost('l10n.mozilla-community.org')
    ->setPathPrefix('~pascalc/stores_l10n/api/');

$check = function ($object, $paths) {
    foreach ($paths as $values) {
        list($path, $code) = $values;
        $object
            ->setPath($path)
            ->fetchContent()
            ->hasResponseCode($code)
            ->isJson();
    }
};

$check($obj1, $paths_before);
$check($obj2, $paths_after);

$equiv = [
    '?firefox_locales&channel=org.mozilla.firefox'  => 'google/firefoxlocales/release/',
    '?play_locales'                                 => 'google/storelocales/',
    '?locale_mapping'                               => 'google/localesmapping/',
    '?locale_mapping&reverse'                       => 'google/localesmapping/?reverse',
    '?done'                                         => 'google/done/release/',
    '?locale=fr'                                    => 'google/translation/release/fr/',
];

foreach ($equiv as $key => $value) {
    $before = $obj1->setPath($key)->fetchContent()->getContent();
    $after  = $obj2->setPath($value)->fetchContent()->getContent();
    if ($before !== $after) {
        $obj2->setError(
            "Difference in results between:\n"
             . $obj1->uri . "\n"
             . $obj2->uri . "\n"
        );
    }
}

$obj1->report();
$obj2->report();

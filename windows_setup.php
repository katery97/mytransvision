<?php

echo "generate config.ini ../r\n";

$dir = dirname(__FILE__);

$ini_dir = $dir.'/app/config/config.ini';

//Copy config.ini file
if(!copy($dir.'/app/config/config.ini-dev', $ini_dir)){
    echo "config generate error please check config folder...\r\n";
    exit();
}

//Deal the path in config.ini
$str = file_get_contents($ini_dir);
$str = str_replace('${TRANSVISIONDIR}', $dir, $str);
file_put_contents($ini_dir, $str);


$server_config = parse_ini_file($ini_dir);

if (! isset($server_config['l10nwebservice'])) {
    echo "Missing l10nwebservice parameter in config.ini";
    exit();
}

$uri = $server_config['l10nwebservice'];

$res = file_get_contents($uri);

if (! $repositories = json_decode(file_get_contents($uri), true)) {
    echo 'JSON source is not valid or not reachable.';
    exit();
}


foreach ($repositories as $repository) {

    if ($repository['enabled']) {
        // Save supported locales for each repository
        echo "* Saving list of locales for {$repository["id"]}\n";
        $file_name = "app/config/sources/{$repository["id"]}.txt";
        file_put_contents($file_name, implode("\n", $repository['locales']) . "\n");
    }
}

$json_repositories = [];
foreach ($repositories as $repository) {
    if ($repository['enabled']) {
        $json_repositories[intval($repository['display_order'])] = [
            'id'   => $repository['id'],
            'name' => $repository['name']
        ];
    }
}

ksort($json_repositories);
echo "* Saving JSON record of all supported repositories\n";
$file_name = "app/config/sources/supported_repositories.json";
file_put_contents($file_name, json_encode($json_repositories));

$file_content = json_encode($repositories[0]['locales']);
echo "* Saving JSON record of tools and their supported locales\n";

$file_name = "app/config/sources/tools.json";
file_put_contents($file_name, $file_content);



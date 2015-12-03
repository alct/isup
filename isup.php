#!/usr/bin/env php
<?php namespace IsUP;


// Execute the script from the right location

chdir(realpath(dirname(__FILE__)));


// CLI options

$opt = getopt('a:c:ej:lu');


// Config

$configFile = 'config.ini';

if (! file_exists($configFile)) {

    cli('config file "' . $configFile . '" not found');
    exit;
}

$config = parse_ini_file($configFile);

if (empty($config['url']) && empty($opt['a']) && empty($opt['c'])) {

    cli('the list of monitored resources is empty');
    exit;
}


// Functions

/**
 * Add a given URL to the list of monitored resources.
 *
 * @param   string  $url
 * @return  bool
 */
function add($url)
{
    $url     = trim($url);
    $content = 'url[] = "' . $url . '"' . PHP_EOL;

    return @file_put_contents($GLOBALS['configFile'], $content, FILE_APPEND) ? true : false;
}

/**
 * Check whether a given URL is up.
 *
 * @param   string  $url
 * @return  bool
 */
function check($url)
{
    $url = trim($url);
    $req = curl_init();

    curl_setopt_array($req,
        [
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_COOKIEJAR      => tempnam(sys_get_temp_dir(), 'curl'),
            CURLOPT_FAILONERROR    => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => ['DNT: 1'],
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_REFERER        => 'http://google.com',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:25.0) Gecko/20100101 Firefox/29.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
        ]
    );

    if (! $res = curl_exec($req)) return false;

    if (curl_getinfo($req, CURLINFO_HTTP_CODE) == '200') return true;

    return false;
}

/**
 * Wrap a given string and print it.
 *
 * @param   string  $string
 */
function cli($string)
{
    echo 'isup: ' . $string . PHP_EOL;
}

/**
 * Export a given log to JSON.
 *
 * @param   string  $url
 * @return  bool
 */
function export($url)
{
    $config = $GLOBALS['config'];

    $src  = $config['logpath'] . base64_encode($url) . '.log';
    $dest = $src . '.json';

    if (! $handle = @fopen($src, 'r')) return false;

    $data = [];
    $json = [];

    while (! feof($handle)) {

        $line = fgets($handle);

        if (empty($line)) continue;

        $frag = explode(' ', $line);

        $day    = date('Y-m-d', $frag[0]);
        $status = (int) trim($frag[1]);

        if (! array_key_exists($day, $data)) $data[$day] = ['status' => 0, 'nb' => 0];

        $data[$day]['status'] = $data[$day]['status'] + $status;
        $data[$day]['nb'] = $data[$day]['nb'] + 1;
    }

    fclose($handle);

    foreach ($data as &$item) {

        $item = (int) round(($item['status'] / $item['nb']) * 100);
    }

    unset($item);

    $json['url'] = $url;
    $json['log'] = $data;

    return @file_put_contents($dest, json_encode($json, JSON_PRETTY_PRINT)) ? true : false;
}

/**
 * Get the JSON formatted log for a given ID.
 *
 * @param   int  $id
 * @return  string|bool
 */
function get_json($id)
{
    $config = $GLOBALS['config'];

    if (! is_numeric($id) || $id < 0 || $id > count($config['url']) - 1) return false;

    $url = $config['url'][$id];

    $log  = $config['logpath'] . base64_encode($url) . '.log';
    $json = $log . '.json';

    if (! export($url)) return false;

    return file_exists($json) ? file_get_contents($json) . PHP_EOL : false;
}

/**
 * Create a log file for a given URL.
 *
 * @param   string  $url
 * @return  bool
 */
function log($url)
{
    $config = $GLOBALS['config'];

    $logfile = $config['logpath'] . base64_encode($url) . '.log';
    $status  = check($url) ? 1 : 0;
    $content = time() . ' ' . $status . PHP_EOL;

    return @file_put_contents($logfile, $content, FILE_APPEND) ? true : false;
}


// Where the magic happens

if (array_key_exists('a', $opt)) {

    foreach (explode(PHP_EOL, $opt['a']) as $url) {

        if (empty($url)) continue;

        $url = trim($url);

        if (add($url)) {

            cli('"' . $url . '" successfully added to the list of monitored resources');

        } else {

            cli('failed to add "' . $url . '" to the list of monitored resources');
        }
    }

} elseif (array_key_exists('c', $opt)) {

    foreach (explode(PHP_EOL, $opt['c']) as $url) {

        if (empty($url)) continue;

        $url = trim($url);

        if (check($url)) {

            cli('"' . $url . '" is up');

        } else {

            cli('"' . $url . '" is down');
        }
    }

} elseif (array_key_exists('e', $opt)) {

    foreach ($config['url'] as $url ) {

        if (export($url)) {

            cli('successfully generated JSON formatted log for "' . $url . '"');

        } else {

            cli('failed to generate JSON formatted log for "' . $url . '"');
        }
    }

} elseif (array_key_exists('j', $opt)) {

    if ($data = get_json($opt['j'])) {

        echo $data;

    } else {

        cli('failed to get JSON formatted log for id "' . $opt['j'] . '"');
    }

} elseif (array_key_exists('l', $opt)) {

    $i = 0;

    echo "ID\tURL" . PHP_EOL;

    foreach ($config['url'] as $url ) {

        echo "$i\t" . $url . PHP_EOL;
        $i++;
    }

} elseif (array_key_exists('u', $opt)) {

    foreach ($config['url'] as $url) {

        if (log($url)) {

            cli('successfully updated log for "' . $url . '"');

        } else {

            cli('failed to update log for "' . $url . '"');
        }
    }

} elseif (empty($opt)) {

    cli('missing argument');
}

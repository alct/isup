<?php namespace IsUP;


// Execute the script from the right location

chdir(realpath(dirname(__FILE__)));


// CLI options

$opt = getopt('a:c:ew');


// Get config

$configFile = 'config.ini';

if (! file_exists($configFile)) {

    cli('config file "' . $configFile . '" not found');
    exit;
}

$config = parse_ini_file($configFile);

if (empty($config['url']) && empty($opt['a'])) {

    cli('the watchlist is empty');
    exit;
}


// Functions

/**
 * Add a given URL to the watchlist.
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
 * Wrap a given string and print it to the terminal.
 *
 * @param   string  $value
 */
function cli($value)
{
    echo 'isup: ' . $value . PHP_EOL;
}

/**
 * Combine and export a given raw log to a JSON file.
 *
 * @param   string  $src
 * @return  bool
 */
function export($src)
{
    $config = $GLOBALS['config'];

    $src  = $config['logpath'] . $src . '.log';

    $dest = $src . '.json';

    if (! $handle = @fopen($src, 'r')) return false;

    $data = [];

    while(! feof($handle)){

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

    return @file_put_contents($dest, json_encode($data, JSON_PRETTY_PRINT)) ? true : false;
}

/**
 * Create a log file for a given url.
 *
 * @param   string  $url
 * @return  bool
 */
function log($url)
{
    $config = $GLOBALS['config'];

    $logfile = $config['logpath'] . $url . '.log';
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

            cli('"' . $url . '" successfully added to the watchlist');

        } else {

            cli('failed to add "' . $url . '" to the watchlist');
        }
    }

} elseif (array_key_exists('c', $opt)) {

    foreach (explode(PHP_EOL, $opt['c']) as $url) {

        if (empty($url)) continue;

        $url = trim($url);

        if (check($url)) {

            cli('"' . $url . '" seems to be up');

        } else {

            cli('"' . $url . '" seems to be down');
        }
    }

} elseif (array_key_exists('e', $opt)) {

    foreach ($config['url'] as $url ) {

        if (export($url)) {

            cli('successfully generated json for "' . $url . '"');

        } else {

            cli('failed to generate json for "' . $url . '"');
        }
    }

} elseif (array_key_exists('w', $opt)) {

    cli('watchlist');

    foreach ($config['url'] as $url ) {

        echo '- ' . $url . PHP_EOL;
    }

} elseif (empty($opt)) {

    foreach ($config['url'] as $url) {

        if (log($url)) {

            cli('successfully created logfile for "' . $url . '"');

        } else {

            cli('failed to create logfile for "' . $url . '"');
        }
    }
}

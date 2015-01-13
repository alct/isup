# IsUP?

Combined with `cron`, this tool logs the availability of one or several resources(s) over time. To determine whether a resource is available, `isup` performs an HTTP GET request and follows any redirection. If the final HTTP answer differs from "200 OK" or if the request times out, the resource is considered unavailable.

## Dependencies

* PHP 5.4+
* [cURL](http://curl.haxx.se/libcurl/php/)
* cron

## Installation

Clone the repository and rename the config file:

```bash
git clone https://github.com/alct/isup.git
cd isup && mv config.example config.ini
```

Add URL(s) to the watchlist:

```bash
php /path/to/isup.php -a "www.website.com" # or
php /path/to/isup.php -a "$(cat /path/to/list)" # where "list" is a file containing one URL per line
```

Add the following rule to your crontab:

```bash
*/10 * * * * php /path/to/isup.php
```

And... that's it.

## Options

### -a URL

Add one or a series of URL(s) to the watchlist.

```bash
php /path/to/isup.php -a "www.website.com" # or
php /path/to/isup.php -a "$(cat /path/to/list)" # where "list" is a file containing one URL per line
```

### -c URL

Check whether one or a series of resource(s) are available and write the result to standard output.

```bash
php /path/to/isup.php -c "www.website.com" # or
php /path/to/isup.php -c "$(cat /path/to/list)" # where "list" is a file containing one URL per line
```

### -e

Generate JSON based on the logs and write the result to separate files.

```bash
php /path/to/isup.php -e
```

### -w

Write the watchlist to standard output.

```bash
php /path/to/isup.php -w
```

## License

[GPLv3](LICENSE)

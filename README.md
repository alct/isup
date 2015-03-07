# IsUP?

Combined with `cron`, this tool logs the availability of one or several resources(s) over time. To determine whether a resource is available, `isup` performs an HTTP GET request and follows any redirection. If the final HTTP answer differs from "200 OK" or if the request times out, the resource is considered unavailable.

## Dependencies

* PHP 5.4+
* [cURL](http://curl.haxx.se/libcurl/php/)
* cron

## Installation

Clone the repository and create the config file:

```bash
git clone https://github.com/alct/isup.git ~/isup
cd isup && cp config.example config.ini
```

Add `isup` to the PATH:

```bash
sudo ln -s ~/isup/isup.php /usr/local/bin/isup
export PATH=$PATH
```

Add URL(s) to the list of monitored resources:

```bash
isup -a "example.com" # or
isup -a "$(cat /path/to/list)" # where "list" is a file containing one URL per line
```

Add the following rule to your crontab:

```bash
*/10 * * * * /path/to/isup.php -u > /dev/null
```

And... that's it.

## Options

### -a URL

Add one or a series of URL(s) to the list of monitored resources.

```bash
isup -a "example.com" # or
isup -a "$(cat /path/to/list)" # where "list" is a file containing one URL per line
```

### -c URL

Check whether one or a series of resource(s) are available.

```bash
isup -c "example.com" # or
isup -c "$(cat /path/to/list)" # where "list" is a file containing one URL per line
```

### -e

Export current logs to JSON.

```bash
isup -e
```



```bash
```

### -l

List monitored resources and IDs.

```bash
isup -l
```

## License

[GPLv3](LICENSE)

# nzb.to nZEDb Proxy

This is a simple Proxy which translates nzb.to requests to a nZEDb like format for sonarr, radarr, SickRage etc...

## requirements

- Webserver with PHP 5
- Tor SOCKS5 Proxy

## manual installation

1. copy files to webserver
2. copy `inc/config.inc.php.sample` to `inc/config.inc.php`
3. change values in `config.inc.php` most should be good to go but you'll need to set `SALT_KEY`, `TMDB_KEY` and `TORPROXY` (SALT_KEY should have 16, 24 or 32 chars)
4. make sure the webserver can write to `cache`, `logs`, `nzbs` and `cookies`
5. set documentroot to httpdocs
6. open website and generate your apikey
7. add url and apikey to your software

## run using docker

Note: we use a small `docker-compose` file to start a `tor socks5 proxy` as well as a `webserver` so you won't need to change the `TORPROXY` value in `config.inc.php` as it defaults to the docker one.

1. copy `inc/config.inc.php.sample` to `inc/config.inc.php`
2. change values in `config.inc.php` most should be good to go but you'll need to set `SALT_KEY` and `TMDB_KEY` (SALT_KEY should have 16, 24 or 32 chars)
3. run `docker-compose up`

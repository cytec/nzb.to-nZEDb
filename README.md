# nzb.to nZEDb Proxy

This is a simple Proxy which translates nzb.to requests to a nZEDb like format for sonarr, radarr, SickRage etc...

## requirements

- Webserver with PHP 5
- Tor SOCKS5 Proxy

## manual installation

1. copy files to webserver
2. copy `httpdocs/inc/config.inc.php.sample` to `httpdocs/inc/config.inc.php`
3. change values in `config.inc.php` most should be good to go but you'll need to set `SALT_KEY`, `TMDB_KEY` and `TORPROXY` (SALT_KEY should have 16, 24 or 32 chars)
4. make sure the webserver can write to `cache`, `logs`, `nzbs` and `cookies`
5. set documentroot to httpdocs
6. open website and generate your apikey
7. add url and apikey to your software

## run using docker

Note: we use a small `docker-compose` file to start a `tor socks5 proxy` as well as a `webserver` so you won't need to change the `TORPROXY` value in `config.inc.php` as it defaults to the docker one.

1. copy `httpdocs/inc/config.inc.php.sample` to `httpdocs/inc/config.inc.php`
2. change values in `config.inc.php` most should be good to go but you'll need to set `SALT_KEY` and `TMDB_KEY` (SALT_KEY should have 16, 24 or 32 chars)
3. run `docker-compose up`


## FAQ:

**Es wird nur eine leere weiße Seite angezeigt**

Aller wahrscheinlichkeit nach, musst du die Ordner berechtigungen anpassen.
Im log steht etwas wie: 

```[Fri Jul 26 10:21:02.801693 2019] [:error] [pid 433] [client 192.168.0.66:61683] PHP Fatal error: Uncaught --> Smarty: unable to write file /var/www/httpdocs/libs/smarty//templates_c/wrt5d3ad40ec3a402.19341450 <-- \n thrown in /var/www/httpdocs/libs/smarty/sysplugins/smarty_internal_write_file.php on line 4```

In der Konsole folgendes eingeben:

`docker exec -it nzbto-nzedb_nzbtoproxy_1 bash` und dort dann `chown -R www-data:www-data /var/www` ausführen.

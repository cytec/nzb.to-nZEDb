# nzb.to nZEDb Proxy

This is a simple Proxy which translates nzb.to requests to a nZEDb like format for sonarr, radarr, SickRage etc...

## requirements

- Webserver with PHP 5
- Tor SOCKS5 Proxy

## Installation

1. Copy files to webserver
2. Change values in `config.inc.php` most should be good to go but you'll need to set `SALT_KEY`, `TMDB_KEY` and `TORPROXY`
3. Make sure the webserver can write to `cache`, `logs`, `nzbs` and `cookies`
4. Set Documentroot to httpdocs
5. Open website and generate your apikey
6. add url and apikey to your software

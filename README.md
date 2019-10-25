# nzb.to nZEDb Proxy

This is a simple Proxy which translates nzb.to requests to a nZEDb like format for sonarr, radarr, SickRage etc...

## requirements

- Webserver with PHP 7
- composer for keeping libs up to date
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

## Note from r0x0r
Changed quite a bit to the password handling and search in general normalizen titles and getting rid of passwords with spaces double passwords and so on. Its all a bit hacky,
but as soon as i have more time I'll make finishing touches. I packaged the libs and hope i haven't forgotten anything important. php files are chmod 0644 and dirs
to the default 0755.
I added a UHD search and search by imdb,tmdb aswell as TVRage and tvdb. Right now there is a problem when more than one ID is sent (which nzbhydra does...) It will repeat the search for each ID.
I will improve that code once i got the time for it and also will add the remaining Sections like Audiobooks/Mp3 since those need a whole new overhaul of the matching it will be 
quite time consuming so that is low_priority untill i finished the api searches by ids. 
Anyways it works nicely for me since 6 months this way. Any suggestions are welcome and of course security fixes.
This is my first git commit so please bare with me, if I made errors after all i just began.

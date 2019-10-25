# TVMaze-PHP-API-Wrapper

An easier way to interact with TVMaze's endpoints. Developed in PHP.

### Installing VIA Composer
* composer require joshpinkney/tv-maze-php-api dev-master

### Goal
 * The goal of this API Wrapper is to turn TVMaze's endpoints into something more object orientated and readable
 * Provide a simple, open source project that anybody can contribute to

Supported Methods with full example below. Simple example found in Examples.php.

```php
<?php

    require_once "TVMazeIncludes.php";

    $Client = new JPinkney\TVMaze\Client;
    
    /*
     * List of simple ways you can interact with the api
     */
     
    //Return all tv shows relating to the given input
    $Client->TVMaze->search("Arrow");
    
    //Return the most relevant tv show to the given input
    $Client->TVMaze->singleSearch("The Walking Dead");
    
    //Allows show lookup by using TVRage or TheTVDB ID
    $Client->TVMaze->getShowBySiteID("TVRage", 33272);

    //Return all possible actors relating to the given input
    $Client->TVMaze->getPersonByName("Nicolas Cage");
    
    //Return all the shows in the given country and/or date
    $Client->TVMaze->getSchedule();
    
    //Return all information about a show given the show ID
    $Client->TVMaze->getShowByShowID(1);
    
    //Return all episodes for a show given the show ID
    $Client->TVMaze->getEpisodesByShowID(1);
    
    //Return the cast for a show given the show ID
    $Client->TVMaze->getCastByShowID(1);
    
    //Return a master list of TVMazes shows given the page number
    $Client->TVMaze->getAllShowsByPage(2);
    
    //Return an actor given their ID
    $Client->TVMaze->getPersonByID(50);
    
    //Return an array of all the shows a particular actor has been in
    $Client->TVMaze->getCastCreditsByID(25);
    
    //Return an array of all the positions a particular actor has been in
    $Client->TVMaze->getCrewCreditsByID(100);
    
?>
```

### Open Source Projects using this

 * [nZEDb](https://github.com/nZEDb/nZEDb) Website Link: http://www.nzedb.com/
 * [newznab-tmux](https://github.com/DariusIII/newznab-tmux)

phpNemoServer/Nemo Readme
===================

Version 0.92b 
phpNemoServer is a set of PHP-scripts to manage JNTP Server.  
http://news.nemoweb.net/

Copyright
---------

Copyright (C) 2013-2016
    Julien Arlandis <julien.arlandis_at_gmail.com>

License
-------

http://www.gnu.org/licenses/agpl.txt

Requirements
------------

* PHP 5.2 or later
* MongoDB 2.4 or later
* libcurl

Support
-------

See reference about server support forums under \<news:nemo.dev.serveur\>  
See reference about client support forums under \<news:nemo.dev.client\>

Installation
------

* Go to your website root directory.
* Install the most popular JNTP client (Nemo) :

```sh
git clone https://github.com/Julien-Arlandis/NemoClient.git .
```

* Install JNTP server :

```sh
git clone https://github.com/Julien-Arlandis/phpNemoServer.git jntp
```

* Go to http://yourserver/jntp/ and follow the instructions. Greats :)

Upgrade Server
-------

```sh
git pull
```

Export users to user.json file
-------

```sh
mongoexport --db <database> --collection user --out user.json
```

Import users from user.json file
-------

```sh
mongoimport --db <database> --collection user --file user.json
```
```sh
mongo <<EOF
use <database>
db.counters.findAndModify({
    query: {"_id":"UserID"},
    update: {"seq":db.user.find().sort({"UserID":-1}).limit(1).next().UserID},
    upsert: true
});
EOF
```

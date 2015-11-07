phpNemoServer/Nemo Readme
===================

Version 0.89f

phpNemoServer is a set of PHP-scripts to manage JNTP Server.

http://news.nemoweb.net/

Copyright
---------

Copyright (C) 2013-2014
    Julien Arlandis <julien.arlandis_at_gmail.com>

License
-------

http://www.gnu.org/licenses/agpl.txt

Requirements
------------

* PHP 5.2 or later
* MongoDB 2.4 or later
* libcurl
* mod_rewrite

Support
-------

See reference about server support forums under <news:nemo.dev.serveur>
See reference about client support forums under <news:nemo.dev.client>

Installation
------

Copy phpNemoServer.tar.gz in your website.  
tar xzvf phpNemoServer.tar.gz

With github :  
git clone https://github.com/Julien-Arlandis/phpNemoServer.git webSiteDirectory

Go to http://yourserver/NemoServer/install.php.  
Delete install.php file.  
Copy .htaccess at the root folder of the site.  
Configure your feed in Applications/core/conf/description.json, do not forget to set actif=1 for active feeds.  
Greats :)

Procedure to export users to user.json file
-------

mongoexport --db <database> --collection user --out user.json

Procedure to import users from user.json file
-------

mongoimport --db <database> --collection user --file user.json  
mongo <<EOF  
use <database>  
db.counters.findAndModify({  
    query: {"_id":"UserID"},  
    update: {"seq":db.user.find().sort({"UserID":-1}).limit(1).next().UserID},  
    upsert: true  
});  
EOF  

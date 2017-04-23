<?php

/**
Copyright © 2013-2016 Julien Arlandis
    @author : Julien Arlandis <julien.arlandis_at_gmail.com>
    @Licence : http://www.gnu.org/licenses/agpl-3.0.txt

This file is part of PhpNemoServer.

    PhpNemoServer is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PhpNemoServer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with PhpNemoServer.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once( __DIR__."/start.php" );

error_reporting( ~E_NOTICE );
header( "Cache-Control: no-cache, must-revalidate" );

JNTP::init();
if( $_SERVER['QUERY_STRING'] ) { die( JNTP::getResource( $_SERVER['QUERY_STRING'] ) ); }
JNTP::setSession();
JNTP::exec( file_get_contents( "php://input" ) );
JNTP::send();

<?php

/**
Copyright © 2013-2014 Julien Arlandis
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
header("Cache-Control: no-cache, must-revalidate");
require_once(__DIR__."/Applications/core/lib/class.jntp.php");


if( !file_exists( __DIR__ . '/conf/config.php'))
{
	require_once(__DIR__."/install.php");
}

if( file_exists( __DIR__ . '/sleep'))
{
	die( 'You must remove sleep file to continue<br><strong>rm jntp/sleep</strong>' );
}

$jntp = new JNTP();


// Permit local connection
if(!isset($_SERVER['HTTP_REFERER']) || $jntp->config['crossDomainAccept'])
{
	header("Access-Control-Allow-Headers: JNTP-Session");
	header("Access-Control-Allow-Origin: *");
	if(isset( $_SERVER['JNTP-Session'] ) && $_SERVER['JNTP-Session'] != '')
	{
		$_COOKIE['JNTP-Session'] = $_SERVER['JNTP-Session'];
	}
}

$post = file_get_contents("php://input");
$queryString = $_SERVER['QUERY_STRING'];
if($queryString = $_SERVER['QUERY_STRING'])
{
	die( $jntp->getResource($queryString) );
}

if($post === '')
{
	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == true) ? 'https' : 'http';
	die( "200 ".$protocol.'://'.$jntp->config{'domain'}.'/jntp/ - PhpNemoServer/'.$jntp->config{'serverVersion'}.' - JNTP Service Ready - '.$jntp->config{'administrator'}.' - Type ["help"] for help' );
}

$jntp->log($post);
$jntp->exec($post);
$jntp->send();


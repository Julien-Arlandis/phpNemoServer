-<?php

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

function J2_($server, $jid, $dataid, $datatype)
{
	$propose = array();
	$propose[0]{'Jid'} = $jid;
	$propose[0]{'Data'}{'DataType'} = $datatype;
	if($jid != $dataid)
	{
		$propose[0]{'Data'}{'DataID'} = $dataid;
	}

	$post = array();
	$post[0] = "diffuse";
	$post[1]{'Propose'} = $propose;
	$post[1]{'From'} = JNTP::$config['domain'];

	JNTP::exec($post, $server);

	Tools::logFeed($post, $server, '(SEND)');
	Tools::logFeed(JNTP::$reponse, $server, '(RESP)');

	if(JNTP::$reponse{'code'} == '200')
	{
		foreach(JNTP::$reponse{'body'}{'Jid'} as $jid)
		{
			$post = array();
			$post[0] = "diffuse";
			$post[1]{'Packet'} = JNTP::getPacket( array('Jid'=>$jid) );
			$post[1]{'From'} = JNTP::$config['domain'];
			JNTP::exec($post, $server);

			Tools::logFeed($post, $server, '(SEND)');
			Tools::logFeed(JNTP::$reponse, $server, '(RESP)');
		}
	}
}

if(count($argv)>1)
{
	require_once(__DIR__."/../lib/class.jntp.php");
	JNTP::init();
	J2_($argv[1], $argv[2], $argv[3], $argv[4]);
}

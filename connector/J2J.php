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

function J2J($server, $jid, $datatype, $dataid) 
{
	$jntp = new JNTP();

	$propose = array();
	$propose{'Jid'} = $jid;
	$propose{'Data'}{'DataID'} = $dataid;
	$propose{'Data'}{'DataType'} = $datatype;

	$post = array();
	$post[0] = "diffuse";
	$post[1]{'Propose'} = $propose;
	$post[1]{'From'} = $jntp->domain;

	$jntp->exec($post, $server);
	$jntp->logFeed($post, $server, '>');
	//$jntp->logFeed($jntp->reponse, $server, '<');

	if($jntp->reponse[0] != 'iwant' || count($jntp->reponse[1]{'Jid'}) == 0 ) 
	{
		return; 
	}

	foreach($jntp->reponse[1]{'Jid'} as $jid)
	{
		$post = null;
		$post[0] = "diffuse";
		$post[1]{'From'} = $jntp->domain;
		$post[1]{'Packet'} = $jntp->getPacket($jid);

		$jntp->exec(json_encode($post), $server);

		$jntp->logFeed($post, $server, '>');
		//$jntp->logFeed($this->reponse, $server);
	}
}

if(count($argv)>1) 
{
	require_once(__DIR__."/../core/conf/config.php");
	require_once(__DIR__."/../core/lib/class.jntp.php");
	J2J($argv[1], $argv[2]);
}

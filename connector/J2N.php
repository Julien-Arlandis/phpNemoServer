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

function J2N($server, $jid, $dataid, $datatype)
{
	$jntp = new JNTP();
	$fp = fsockopen($server, 119, $errno, $errstr, 10);
	fgets($fp, 128);
	if (!$fp && $argv[0]) { die ($errstr." ".$errno."\n"); }

	$put = "CHECK <".$dataid.">\n";
	fputs($fp, $put);
	$jntp->logFeed($put, $server, '>');
	$reponse = fgets($fp);
	$jntp->logFeed($reponse, $server, '<');

	$reponses = preg_split("/[\s]+/", $reponse);
	if ($reponses[0] == "238")
	{
		$packet = $jntp->getPacket( array('DataType'=>'Article', 'Data.DataID'=>$dataid) );
		$put = "TAKETHIS <".$packet{'Data'}{'DataID'}.">\n".NNTP::articleJ2N($packet)."\r\n.\r\n";
		fputs($fp, $put);
		$jntp->logFeed($put, $server, '>');
		$reponse = fgets($fp);
		$reponses = preg_split("/[\s]+/", $reponse);
		$jntp->logFeed($reponse, $server, '<');
	}
	fclose($fp);
}

if(count($argv)>1) 
{
	require_once(__DIR__."/../Applications/core/lib/class.jntp.php");
	require_once(__DIR__."/../Applications/NemoNetwork/lib/class.nntp.php");
	J2J($argv[1], $argv[2], $argv[3], $argv[4]);
}

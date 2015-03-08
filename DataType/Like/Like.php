<?php
class DataType
{
	var $jidlike;
	var $total = 0;

	function __construct() 
	{
	}

	function isValidData()
	{
		global $jntp;
		if(!$jntp->userid) {
			$jntp->reponse{'body'} = 'Vous devez être connecté pour liker';
			return false;
		}
		$this->total = $jntp->mongo->packet->find(array("Data.UserID"=>$jntp->userid, "Data.DataType" => "Like", "Data.JidLike" => $jntp->packet{'Data'}{'JidLike'}))->count();
		if($this->total > 0)
		{
			$jntp->reponse{'body'} = 'Vous avez déjà liké cet article';
			return false;
		}
		else
		{
			return true;
		}
	}

	function forgeData()
	{
		global $jntp;
		$jntp->packet{'Data'}{'DataID'} = "";
		$jntp->packet{'Data'}{'InjectionDate'} = date("Y-m-d")."T".date("H:i:s")."Z";
		$jntp->packet{'Data'}{'OriginServer'} = $jntp->config{'domain'};
		$jntp->packet{'Data'}{'Organization'} = $jntp->config{'organization'};
		$jntp->packet{'Data'}{'Browser'} = $_SERVER['HTTP_USER_AGENT'];
		$jntp->packet{'Data'}{'PostingHost'} = sha1($_SERVER['REMOTE_ADDR']);
		$jntp->packet{'Data'}{'ComplaintsTo'} = $jntp->config{'administrator'};
		$jntp->packet{'Data'}{'ProtocolVersion'} = $jntp->config{'protocolVersion'};
		$jntp->packet{'Data'}{'Server'} = "PhpNemoServer/".$jntp->config{'serverVersion'};
		$jntp->packet{'Data'}{'UserID'} = $jntp->userid;
	}

	function beforeInsertion()
	{
		global $jntp;
		return true;
	}

	function afterInsertion()
	{
		global $jntp;
		$jntp->mongo->packet->update(array("Jid"=>$jntp->packet{'Data'}{'JidLike'}), array( '$inc' => array("Meta.Like"=>1)) );
		// Diffuse le paquet sur le réseau
		$jntp->superDiffuse();
		return true;
	}
}

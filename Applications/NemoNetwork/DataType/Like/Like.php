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
		if(!JNTP::$userid) {
			JNTP::$reponse{'info'} = 'Vous devez être connecté pour liker';
			return false;
		}
		$this->total = JNTP::$mongo->packet->find(array("Data.UserID"=>JNTP::$userid, "Data.DataType" => "Like", "Data.DataIDLike" => JNTP::$packet{'Data'}{'DataIDLike'}))->count();
		if($this->total > 0)
		{
			JNTP::$reponse{'info'} = 'Vous avez déjà liké cet article';
			return false;
		}
		else
		{
			return true;
		}
	}

	function forgeData()
	{
		JNTP::$packet{'Data'}{'DataID'} = "";
		JNTP::$packet{'Data'}{'InjectionDate'} = date("Y-m-d")."T".date("H:i:s")."Z";
		JNTP::$packet{'Data'}{'OriginServer'} =JNTP::$config{'domain'};
		JNTP::$packet{'Data'}{'Organization'} =JNTP::$config{'organization'};
		JNTP::$packet{'Data'}{'Browser'} = $_SERVER['HTTP_USER_AGENT'];
		JNTP::$packet{'Data'}{'PostingHost'} = sha1($_SERVER['REMOTE_ADDR']);
		JNTP::$packet{'Data'}{'ComplaintsTo'} =JNTP::$config{'administrator'};
		JNTP::$packet{'Data'}{'ProtocolVersion'} =JNTP::$config{'protocolVersion'};
		JNTP::$packet{'Data'}{'Server'} = "PhpNemoServer/".JNTP::$config{'serverVersion'};
		JNTP::$packet{'Data'}{'UserID'} = JNTP::$userid;
	}

	function beforeInsertion()
	{
		return true;
	}

	function afterInsertion()
	{
		JNTP::$mongo->packet->update( array("Data.DataID"=>JNTP::$packet{'Data'}{'DataIDLike'}), array( '$inc' => array("Meta.Like"=>1)) );
		// Diffuse le paquet sur le réseau
		JNTP::superDiffuse();
		return true;
	}
}

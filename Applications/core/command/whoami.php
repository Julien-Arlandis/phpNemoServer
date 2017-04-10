<?php

if( JNTP::$id )
{
	$obj = JNTP::$mongo->user->findOne( array('UserID' => JNTP::$id) );
	JNTP::$reponse{'code'} = "200";
	JNTP::$reponse{'body'} = array("FromName"=>$obj{'FromName'}, "FromMail"=>$obj{'FromMail'}, "ReplyTo"=>$obj{'ReplyTo'}, "UserID"=>JNTP::$userid, "email"=>$obj{'email'}, "privilege"=>JNTP::$privilege, "Session"=>JNTP::$session, "hashkey"=>$obj{'hashkey'});
	JNTP::$reponse{'info'} = "User ".JNTP::$userid." connected on ".JNTP::$config{'domain'};
}
else
{
	JNTP::$reponse{'code'} = "400";
	JNTP::$reponse{'body'} = array();
	JNTP::$reponse{'info'} = "User not connected";
}

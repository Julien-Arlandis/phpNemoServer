<?php

if( $jntp->id )
{
	$obj = $jntp->mongo->user->findOne( array('UserID' => $jntp->id) );
	$jntp->reponse{'code'} = "200";
	$jntp->reponse{'body'} = array("FromName"=>$obj{'FromName'}, "FromMail"=>$obj{'FromMail'}, "ReplyTo"=>$obj{'ReplyTo'}, "UserID"=>$jntp->userid, "email"=>$obj{'email'}, "privilege"=>$jntp->privilege, "Session"=>$jntp->session, "hashkey"=>$obj{'hashkey'});
	$jntp->reponse{'info'} = "User ".$jntp->userid." connected on ".$jntp->config{'domain'};
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'body'} = array();
	$jntp->reponse{'info'} = "User not connected";
}

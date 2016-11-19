<?php

if( $this->id )
{
	$obj = $this->mongo->user->findOne( array('UserID' => $this->id) );
	$this->reponse{'code'} = "200";
	$this->reponse{'body'} = array("FromName"=>$obj{'FromName'}, "FromMail"=>$obj{'FromMail'}, "ReplyTo"=>$obj{'ReplyTo'}, "UserID"=>$this->userid, "email"=>$obj{'email'}, "privilege"=>$this->privilege, "Session"=>$this->session, "hashkey"=>$obj{'hashkey'});
	$this->reponse{'info'} = "User ".$this->userid." connected on ".$this->config{'domain'};
}
else
{
	$this->reponse{'code'} = "400";
	$this->reponse{'body'} = array();
	$this->reponse{'info'} = "User not connected";
}

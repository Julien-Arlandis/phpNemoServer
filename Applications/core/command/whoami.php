<?php
$this->setSession();
if( $this->id )
{
	$obj = $this->mongo->user->findOne( array('UserID' => $this->id) );
	$this->reponse{'code'} = "200";
	$this->reponse{'body'} = array("FromName"=>$obj{'FromName'}, "FromMail"=>$obj{'FromMail'}, "ReplyTo"=>$obj{'ReplyTo'}, "UserID"=>$this->userid, "email"=>$obj{'email'}, "privilege"=>$this->privilege, "Session"=>$this->session, "hashkey"=>$obj{'hashkey'});
	$this->reponse{'server'} = $this->config{'domain'};
}
else
{
	$this->reponse{'code'} = "400";
	$this->reponse{'info'} = "User not connected";
	$this->reponse{'body'} = array();
}

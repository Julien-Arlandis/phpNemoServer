<?php
$this->setSession();
if( $this->id )
{
	$obj = $this->mongo->user->findOne( array('UserID' => $this->id) );
	$this->reponse{'code'} = "200";
	$this->reponse{'body'} = array("FromName"=>$obj{'FromName'}, "FromMail"=>$obj{'FromMail'}, "ReplyTo"=>$obj{'ReplyTo'}, "UserID"=>$this->userid, "email"=>$obj{'email'}, "privilege"=>$this->privilege, "Session"=>$this->session, "hashkey"=>$obj{'hashkey'});
}
else
{
	$this->reponse{'code'} = "500";
	$this->reponse{'body'} = array();
}

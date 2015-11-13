<?php

if(strlen($this->param{'user'}) < 1)
{
	$this->reponse{'code'} = "500";
	$this->reponse{'info'} = "Bad parameters";
	$this->send();
}

$email = new MongoRegex("/^".preg_quote($this->param{'user'})."$/i");
$obj = $this->mongo->user->findOne(  array('$or' => array(array('email' => $email), array('user' => $email)))     );

if(count($obj) > 0)
{
	if(sha1($obj{'checksum'}.$this->param{'password'}) != $obj{'password'}) 
	{
		$this->reponse{'code'} = "400";
		$this->reponse{'info'} = "Bad authentification";
		$this->send();
	}

	if($obj{'check'})
	{
		$this->reponse{'code'} = "401";
		$this->reponse{'info'} = "The account has not yet been validated";
		$this->send();
	}

	$this->startSession($obj{'Session'}, $obj{'UserID'}, $obj{'privilege'});

	$this->reponse{'code'} = "200";
	$this->reponse{'body'} = array("FromName"=>$obj{'FromName'}, "FromMail"=>$obj{'FromMail'}, "ReplyTo"=>$obj{'ReplyTo'}, "UserID"=>$this->userid, "email"=>$obj{'email'}, "privilege"=>$this->privilege, "Session"=>$this->session, "HashKey"=>$obj{'hashkey'});
	$this->reponse{'info'} = $this->userid." connected";
}
else
{
	$this->reponse{'code'} = "402";
	$this->reponse{'info'} = "Bad user";
}

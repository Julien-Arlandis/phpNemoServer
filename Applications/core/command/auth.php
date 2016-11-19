<?php

if(strlen($jntp->param{'user'}) < 1)
{
	$jntp->reponse{'code'} = "500";
	$jntp->reponse{'info'} = "Bad parameters";
	$jntp->send();
}

$email = new MongoRegex("/^".preg_quote($jntp->param{'user'})."$/i");
$obj = $jntp->mongo->user->findOne(  array('$or' => array(array('email' => $email), array('user' => $email)))     );

if(count($obj) > 0)
{
	if(sha1($obj{'checksum'}.$jntp->param{'password'}) != $obj{'password'})
	{
		$jntp->reponse{'code'} = "400";
		$jntp->reponse{'info'} = "Bad authentification";
		$jntp->send();
	}

	if($obj{'check'})
	{
		$jntp->reponse{'code'} = "401";
		$jntp->reponse{'info'} = "The account has not yet been validated";
		$jntp->send();
	}

	$jntp->startSession($obj{'Session'}, $obj{'UserID'}, $obj{'privilege'});

	$jntp->reponse{'code'} = "200";
	$jntp->reponse{'body'} = array("FromName"=>$obj{'FromName'}, "FromMail"=>$obj{'FromMail'}, "ReplyTo"=>$obj{'ReplyTo'}, "UserID"=>$jntp->userid, "email"=>$obj{'email'}, "privilege"=>$jntp->privilege, "Session"=>$jntp->session, "HashKey"=>$obj{'hashkey'});
	$jntp->reponse{'info'} = $jntp->userid." connected";
}
else
{
	$jntp->reponse{'code'} = "402";
	$jntp->reponse{'info'} = "Bad user";
}

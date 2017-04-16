<?php

if(strlen(JNTP::$param{'user'}) < 1)
{
	JNTP::$reponse{'code'} = "500";
	JNTP::$reponse{'info'} = "Bad parameters";
	JNTP::send();
}

$email = new MongoRegex("/^".preg_quote(JNTP::$param{'user'})."$/i");
$obj = JNTP::$mongo->user->findOne(  array('$or' => array(array('email' => $email), array('user' => $email)))     );

if(count($obj) > 0)
{
	if(sha1($obj{'checksum'}.JNTP::$param{'password'}) != $obj{'password'})
	{
		JNTP::$reponse{'code'} = "400";
		JNTP::$reponse{'info'} = "Bad authentification";
		JNTP::send();
	}

	if($obj{'check'})
	{
		JNTP::$reponse{'code'} = "401";
		JNTP::$reponse{'info'} = "The account has not yet been validated";
		JNTP::send();
	}

	JNTP::startSession($obj{'Session'}, $obj{'UserID'}, $obj{'privilege'});

	JNTP::$reponse{'code'} = "200";
	JNTP::$reponse{'body'} = array("FromName"=>$obj{'FromName'}, "FromMail"=>$obj{'FromMail'}, "ReplyTo"=>$obj{'ReplyTo'}, "UserID"=>JNTP::$userid, "email"=>$obj{'email'}, "privilege"=>JNTP::$privilege, "Session"=>JNTP::$session, "HashKey"=>$obj{'hashkey'});
	JNTP::$reponse{'info'} = JNTP::$userid." connected";
}
else
{
	JNTP::$reponse{'code'} = "402";
	JNTP::$reponse{'info'} = "Bad user";
}

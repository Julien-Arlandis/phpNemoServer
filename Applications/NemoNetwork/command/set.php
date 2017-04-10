<?php

if(JNTP::$userid)
{
	if(isset(JNTP::$param{'email'}))
	{
		JNTP::$reponse{'code'} = "200";

		if (!preg_match('#^[\w.-]+@[\w.-]+\.[a-z]{2,6}$#i', JNTP::$param{'email'}))
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "Email invalide";
		}

		$total = JNTP::$mongo->user->find(array('email' => JNTP::$param{'email'}))->count();
		$total = ($total > 0 ) ? $total : 0;

		if ( $total > 0)
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "Email déjà pris";
		}

		if(JNTP::$reponse{'code'} == "200")
		{
			JNTP::updateUserConfig( array("email" => JNTP::$param{'email'}) );
			JNTP::$reponse{'info'} = "Email modifié";
		}
	}
	if(isset(JNTP::$param{'password'}))
	{
		JNTP::$reponse{'code'} = "200";

		if(strlen(JNTP::$param{'password'}) < 4)
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "Password trop court";
		}
		else
		{
			$obj = JNTP::$mongo->user->findOne( array('UserID' => JNTP::$id) );
			if(sha1($obj{'checksum'}.JNTP::$param{'oldPassword'}) != $obj{'password'})
			{
				JNTP::$reponse{'info'} = "Ancien password incorrect";
				JNTP::$reponse{'code'} = "400";
			}

		}
		if(JNTP::$reponse{'code'} == "200")
		{
			$res = JNTP::$mongo->user->findAndModify(
				array("UserID" => JNTP::$id ),
				array('$set' => array("password" => sha1($obj{'checksum'}.JNTP::$param{'password'}) ) )
			);
			JNTP::$reponse{'info'} = "password modifié";
		}
	}
	if(isset(JNTP::$param{'FromName'}))
	{
		if(strlen(JNTP::$param{'FromName'}) > 50)
		{
			JNTP::$reponse{'info'} = "Trop long";
			JNTP::$reponse{'code'} = "400";
		}
		else
		{
			JNTP::updateUserConfig( array("FromName" => JNTP::$param{'FromName'}) );
			JNTP::$reponse{'code'} = "200";
			JNTP::$reponse{'info'} = "FromName modifié";
		}
	}
	if(isset(JNTP::$param{'FromMail'}))
	{
		if(strlen(JNTP::$param{'FromMail'}) > 50)
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "Trop long";
		}
		else
		{
			JNTP::updateUserConfig( array("FromMail" => JNTP::$param{'FromMail'}) );
			JNTP::$reponse{'code'} = "200";
			JNTP::$reponse{'info'} = "FromMail modifié";
		}
	}
	if(isset(JNTP::$param{'ReplyTo'}))
	{
		if(strlen(JNTP::$param{'ReplyTo'}) > 50)
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "Trop long";
		}
		else
		{
			JNTP::updateUserConfig( array("ReplyTo" => JNTP::$param{'ReplyTo'}) );
			JNTP::$reponse{'code'} = "200";
			JNTP::$reponse{'info'} = "ReplyTo modifié";
		}
	}
	if(isset(JNTP::$param{'hashkey'}))
	{
		if(strlen(JNTP::$param{'HashKey'}) > 30)
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "Trop long";
		}
		else
		{
			JNTP::updateUserConfig( array("hashkey" => JNTP::$param{'hashkey'}) );
			JNTP::$reponse{'code'} = "200";
			JNTP::$reponse{'info'} = "hashkey modifié";
		}
	}
}
else
{
	JNTP::$reponse{'code'} = "400";
	JNTP::$reponse{'info'} = "Not connected";
}

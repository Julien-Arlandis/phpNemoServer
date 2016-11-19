<?php

if($jntp->userid)
{
	if(isset($jntp->param{'email'}))
	{
		$jntp->reponse{'code'} = "200";
	
		if (!preg_match('#^[\w.-]+@[\w.-]+\.[a-z]{2,6}$#i', $jntp->param{'email'}))
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "Email invalide";
		}

		$total = $jntp->mongo->user->find(array('email' => $jntp->param{'email'}))->count();
		$total = ($total > 0 ) ? $total : 0;

		if ( $total > 0)
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "Email déjà pris";
		}

		if($jntp->reponse{'code'} == "200")
		{
			$jntp->updateUserConfig( array("email" => $jntp->param{'email'}) );
			$jntp->reponse{'info'} = "Email modifié";
		}
	}
	if(isset($jntp->param{'password'}))
	{
		$jntp->reponse{'code'} = "200";

		if(strlen($jntp->param{'password'}) < 4)
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "Password trop court";
		}
		else
		{
			$obj = $jntp->mongo->user->findOne( array('UserID' => $jntp->id) );
			if(sha1($obj{'checksum'}.$jntp->param{'oldPassword'}) != $obj{'password'})
			{
				$jntp->reponse{'info'} = "Ancien password incorrect";
				$jntp->reponse{'code'} = "400";
			}

		}
		if($jntp->reponse{'code'} == "200")
		{
			$res = $jntp->mongo->user->findAndModify(
				array("UserID" => $jntp->id ),
				array('$set' => array("password" => sha1($obj{'checksum'}.$jntp->param{'password'}) ) )
			);
			$jntp->reponse{'info'} = "password modifié";
		}
	}
	if(isset($jntp->param{'FromName'}))
	{
		if(strlen($jntp->param{'FromName'}) > 50)
		{
			$jntp->reponse{'info'} = "Trop long";
			$jntp->reponse{'code'} = "400";
		}
		else
		{
			$jntp->updateUserConfig( array("FromName" => $jntp->param{'FromName'}) );
			$jntp->reponse{'code'} = "200";
			$jntp->reponse{'info'} = "FromName modifié";
		}
	}
	if(isset($jntp->param{'FromMail'}))
	{
		if(strlen($jntp->param{'FromMail'}) > 50)
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "Trop long";
		}
		else
		{
			$jntp->updateUserConfig( array("FromMail" => $jntp->param{'FromMail'}) );
			$jntp->reponse{'code'} = "200";
			$jntp->reponse{'info'} = "FromMail modifié";
		}
	}
	if(isset($jntp->param{'ReplyTo'}))
	{
		if(strlen($jntp->param{'ReplyTo'}) > 50)
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "Trop long";
		}
		else
		{
			$jntp->updateUserConfig( array("ReplyTo" => $jntp->param{'ReplyTo'}) );
			$jntp->reponse{'code'} = "200";
			$jntp->reponse{'info'} = "ReplyTo modifié";
		}
	}
	if(isset($jntp->param{'hashkey'}))
	{
		if(strlen($jntp->param{'HashKey'}) > 30)
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "Trop long";
		}
		else
		{
			$jntp->updateUserConfig( array("hashkey" => $jntp->param{'hashkey'}) );
			$jntp->reponse{'code'} = "200";
			$jntp->reponse{'info'} = "hashkey modifié";
		}
	}
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'info'} = "Not connected";
}

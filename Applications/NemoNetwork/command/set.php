<?php

$this->setSession();
if($this->userid)
{
	if(isset($this->param{'email'}))
	{
		$this->reponse{'code'} = "200";
	
		if (!preg_match('#^[\w.-]+@[\w.-]+\.[a-z]{2,6}$#i', $this->param{'email'}))
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = "Email invalide";
		}

		$total = $this->mongo->user->find(array('email' => $this->param{'email'}))->count();
		$total = ($total > 0 ) ? $total : 0;

		if ( $total > 0)
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = "Email déjà pris";
		}

		if($this->reponse{'code'} == "200") 
		{
			$this->updateUserConfig( array("email" => $this->param{'email'}) );
			$this->reponse{'info'} = "Email modifié";
		}
	}
	if(isset($this->param{'password'}))
	{
		$this->reponse{'code'} = "200";

		if(strlen($this->param{'password'}) < 4) 
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = "Password trop court";
		}
		else
		{
			$obj = $this->mongo->user->findOne( array('UserID' => $this->id) );
			if(sha1($obj{'checksum'}.$this->param{'oldPassword'}) != $obj{'password'})
			{
				$this->reponse{'info'} = "Ancien password incorrect";
				$this->reponse{'code'} = "400";
			}

		}
		if($this->reponse{'code'} == "200") 
		{
			$res = $this->mongo->user->findAndModify(
				array("UserID" => $this->id ),
				array('$set' => array("password" => sha1($obj{'checksum'}.$this->param{'password'}) ) )
			);
			$this->reponse{'info'} = "password modifié";
		}
	}
	if(isset($this->param{'FromName'}))
	{
		if(strlen($this->param{'FromName'}) > 50) 
		{
			$this->reponse{'info'} = "Trop long";
			$this->reponse{'code'} = "400";
		}
		else
		{
			$this->updateUserConfig( array("FromName" => $this->param{'FromName'}) );
			$this->reponse{'code'} = "200";
			$this->reponse{'info'} = "FromName modifié";
		}
	}
	if(isset($this->param{'FromMail'}))
	{
		if(strlen($this->param{'FromMail'}) > 50) 
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = "Trop long";
		}
		else
		{
			$this->updateUserConfig( array("FromMail" => $this->param{'FromMail'}) );
			$this->reponse{'code'} = "200";
			$this->reponse{'info'} = "FromMail modifié";
		}
	}
	if(isset($this->param{'ReplyTo'}))
	{
		if(strlen($this->param{'ReplyTo'}) > 50) 
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = "Trop long";
		}
		else
		{
			$this->updateUserConfig( array("ReplyTo" => $this->param{'ReplyTo'}) );
			$this->reponse{'code'} = "200";
			$this->reponse{'info'} = "ReplyTo modifié";
		}
	}
	if(isset($this->param{'hashkey'}))
	{
		if(strlen($this->param{'HashKey'}) > 30) 
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = "Trop long";
		}
		else
		{
			$this->updateUserConfig( array("hashkey" => $this->param{'hashkey'}) );
			$this->reponse{'code'} = "200";
			$this->reponse{'info'} = "hashkey modifié";
		}
	}
}
else
{
	$this->reponse{'code'} = "400";
	$this->reponse{'info'} = "Not connected";
}

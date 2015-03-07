<?php

$this->setSession();
if($this->userid)
{
	if(isset($this->param{'email'}))
	{
		$this->reponse{'code'} = "200";
	
		if (!preg_match('#^[\w.-]+@[\w.-]+\.[a-z]{2,6}$#i', $this->param{'email'}))
		{
			$this->reponse{'error'} = "Email invalide";
			$this->reponse{'code'} = "500";
		}

		$total = $this->mongo->user->find(array('email' => $this->param{'email'}))->count();
		$total = ($total > 0 ) ? $total : 0;

		if ( $total > 0)
		{
			$this->reponse{'error'} = "Email déjà pris";
			$this->reponse{'code'} = "500";
		}

		if($this->reponse{'code'} == "200") 
		{
			$this->updateUserConfig( array("email" => $this->param{'email'}) );
			$this->reponse{'body'} = "Email modifié";
		}
	}
	if(isset($this->param{'password'}))
	{
		$this->reponse{'code'} = "200";

		if(strlen($this->param{'password'}) < 4) 
		{
			$this->reponse{'error'} = "Password trop court";
			$this->reponse{'code'} = "500";
		}
		else
		{
			$obj = $this->mongo->user->findOne( array('UserID' => $this->id) );
			if(sha1($obj{'checksum'}.$this->param{'oldPassword'}) != $obj{'password'})
			{
				$this->reponse{'error'} = "Ancien password incorrect";
				$this->reponse{'code'} = "500";
			}

		}
		if($this->reponse{'code'} == "200") 
		{
			$res = $this->mongo->user->findAndModify(
				array("UserID" => $this->id ),
				array('$set' => array("password" => sha1($obj{'checksum'}.$this->param{'password'}) ) )
			);
			$this->reponse{'body'} = "password modifié";
		}
	}
	if(isset($this->param{'FromName'}))
	{
		if(strlen($this->param{'FromName'}) > 50) 
		{
			$this->reponse{'error'} = "Trop long";
			$this->reponse{'code'} = "500";
		}
		else
		{
			$this->updateUserConfig( array("FromName" => $this->param{'FromName'}) );
			$this->reponse{'code'} = "200";
			$this->reponse{'body'} = "FromName modifié";
		}
	}
	if(isset($this->param{'FromMail'}))
	{
		if(strlen($this->param{'FromMail'}) > 50) 
		{
			$this->reponse{'error'} = "Trop long";
			$this->reponse{'code'} = "500";
		}
		else
		{
			$this->updateUserConfig( array("FromMail" => $this->param{'FromMail'}) );
			$this->reponse{'code'} = "200";
			$this->reponse{'body'} = "FromMail modifié";
		}
	}
	if(isset($this->param{'ReplyTo'}))
	{
		if(strlen($this->param{'ReplyTo'}) > 50) 
		{
			$this->reponse{'error'} = "Trop long";
			$this->reponse{'code'} = "500";
		}
		else
		{
			$this->updateUserConfig( array("ReplyTo" => $this->param{'ReplyTo'}) );
			$this->reponse{'code'} = "200";
			$this->reponse{'body'} = "ReplyTo modifié";
		}
	}
}
else
{
	$this->reponse{'code'} = "500";
	$this->reponse{'body'} = "Not connected";
}

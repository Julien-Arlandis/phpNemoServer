<?php

// Supprime un utilisateur
function deleteUser($id)
{
	global $jntp;
	return $jntp->mongo->user->remove(array('UserID' => $id));
}

if($this->privilege == 'admin')
{
	$this->reponse{'code'} = "200";
	deleteUser($this->param{'UserID'});
	$this->reponse{'info'} = "User ".$this->param{'UserID'}." deleted";
}
else
{
	$this->reponse{'code'} = "400";
	$this->reponse{'info'} = "Not autorised to delete";
}

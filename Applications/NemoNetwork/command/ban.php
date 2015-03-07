<?php

// Supprime un utilisateur
function deleteUser($id)
{
	global $jntp;
	return $jntp->mongo->user->remove(array('UserID' => $id));
}

$this->setSession();

if($this->privilege == 'admin')
{
	$this->reponse{'code'} = "200";
	deleteUser($this->param{'UserID'});
	$this->reponse{'body'} = array("User ".$this->param{'UserID'}." deleted");
}
else
{
	$this->reponse{'code'} = "500";
	$this->reponse{'body'} = array("Not autorised to delete");
}

<?php

// Supprime un utilisateur
function deleteUser($id)
{
	global $jntp;
	return $jntp->mongo->user->remove(array('UserID' => $id));
}

if($jntp->privilege == 'admin')
{
	$jntp->reponse{'code'} = "200";
	deleteUser($jntp->param{'UserID'});
	$jntp->reponse{'info'} = "User ".$jntp->param{'UserID'}." deleted";
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'info'} = "Not autorised to delete";
}

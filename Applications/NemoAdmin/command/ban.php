<?php

// Supprime un utilisateur
function deleteUser($id)
{
	return JNTP::$mongo->user->remove(array('UserID' => $id));
}

if(JNTP::$privilege == 'admin')
{
	JNTP::$reponse{'code'} = "200";
	deleteUser(JNTP::$param{'UserID'});
	JNTP::$reponse{'info'} = "User ".JNTP::$param{'UserID'}." deleted";
}
else
{
	JNTP::$reponse{'code'} = "400";
	JNTP::$reponse{'info'} = "Not autorised to delete";
}

<?php
class NemoNetwork
{
	// Met à jour les informations de l' utilisateur
	static function updateUserConfig($arr)
	{
		JNTP::$mongo->user->update(
		    array("UserID" => JNTP::$id),
		    array('$set' => $arr)
		);
	}
}

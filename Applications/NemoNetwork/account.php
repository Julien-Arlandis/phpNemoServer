<?php
require_once(__DIR__."/../core/lib/class.jntp.php");

$action = isset($_GET['action']) ? $_GET['action'] : '';
$check = isset($_GET['check']) ? $_GET['check'] : '';
$userid = isset($_GET['userid']) ? $_GET['userid'] : '';

if($action == "inscription")
{
	$jntp = new JNTP();
	$obj = $jntp->mongo->user->findOne(array('UserID' => intval($userid) ));

	if($obj)
	{
		if($check == $obj{'check'})
		{
			$jntp->mongo->user->update(
				array("UserID" => intval($userid)),
				array('$unset' => array("check"=>true))
			);
			$txt = "Votre compte ".$obj{'email'}." a bien été activé.";
		}
		else
		{
			$txt = "Votre compte a déjà été activé.";
		}
	}
	else
	{
		$txt = "Ce compte n'existe pas.";
	}
	
	echo JNTP::getTpl(__DIR__."/tpl/valid_inscription.tpl",
			array(
				"txt" => $txt
				)
	);
}
elseif($action == "unsubscribe")
{
	$valid = isset($_POST['valid']);
	if($valid) {
		$jntp = new JNTP();
		$obj = $jntp->mongo->user->findOne(array('UserID' => intval($userid) ));

		if($obj)
		{
			if($check == $obj{'checkunsubcribe'})
			{
				$jntp->mongo->user->remove(array('UserID' => intval($userid) ));
				$txt = "Votre compte ".$obj{'email'}." a bien été supprimé.";
				$redirection = true;
			}
			else
			{
				$txt = "Le checksum ne correspond pas";
			}
		}
		else
		{
			$txt = "Ce compte n'existe pas.";
		}
	}
	
	echo JNTP::getTpl(__DIR__."/tpl/valid_unsubscribe.tpl",
		array(
				"txt" => $txt
				)
	);
	
}
elseif( $action == "changepassword" )
{
	$valid = isset($_POST['valid']);
	if($valid) {
		$jntp = new JNTP();
		$obj = $jntp->mongo->user->findOne(array('UserID' => intval($userid) ));

		if($obj)
		{
			if($check == $obj{'checkpassword'})
			{
				$password1 = isset($_POST['password1']) ? $_POST['password1'] : '';
				$password2 = isset($_POST['password2']) ? $_POST['password2'] : '';
				if( $password1 != $password2 )
				{
					$txt = "Les mots de passe ne correspondent pas.";
				}
				elseif(strlen($password1) < 5)
				{
					$txt = "Le mot de passe doit faire au minimum 5 caractères.";
				}
				else
				{
					$checksum = sha1(uniqid());
					$password_crypt = sha1($checksum.$password1);

					$jntp->mongo->user->update(
						array("UserID" => intval($userid)),
						array('$set'=>array('password'=>$password_crypt, 'checksum'=>$checksum), '$unset'=>array('checkpassword'=>true) )
					);
					$redirection = true;
					$txt = "Le mot de passe du compte ".$obj{'email'}." a bien été mis à jour.";
				}
			}
			else
			{
				$txt = "Le checksum ne correspond pas";
			}
		}
		else
		{
			$txt = "Ce compte n'existe pas.";
		}
	}
	
	echo JNTP::getTpl(__DIR__."/tpl/confirm_changePassword.tpl",
	array(
				"txt" => $txt
			)
	);
	
}
else
{
	$txt = "URL incomplète";
}

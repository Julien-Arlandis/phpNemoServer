<?php
require_once("../core/conf/config.php");
require_once("../core/lib/class.jntp.php");

$action = isset($_GET['action']) ? $_GET['action'] : '';
$check = isset($_GET['check']) ? $_GET['check'] : '';
$userid = isset($_GET['userid']) ? $_GET['userid'] : '';
$redirection = false;

if($action == "inscription")
{
	$redirection = true;
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
}
else
{
	$redirection = true;
	$txt = "URL incomplète";
}
?>

<!DOCTYPE HTML>
<html>
<head>
<title>Nemo : Accès aux Newsgroups</title>
<?php
if($redirection)
{
?>
<meta http-equiv="Refresh" content="5; url=/" />
<?php
}
?>
<meta charset="UTF-8">
</head>
<body>
<?php
if($action == "inscription")
{
?>
	<h2>Validation de votre inscription sur Nemo :
	<h3><?=$txt?></h3>
	</h2>
<?php
}
elseif($action == "unsubscribe")
{
	if(!$valid)
	{
?>
		<h2>Confirmer votre désinscription sur Nemo
		<form action="?action=unsubscribe&amp;userid=<?=$userid?>&amp;check=<?=$check?>" method="post">
		<input name="valid" type="submit" value="Confirmer ma désinscription">
		</form>
		</h2>
<?php
	}else{
?>
		<h2>Modification de votre mot de passe Nemo :
		<h3><?=$txt?></h3>
		</h2>
<?php		
	}
}
elseif($action == "changepassword")
{
	if(!$valid)
	{
?>
		<h2>Indiquez le nouveau mot de passe :
		<form action="?action=changepassword&amp;userid=<?=$userid?>&amp;check=<?=$check?>" method="post">
		<input name="password1" type="password">
		<input name="password2" type="password">
		<input name="valid" type="submit" value="Confirmer le nouveau mot de passe">
		</form>
		</h2>
<?php
	}else{
?>
		<h2>Validation de votre désinscription sur Nemo :
		<h3><?=$txt?></h3>
		</h2>
<?php		
	}
}
else{
?>
	<h2><?=$txt?></h2>
<?php
}
if($redirection)
{
?>
<br>
Redirection vers Nemo dans 5 secondes.
<?php
}
?>
</body>
</html>

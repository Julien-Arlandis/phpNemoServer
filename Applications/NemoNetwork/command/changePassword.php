<?php

function mailRecupPassword($email, $userid, $check, $organization, $administrator, $domain)
{
	require_once(__DIR__.'/../../core/lib/class.phpmailer.php');
	$ObjMail = new PHPMailer();
	$url = "http://".$domain."/NemoServer/Applications/NemoNetwork/account.php?action=changepassword&amp;userid=".$userid."&amp;check=".$check;
	$message = "
Bonjour, vous pouvez modifier le mot de passe du compte ".$email." du service ".$organization." en cliquant sur ce lien : <br>
<a href=\"".$url."\">".$url."</a><br>
ou en le recopiant dans votre barre d'adresse.";

	$ObjMail->MsgHTML($message);
	$ObjMail->setFrom($administrator);
	$ObjMail->FromName   = $organization;
	$ObjMail->Subject    = $organization." : récupération du mot de passe";
	$ObjMail->AddAddress($email);
	$ObjMail->CharSet = "UTF-8";
	if(!$ObjMail->Send())
	{
		return array("code" =>"400", "info" => "L'email n'a pas pu être envoyé\n" );
	}
	return true;
}

if(isset($jntp->param{'email'}))
{
	$total = $jntp->mongo->user->find(array('email' => $jntp->param{'email'}))->count();

	if ( $total == 0)
	{
		$jntp->reponse{'code'} = "400";
		$jntp->reponse{'info'} = "Cet email n'est pas enregistré dans la base";
	}
	else
	{
		$check = (string)rand(1e15, 9e15);
		$obj = $jntp->mongo->user->findOne( array('email' => $jntp->param{'email'}) );
		$jntp->mongo->user->update(array('UserID' => $obj['UserID']), array('$set' => array('checkpassword' => $check) ));
		mailRecupPassword($obj['email'], $obj['UserID'], $check, $jntp->config{'organization'}, $jntp->config{'administrator'}, $jntp->config{'domain'});
		$jntp->reponse{'code'} = "200";
		$jntp->reponse{'info'} = "Un courriel a été envoyé à l'adresse ".$obj['email'];
	}
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'info'} = "Not connected";
}

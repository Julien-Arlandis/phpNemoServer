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
		return array("code" =>"500", "error" => "L'email n'a pas pu être envoyé\n" );
	}
	return true;
}

if(isset($this->param{'email'}))
{
	$total = $this->mongo->user->find(array('email' => $this->param{'email'}))->count();

	if ( $total == 0)
	{
		$this->reponse{'code'} = "500";
		$this->reponse{'body'} = "Cet email n'est pas enregistré dans la base";
	}
	else
	{
		$check = (string)rand(1e15, 9e15);
		$obj = $this->mongo->user->findOne( array('email' => $this->param{'email'}) );
		$this->mongo->user->update(array('UserID' => $obj['UserID']), array('$set' => array('checkpassword' => $check) ));
		mailRecupPassword($obj['email'], $obj['UserID'], $check, $this->config{'organization'}, $this->config{'administrator'}, $this->config{'domain'});
		$this->reponse{'code'} = "200";
		$this->reponse{'body'} = "Un courriel a été envoyé à l'adresse ".$obj['email'];
	}
}
else
{
	$this->reponse{'code'} = "500";
	$this->reponse{'body'} = "Not connected";
}

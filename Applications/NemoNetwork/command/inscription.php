<?php

// Insère un nouvel utilisateur
function insertUser($email, $password, $privilege = 1)
{
	global $jntp;
	$error = "";
	$code = "200";
	$userid = "";
	$check = "";
	if(strlen($password) < 4) 
	{
		$error .= "Password trop court\n";
		$code = "500";
	}
	if (!preg_match('#^[\w.-]+@[\w.-]+\.[a-z]{2,6}$#i', $email))
	{
		$error .= "Email invalide\n";
		$code = "500";
	}

	$total = $jntp->mongo->user->find(array('email' => $email))->count();
	$total = ($total > 0 ) ? $total : 0;

	if ( $total > 0)
	{
		$error .= "Email déjà pris\n";
		$code = "500";
	}
	if($code == 200) 
	{
		$check = (string)rand(100000000000, 99999999999999);
		$hashkey = sha1(rand(0, 9e16).uniqid());
		$checksum = sha1(uniqid());
		$password_crypt = sha1($checksum.$password);
		$date = date("Y-m-d").'T'.date("H:i:s").'Z';

		$res = $jntp->mongo->counters->findAndModify(
			array("_id"=>"UserID"),
			array('$inc'=>array("seq"=>1)),
			null,
			array("new" => true, "upsert"=>true)
		);
		$userid = $res['seq'];
		$user = array('UserID' => $userid, 'email' => $email, 'password' => $password_crypt, 'privilege' => $privilege, 'hashkey' => $hashkey, 'check' => $check, date => $date, 'checksum' => $checksum);

		$jntp->mongo->user->save($user);
	}
	return(array("code" => $code, "error" => $error, "userid" => $userid, "check" => $check));
}

function mailInscription($email, $password, $userid, $check)
{
	global $jntp;
	require_once(__DIR__.'/../../core/lib/class.phpmailer.php');
	$ObjMail = new PHPMailer();

	$url = "http://".$jntp->config{'domain'}."/NemoServer/Applications/NemoNetwork/account.php?action=inscription&amp;userid=".$userid."&amp;check=".$check;
	$message = "
Bonjour, bienvenue sur <a href=\"http://".$jntp->config{'domain'}."\">".$jntp->config{'organization'}."</a>.
<br><br>
Votre inscription a bien été enregistrée avec l'email ".$email.".<br>
Votre mot de passe est : <strong>".$password."</strong>
<br><br>
Merci de valider votre adresse mail en cliquant sur ce lien : <br>
<a href=\"".$url."\">".$url."</a><br>
ou en le recopiant dans votre barre d'adresse.";

	$ObjMail->MsgHTML($message);
	$ObjMail->setFrom($jntp->config{'administrator'});
	$ObjMail->FromName   = $jntp->config{'organization'};
	$ObjMail->Subject    = "Bienvenue sur ".$jntp->config{'organization'};
	$ObjMail->AddAddress($email);
	$ObjMail->CharSet = "UTF-8";
	if(!$ObjMail->Send())
	{
		return array("code" =>"500", "error" => "L'email n'a pas pu être envoyé\n" );
	}
	return true;
}

if($this->config{'activeInscription'} || $this->privilege == 'admin')
{
	$res = insertUser($this->param{'email'}, $this->param{'password'});
	if($res['code'] == 200)
	{
		mailInscription($this->param{'email'}, $this->param{'password'}, $res['userid'], $res['check']);
	}
	$this->reponse{'code'} = $res['code'];
	$this->reponse{'body'} = array($res['error']);
}
else
{
	$this->reponse{'code'} = "500";
	$this->reponse{'body'} = "L'inscription en ligne est désactivée sur ce serveur, veuillez adresser un mail à ".NEWSMASTER_MAIL." en spécifiant le mot de passe souhaité, votre compte sera ouvert dans les plus brefs délais.";
}

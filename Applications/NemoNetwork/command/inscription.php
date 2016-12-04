<?php

// Insère un nouvel utilisateur
function insertUser($email, $password)
{
	global $jntp;
	$error = array();
	$code = "200";
	$userid = "";
	$check = "";
	if(strlen($password) < 4)
	{
		array_push($error, "Password trop court");
		$code = "400";
	}
	if (!preg_match('#^[\w.-]+@[\w.-]+\.[a-z]{2,6}$#i', $email))
	{
		array_push($error, "Email invalide");
		$code = "400";
	}

	$isUserInDataBase = $jntp->mongo->user->find(array('email' => strtolower($email)))->count();

	if ( $isUserInDataBase )
	{
			array_push($error, "Email déjà pris");
			$code = "400";
	}
	else
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
		$user = array('UserID' => $userid, 'email' => $email, 'password' => $password_crypt, 'privilege' => 1, 'hashkey' => $hashkey, 'check' => $check, date => $date, 'checksum' => $checksum);

		$jntp->mongo->user->save($user);
	}
	return(array("code" => $code, "info" => $error, "userid" => $userid, "check" => $check));
}

if($jntp->config{'activeInscription'} || $jntp->privilege == 'admin')
{
	$res = insertUser($jntp->param{'email'}, $jntp->param{'password'});
	if($res['code'] == "200")
	{
		require_once(__DIR__.'/../../core/lib/class.phpmailer.php');
		require_once(__DIR__.'/../../core/lib/class.smtp.php');

		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->Host = $jntp->config{'smtpHost'};
		if($mail->SMTPAuth = $jntp->config{'smtpAuth'})
		{
			$mail->Username = $jntp->config{'smtpLogin'};
			$mail->Password = $jntp->config{'smtpPassword'};
		}
		$mail->SMTPSecure = $jntp->config{'smtpSecure'};
		$mail->Port = $jntp->config{'smtpPort'};
		$mail->setFrom( $jntp->config{'administrator'}, $jntp->config{'organization'} );
		$mail->AddAddress( $jntp->param{'email'} );
		$mail->Subject = "Bienvenue sur ".$jntp->config{'organization'};
		$mail->isHTML( true );
		$mail->CharSet = "UTF-8";
		$mail->Body = JNTP::getTpl(__DIR__."/../tpl/mail_inscription.tpl",
				array(
					"domain" => $jntp->config{'domain'},
					"organization" => $jntp->config{'organization'},
					"email" => $jntp->param{'email'},
					"password" => $jntp->param{'password'},
					"url" => "http://".$jntp->config{'domain'}."/jntp/Applications/NemoNetwork/account.php?action=inscription&amp;userid=".$res['userid']."&amp;check=".$res['check']
				     )
		);
		if(!$mail->Send())
		{
			array_push( $res['info'], "L'email n'a pas pu être envoyé" );
		}
	}
	$jntp->reponse{'code'} = $res['code'];
	$jntp->reponse{'info'} = $res['info'];
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'info'} = "L'inscription en ligne est désactivée sur ce serveur, veuillez adresser un mail à ".$jntp->config{'administrator'}." en spécifiant le mot de passe souhaité, votre compte sera ouvert dans les plus brefs délais.";
}

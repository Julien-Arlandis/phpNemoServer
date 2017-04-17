<?php

// Insère un nouvel utilisateur
function insertUser($email, $password)
{
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

	$isUserInDataBase = JNTP::$mongo->user->find(array('email' => strtolower($email)))->count();

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

		$res = JNTP::$mongo->counters->findAndModify(
			array("_id"=>"UserID"),
			array('$inc'=>array("seq"=>1)),
			null,
			array("new" => true, "upsert"=>true)
		);
		$userid = $res['seq'];
		$user = array('UserID' => $userid, 'email' => $email, 'password' => $password_crypt, 'privilege' => 1, 'hashkey' => $hashkey, 'check' => $check, date => $date, 'checksum' => $checksum);

		JNTP::$mongo->user->save($user);
	}
	return(array("code" => $code, "info" => $error, "userid" => $userid, "check" => $check));
}

if(JNTP::$config{'activeInscription'} || JNTP::$privilege == 'admin')
{
	$res = insertUser(JNTP::$param{'email'}, JNTP::$param{'password'});
	if($res['code'] == "200")
	{
		require_once(__DIR__.'/../lib/class.phpmailer.php');
		require_once(__DIR__.'/../lib/class.smtp.php');

		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->Host = JNTP::$config{'smtpHost'};
		if($mail->SMTPAuth = JNTP::$config{'smtpAuth'})
		{
			$mail->Username = JNTP::$config{'smtpLogin'};
			$mail->Password = JNTP::$config{'smtpPassword'};
		}
		$mail->SMTPSecure = JNTP::$config{'smtpSecure'};
		$mail->Port = JNTP::$config{'smtpPort'};
		$mail->setFrom( JNTP::$config{'administrator'}, JNTP::$config{'organization'} );
		$mail->AddAddress( JNTP::$param{'email'} );
		$mail->Subject = "Bienvenue sur ".JNTP::$config{'organization'};
		$mail->isHTML( true );
		$mail->CharSet = "UTF-8";
		$mail->Body = JNTP::getTpl(__DIR__."/../tpl/mail_inscription.tpl",
				array(
					"domain" => JNTP::$config{'domain'},
					"organization" => JNTP::$config{'organization'},
					"email" => JNTP::$param{'email'},
					"password" => JNTP::$param{'password'},
					"url" => "http://".JNTP::$config{'domain'}."/jntp/Applications/NemoNetwork/account.php?action=inscription&amp;userid=".$res['userid']."&amp;check=".$res['check']
				     )
		);
		if(!$mail->Send())
		{
			array_push( $res['info'], "L'email n'a pas pu être envoyé" );
		}
	}
	JNTP::$reponse{'code'} = $res['code'];
	JNTP::$reponse{'info'} = $res['info'];
}
else
{
	JNTP::$reponse{'code'} = "400";
	JNTP::$reponse{'info'} = "L'inscription en ligne est désactivée sur ce serveur, veuillez adresser un mail à ".JNTP::$config{'administrator'}." en spécifiant le mot de passe souhaité, votre compte sera ouvert dans les plus brefs délais.";
}

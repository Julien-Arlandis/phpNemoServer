<?php

if( isset( $jntp->param{'email'} ) )
{
	$isRegister = $jntp->mongo->user->find(array('email' => $jntp->param{'email'}))->count();

	if ( $isRegister )
	{
		require_once(__DIR__.'/../../core/lib/class.phpmailer.php');
		require_once(__DIR__.'/../../core/lib/class.smtp.php');
		$check = (string)rand(1e15, 9e15);
		$obj = $jntp->mongo->user->findOne( array('email' => $jntp->param{'email'}) );
		$jntp->mongo->user->update(array('UserID' => $obj['UserID']), array('$set' => array('checkpassword' => $check) ));
		
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
		$mail->AddAddress( $obj['email'] );
		$mail->Subject = $jntp->config{'organization'} . " : récupération du mot de passe";
		$mail->isHTML( true );
		$mail->CharSet = "UTF-8";
		$mail->Body = JNTP::getTpl(__DIR__."/../tpl/mail_changePassword.tpl",
				array(
					"organization" => $jntp->config{'organization'},
					"email" => $obj['email'],
					"url" => "http://".$jntp->config{'domain'}."/NemoServer/Applications/NemoNetwork/account.php?action=changepassword&amp;userid=".$obj['UserID']."&amp;check=".$check
				     )
		);
		if(!$mail->Send())
		{
			$jntp->reponse{'code'} = "200";
			$jntp->reponse{'info'} = "L'email n'a pas pu être envoyé à l'adresse ".$obj['email'];
		}
		else
		{
			$jntp->reponse{'code'} = "200";
			$jntp->reponse{'info'} = "Un courriel a été envoyé à l'adresse ".$obj['email'];
		}
	}
	else
	{
		$jntp->reponse{'code'} = "400";
		$jntp->reponse{'info'} = "Cet email n'est pas enregistré dans la base";
	}
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'info'} = "Not connected";
}

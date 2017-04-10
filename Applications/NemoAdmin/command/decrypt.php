<?php

if(JNTP::$privilege == 'admin' || JNTP::$privilege == 'moderator')
{
	JNTP::$reponse{'code'} = "200";
	$packet = JNTP::getPacket( array('Jid'=>JNTP::$param{'Jid'}) );
	$data = $packet{'Data'}{'Media'}[0]{'data'};
	$keyCrypt = $packet{'Data'}{'Media'}[0]{'KeyAES256'};
	if (!$privateKey = openssl_get_privatekey(JNTP::$config{'privateKey'})) die('Loading Private Key failed');
	openssl_private_decrypt(base64_decode($keyCrypt), $key_iv, $privateKey);

	$packet = JNTP::decryptAES256($data, $key_iv);
	JNTP::$reponse{'body'} = array(json_decode($packet));
	JNTP::$reponse{'info'} = "Send article decrypted";
}
else
{
	JNTP::$reponse{'code'} = "400";
	JNTP::$reponse{'info'} = "Not autorised to decrypt this article";
}

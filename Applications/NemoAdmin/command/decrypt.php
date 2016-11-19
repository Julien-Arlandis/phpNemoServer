<?php

if($jntp->privilege == 'admin' || $jntp->privilege == 'moderator')
{
	$jntp->reponse{'code'} = "200";
	$packet = $jntp->getPacket( array('Jid'=>$jntp->param{'Jid'}) );
	$data = $packet{'Data'}{'Media'}[0]{'data'};
	$keyCrypt = $packet{'Data'}{'Media'}[0]{'KeyAES256'};
	if (!$privateKey = openssl_get_privatekey(PRIVATE_KEY)) die('Loading Private Key failed');
	openssl_private_decrypt(base64_decode($keyCrypt), $key_iv, $privateKey);

	$packet = $jntp->decryptAES256($data, $key_iv);
	$jntp->reponse{'body'} = array(json_decode($packet));
	$jntp->reponse{'info'} = "Send article decrypted";
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'info'} = "Not autorised to decrypt this article";
}

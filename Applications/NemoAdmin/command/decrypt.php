<?php

if($this->privilege == 'admin' || $this->privilege == 'moderator')
{
	$this->reponse{'code'} = "200";
	$packet = $this->getPacket( array('Jid'=>$this->param{'Jid'}) );
	$data = $packet{'Data'}{'Media'}[0]{'data'};
	$keyCrypt = $packet{'Data'}{'Media'}[0]{'KeyAES256'};
	if (!$privateKey = openssl_get_privatekey(PRIVATE_KEY)) die('Loading Private Key failed');
	openssl_private_decrypt(base64_decode($keyCrypt), $key_iv, $privateKey);

	$packet = $this->decryptAES256($data, $key_iv);
	$this->reponse{'body'} = array(json_decode($packet));
	$this->reponse{'info'} = "Send article decrypted";
}
else
{
	$this->reponse{'code'} = "400";
	$this->reponse{'info'} = "Not autorised to decrypt this article";
}

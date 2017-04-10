<?php

if(JNTP::$privilege == 'admin')
{
	JNTP::createIndex();
	JNTP::$reponse{'code'} = "200";
	JNTP::$reponse{'info'} = "Reload index, done";
}
else
{
	JNTP::$reponse{'code'} = "400";
	JNTP::$reponse{'info'} = "Not autorised to reload index";
}

<?php

if($jntp->privilege == 'admin')
{
	$jntp->createIndex();
	$jntp->reponse{'code'} = "200";
	$jntp->reponse{'info'} = "Reload index, done";
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'info'} = "Not autorised to reload index";
}

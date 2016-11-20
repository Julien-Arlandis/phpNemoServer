<!DOCTYPE HTML>
<html>
<head>
<title>Nemo : Accès aux Newsgroups</title>


%redirection%

<meta charset="UTF-8">
</head>
<body>
<?php
if($action == "inscription")
{
?>
	<h2>Validation de votre inscription sur Nemo :
	<h3>%txt%</h3>
	</h2>
<?php
}
elseif($action == "unsubscribe")
{
	if(!$valid)
	{
?>
		<h2>Confirmer votre désinscription sur Nemo
		<form action="?action=unsubscribe&amp;userid=<?=$userid?>&amp;check=<?=$check?>" method="post">
		<input name="valid" type="submit" value="Confirmer ma désinscription">
		</form>
		</h2>
<?php
	}else{
?>
		<h2>Modification de votre mot de passe Nemo :
		<h3>%txt%</h3>
		</h2>
<?php		
	}
}
elseif($action == "changepassword")
{
	if(!$valid)
	{
?>
		<h2>Indiquez le nouveau mot de passe :
		<form action="?action=changepassword&amp;userid=<?=$userid?>&amp;check=<?=$check?>" method="post">
		<input name="password1" type="password">
		<input name="password2" type="password">
		<input name="valid" type="submit" value="Confirmer le nouveau mot de passe">
		</form>
		</h2>
<?php
	}else{
?>
		<h2>Validation de votre désinscription sur Nemo :
		<h3>%txt%</h3>
		</h2>
<?php		
	}
}
else{
?>
	<h2>%txt%</h2>

<br>
%redirection_message%

</body>
</html>

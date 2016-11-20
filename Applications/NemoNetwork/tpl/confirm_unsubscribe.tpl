<!DOCTYPE HTML>
<html>
<head>
<title>Nemo : Accès aux Newsgroups</title>

%redirection%

<meta charset="UTF-8">
</head>
<body>


		<h2>Confirmer votre désinscription sur Nemo
		<form action="?action=unsubscribe&amp;userid=<?=$userid?>&amp;check=<?=$check?>" method="post">
		<input name="valid" type="submit" value="Confirmer ma désinscription">
		</form>
		</h2>

	<h2>%txt%</h2>
<br>
%redirection_message%
</body>
</html>

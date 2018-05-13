<?php
	header('Content-Type: application/json'); // Entête pour le type de contenu (JSON)
	
	$depechesJSON = file_get_contents('depeches.json');

	print($depechesJSON); // On affiche sous format jason notre tableau
	
	http_response_code(200); // On prévient que tout s'est bien passé et que c'est trop la fête
?>

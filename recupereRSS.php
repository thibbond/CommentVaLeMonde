<?php
	// Les commentaire commançant par "!!" sont a enlevés pour un affichage des données json sur le navigateur.
	// Il faut renommer les adresse (de façon entière) en fonction de l'utilisteur.
	
	//!! header('Content-Type: application/json'); // Entête pour préciser que nous allons afficher du contenu au format JSON.

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////// définition de recupereDepeches() //////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function recupereDepeches(){
	/* Cette fonction ne prend aucun paramètre en entré et retourne dans un tableau les titres, dates, liens et descriptions des dépêches récupérées via le flux RSS du site belge 'http://www.dhnet.be'. Plus l'indice du tableau est petit et plus la dépêche est récente. */
		echo("Récupération des dépêches du site DHNET :::\n");
		
		$rss = simplexml_load_file('http://www.dhnet.be/rss/infos.xml');
		$i = 0;
		$tableau = array();
		foreach ($rss->channel->item as $item){
			$datetime = date_create($item->pubDate);
			$date = date_format($datetime, 'Y-m-d H\hi');
			$tableau[$i]['date'] = $date;
			$titre = $item->title;
			$tableau[$i]['titre'] = strval($titre[0]);
			$tableau[$i]['mots_du_titre'] = decomposeMots($tableau[$i]['titre']);
			$tableau[$i]['lien'] = strval($item->link);
			$tableau[$i]['description'] = strval($item->description);
			$i++;
		}
		
		echo("Récupération terminée.\n");
		
		return $tableau;
	}

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////// définition de recuperePositivite(.) ///////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function recuperePositivite($liste_de_mots){
		$lignes_retournees = array();
		
		$commande_bash = "./calcule_polarite.py";
		$taille_liste = count($liste_de_mots);
		for($j = 0; $j < $taille_liste; $j++){
			$commande_bash .= " "  . $liste_de_mots[$j];
		}
		
		exec($commande_bash, $lignes_retournees);
		
		return intval($lignes_retournees[count($lignes_retournees)-1]);
		
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////// définition de analyseTitre(.) ////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function analyseToponyme($mots){
	/* Cette fonction prend en paramètre une liste de mots. Pour chaque mots commençant par une majuscule, elle récupère ses coordonnées géographiques. */
		
		echo("Analyse des mots du titre ::\n");
		
		$expression="#^[A-Z]|Â|Ê|Î|Ô|Û|Ä|Ë|Ï|Ö|Ü|À|Æ|Ç|É|È|Œ|Ù#";
		$nb_de_mots = count($mots);
		
		for($i=0; $i<$nb_de_mots; $i++)
			/* La fonction preg_match(.,.) permet de faire des recherches d'expressions régulières. */
			if(preg_match($expression, $mots[$i])){
				/* Nous ne voulons pas récupérer les coordonnées des articles. */
				if ( ($mots[$i] != "Le") && ($mots[$i] != "La") && ($mots[$i] != "Les") && ($mots[$i] != "Un") && ($mots[$i] != "Une") && ($mots[$i] != "Des") && ($mots[$i] != "L") && ($mots[$i] != "Et") && ($mots[$i] != "De") && ($mots[$i] != "En") ){
					$coordonnees[$mots[$i]] = recupereCoordonnees($mots[$i]);
				}
			}
		
		echo("Fin de l'analyse des mots du titre.\n");
		return $coordonnees;
	
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////// définition de recupereCoordonnees(.) ///////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function recupereCoordonnees($adresse){
	/* Cette fonction retourne les coordonnées géographique de l'adresse entrée en paramètre. */	
		
		print("Récupération des coordonnées géographiques pour \"$adresse\" :::\n");
		
		$cleAPIGoogleMaps = 'AIzaSyCSKYf8twyuQfJYZYIgE8qHxcrOhZJsuDo';
		$cleZak = 'AIzaSyBnLHV0Uq6qBmHs-O0ThzpzB1Ma7CZIdvs';
		$autreCle = 'AIzaSyA9DXz98lN5tdgO8vHkqy7jfrQypfdr_zU';

		/* file_get_contents(.) nous permet ici de récupérer au format chaîne de caractères le contenu affiché de la page web ci-dessous. Dans notre cas, le contenu est au format JSON.*/
		$reponseJSON = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=$adresse&key=$cleAPIGoogleMaps"); 
		
		/* Nous convertissons la chaîne de caractère en tableau. */
		$reponseTab = json_decode($reponseJSON, true);
		
		$coordonnees = array();
		$coordonnees['status'] = false;
		
		if ($reponseTab == false){
			echo ("Erreur lors de la récupération des coordonnées.\n");
			$coordonnees['erreur_recuperation'] = true;
		}else{
			echo("Statut de la réponse de GoogleMaps : " . $reponseTab['status'] . ".\n");
			if ($reponseTab['status'] == "OK"){
				for($i = 0; $i < count($reponseTab['results']); $i++){
					for($j=0; $j < count($reponseTab['results'][$i]['types']); $j++){
						if($reponseTab['results'][$i]['types'][$j] == "political"){
							$coordonnees['status'] = true;
							$coordonnees['latitude'] = $reponseTab['results'][$i]['geometry']['location']['lat'];
							$coordonnees['longitude'] = $reponseTab['results'][$i]['geometry']['location']['lng'];
							$coordonnees['types'] = $reponseTab['results'][$i]['types'];
							
							echo("Récupération réussie.\n");
						}else{
							
							echo("Le type de coordonnées ne nous intéresse pas.\n"); 
						}
					}
				}		
			}
		}
		
		echo("Fin de la récupération géographique.\n");
		return $coordonnees;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////// définition de decomposeMots(.) ////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function decomposeMots($phrase){
	/* Cette fonction retourne la liste des mots contenus dans la phrase entrée en paramètre. */
		
		echo("Récupération des mots de la phrase \"$phrase\" :::\n");
		
		$phrase = str_replace(":", " ", $phrase); // La fonction str_replace($search, $replace, $subject) remplace toutes les occurrences du mot $search dans la chaine de caractères $subject par le mot $replace.
		$phrase = str_replace("_", " ", $phrase);
		$phrase = str_replace('"', " ", $phrase);
		$phrase = str_replace("'", " ", $phrase);
		$phrase = str_replace("!", " ", $phrase);	
		$phrase = str_replace("?", " ", $phrase);
		$phrase = str_replace(".", " ", $phrase);
		$phrase = str_replace("'", " ", $phrase);
		$phrase = str_replace(",", " ", $phrase);
		// $titre = str_replace("-", " ", $titre);
		$phrase = str_replace("(", " ", $phrase);
		$phrase = str_replace(")", " ", $phrase);
		
		$liste_des_mots = explode(' ', $phrase);
		
		// nous supprimons les mots vides.
		for($i = 0; $i < count($liste_des_mots); $i++){
			if ($liste_des_mots[$i] == ""){
				unset($liste_des_mots[$i]);
			}
		}
		
		echo("Récupération des mots terminée.\n");
		return array_values($liste_des_mots);;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////// définition de convertionDate(.) ///////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function convertionDate($date){
	// Cette fonction prend une chaine de caractère correspondant à une date au format (Y-m-d H\hi') et retourne un nombre entier au format YmdHi. Ce nombre permettra la comparaison dans le temps entre deux dates.
	
		// Si $date = '2018-02-10 08h00'.
		$datetab = explode(' ', $date); // Alors $datetab[0] = '2018-02-10' et $datetab[1] = '08h00'.
		$tab0 = explode('-', $datetab[0]); // tab0[0] = 2018, tab0[1] = 02, tab0[2] = 10.
		$tab1 = explode('h', $datetab[1]); // tab1[0] = 08, tab1[1] = 00.
		return ($tab0[0] . $tab0[1] . $tab0[2] . $tab1[0] . $tab1[1]); // La valeur retournée est '201802100800'.
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////// Comparaison et Ajout des dépêches à notre liste de dépêches ///////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	echo("Récupération du contenu du fichier \"depeches.json\".\n");
	$depechesJSON = file_get_contents('depeches.json'); // Récupère le contenu du fichier .json sous forme d'une chaine de caractères
	$nos_depeches = json_decode($depechesJSON, true); // Converti cette chaine de caractère en une liste ($liste = false si le fichier .json est mal écrit
	$premiere_date_de_nos_depeches = convertionDate($nos_depeches[0]['date']);
	
	$depeches_du_site = recupereDepeches();

	
	if ($nos_depeches != false){ // Si le fichier .json est bien écrit.
		$i = count($depeches_du_site); // Pour l'instant cette valeur vaut toujours 20.
		// test - echo("Le fichier .json est bien écrit.\n");
		
		/* Nous allons parcourrir en sens inverse le tableau $depeches_du_site[], c-à-d, de sa dépêche la plus ancienne à sa dépêche la plus récente. Nous arrêtons le parcours dès que nous trouvons une dépêche postérieure à la première de nos dépêches enregistrées. */ 
		do{
			$i--;
			$date_a_comparer = convertionDate($depeches_du_site[$i]['date']);
		}while ( ($i > 0) && ($date_a_comparer <= $premiere_date_de_nos_depeches) );
		// test :: echo($i\n);
		
		if($i > 0){ // Si $i est strictement positif, cela veut dire que la date de la dépêche n° $i de $depeches_du_site est postérieur à la première date de nos dépêches enregistrées. Nous pouvons donc ajouter toutes les autres dépêches du site (de 0 à $i) à nos dépêches. 
			for($j= $i; $j > -1; $j--){
			 	array_unshift($nos_depeches, $depeches_du_site[$j]);
			 	$nos_depeches[0]['coordonnees'] = analyseToponyme($nos_depeches[0]['mots_du_titre']);
			 	$nos_depeches[0]['coordonnees'] = array_merge($nos_depeches[0]['coordonnees'], analyseToponyme(decomposeMots($nos_depeches[0]['description'])));
			 	$nos_depeches[0]['positivite'] = recuperePositivite($nos_depeches[0]["mots_du_titre"]);
			}
		}else{
			if($i == 0){ // Si $i est nulle, alors il faut comparer les dates, et en fonction, ajouter ou non $depeche_du_site[0] à nos dépêches. 
				if($date_a_comparer > $premiere_date_de_nos_depeches){
					array_unshift($nos_depeches, $depeches_du_site[0]);
					$nos_depeches[0]['coordonnees'] = analyseToponyme($nos_depeches[0]['mots_du_titre']);
					$nos_depeches[0]['coordonnees'] = array_merge($nos_depeches[0]['coordonnees'], analyseToponyme(decomposeMots($nos_depeches[0]['description'])));
					$nos_depeches[0]['positivite'] = recuperePositivite($nos_depeches[0]["mots_du_titre"]);
				}
			}
		}
	}else{ // Si le fichier .json est mal écrit alors nous récupérons toutes les dépêches du site.
		
		echo("Le fichier \"depeches.json\" est mal écrit.\n");
		$nos_depeches = $depeches_du_site;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////// Stockage et Affichage des dépêches ////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	echo("Ecriture du fichier \"depeches.json\" :::\n");
	
	$depechesJSON = json_encode($nos_depeches, JSON_PRETTY_PRINT); // L'option JSON_PRETTY_PRINT permet d'avoir un affichage LISIBLE POUR L'HUMAIN des données dans le fichier.
	file_put_contents('depeches.json', $depechesJSON); // Enregistre la chaîne de caractères dans le fichier .json
	
	echo("Ecriture terminée.\n");
	//!! echo $depechesJSON; // Nous affichons sous format JSON (chaîne de caractères) notre tableau.

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////// Tout s'est bien passé //////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
	//!! http_response_code(200); // Nous prévenons que tout s'est bien passé et que c'est trop la fête.
	
?>

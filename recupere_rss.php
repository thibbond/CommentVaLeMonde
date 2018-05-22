<?php
	// Il faut renommer les adresse (de façon entière) en fonction de l'utilisteur.

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
	///////////////////////////////////////////////// définition de recupereLemmes(.) ///////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function recupereLemmes($liste_de_mots){
	/* Cette fonction fait appel au script python calcule_lemmes.py avec en argument les mots composant le titre de la dépêche pour retourner une liste de lemmes avec les informations les concernant. */
		
		// Nous préparons la commande bash à exécuter.
		$commande_bash = "/usr/bin/python3 /home/tibo/public_html/CommentVaLeMonde/calcule_lemmes.py";
		$taille_liste = count($liste_de_mots);
		for($j = 0; $j < $taille_liste; $j++){
			$commande_bash .= " "  . $liste_de_mots[$j];
		}
		
		print("Récupération des lemmes du titre, cette opération peut prendre quelques minutes...");
		$bla = exec($commande_bash); // Nous exécutons la commande bash.
		print("Récupération terminée");
		$lemmesJSON = file_get_contents('/home/tibo/public_html/CommentVaLeMonde/lemmes.json'); // Nous récupérons le contenu du fichier lemmes.json édité par l'exécution du fichier python.
		$nos_lemmes = json_decode($lemmesJSON, true); // Nous convertissons le format JSON récupéré en liste.
		
		return $nos_lemmes; // Nous retournons la liste récupérée.
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////// définition de calculePolarite(.) ///////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function calculePolarite($liste_de_lemmes){
	/* Cette fonction calcule la polarité du titre de la dépêche en fonction des lemmes qui le compose.*/
		
		print("Calcul de la polarité du titre ::");
		$polarite = 0;
		$nombre_de_lemmes = 0;
		
		$mots_interdits = array("l", "d", "qu", "L", "D", "Qu", "s", "S", "t", "T", "m", "M", "et", "Et"); // Si un lemme fait parti de cette liste, alors il ne sera pas pris en compte pour le calcul de la polarité.
		
		/* Nous allons parcourir chaque lemme de notre liste, s'il n'est pas un adjectif ou un adverbe, s'il ne fait pas parti de la liste ci-dessus, et si sa polarité n'est pas nulle, alors nous le prenons en compte pour le calcul de la polarité du titre.*/
		foreach ($liste_de_lemmes as $lemme => $val) {
			if ( (in_array($lemme, $mots_interdits) == false) && (($val["nature"] != "ADJ") && ($val["nature"] != "ADV")) ){
				$polarite += $val["polarite"];
				if ($val["polarite"] != 0){
					$nombre_de_lemmes += 1;
				}
			}
		}
		
		/* Nous allons chercher les adjectifs. Si ce dernier ne fait pas parti de la liste des mots interdits, si sa polarité n'est pas nulle, si de plus il précède un nom dont la polarité n'est pas nulle, alors nous multiplions sa polarité avec celle du nom qu'il précède. (La multiplication de deux négatifs doit être négative) */
		foreach ($liste_de_lemmes as $lemme => $val) {
			if ( (in_array($lemme, $mots_interdits) == false) && ($val["nature"] == "ADJ") && ($val["polarite"] != 0) ){
				foreach ($liste_de_lemmes as $lemme_suivant => $val_suivante) {
					if ( ($val_suivante["position"] == $val["position"] + 1) && (( (in_array($lemme_suivant, $mots_interdits) == false) && ($val_suivante["nature"] == "NOM")) && ($val_suivante["polarite"] != 0)) ) {
						if ($val["polarite"] > 1){
							$polarite += $val_suivante["polarite"] * ($val["polarite"] - 1);
							$nombre_de_lemmes += $val["polarite"] - 1;
						}else{
							if($val_suivante["polarite"] < 0){
								$polarite += $val_suivante["polarite"] * (abs($val["polarite"]) - 1);
								$nombre_de_lemmes += abs($val["polarite"]) - 1;
							}else{
								$polarite -= $val_suivante["polarite"] * (abs($val["polarite"]) - 1);
								$nombre_de_lemmes += abs($val["polarite"]) - 1;
							}
						}
					}
				}
			}
		}
		
		/* Nous faisons la même chose pour les adverbes. */
		foreach ($liste_de_lemmes as $lemme => $val) {
			if ( (in_array($lemme, $mots_interdits) == false) && ($val["nature"] == "ADV") && ($val["polarite"] != 0) ){
				foreach ($liste_de_lemmes as $lemme_suivant => $val_suivante) {
					if ( ($val_suivante["position"] == $val["position"] + 1) && (( (in_array($lemme_suivant, $mots_interdits) == false) && ($val_suivante["nature"] == "VER")) && ($val_suivante["polarite"] != 0)) ) {
						if ($val["polarite"] > 1){
							$polarite += $val_suivante["polarite"] * ($val["polarite"] - 1);
							$nombre_de_lemmes += $val["polarite"] - 1;
						}else{
							if($val_suivante["polarite"] < 0){
								$polarite += $val_suivante["polarite"] * (abs($val["polarite"]) - 1);
								$nombre_de_lemmes += abs($val["polarite"]) - 1;
							}else{
								$polarite -= $val_suivante["polarite"] * (abs($val["polarite"]) - 1);
								$nombre_de_lemmes += abs($val["polarite"]) - 1;
							}
						}
					}
				}
			}
		}
				
		print("Calcul terminé");
		return round($polarite/$nombre_de_lemmes); /* Nous retournons la valeur arrondie de la moyenne.*/
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////// définition de analyseTitre(.) ////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function analyseToponyme($mots){
	/* Cette fonction prend en paramètre une liste de mots. Pour chaque mots commençant par une majuscule, elle récupère ses coordonnées géographiques. */
		$coordonnees = array();
		echo("Analyse des mots du titre ::\n");
		
		$expression="#^[A-Z]|Â|Ê|Î|Ô|Û|Ä|Ë|Ï|Ö|Ü|À|Æ|Ç|É|È|Œ|Ù#"; // Nous allons utiliser les expressions régulières, celle-ci signifie que nous recherchons l'un de ces caractères visible en première position du mot analysé.
		$nb_de_mots = count($mots);
		
		for($i=0; $i<$nb_de_mots; $i++)
			/* La fonction preg_match(.,.) permet de faire des recherches d'expressions régulières. */
			if(preg_match($expression, $mots[$i])){
				/* Nous ne voulons pas récupérer les coordonnées des articles. */
				if ( ($mots[$i] != "Le") && ($mots[$i] != "La") && ($mots[$i] != "Les") && ($mots[$i] != "Un") && ($mots[$i] != "Une") && ($mots[$i] != "Des") && ($mots[$i] != "L") && ($mots[$i] != "Et") && ($mots[$i] != "De") && ($mots[$i] != "En") && ($mots[$i] != "C")){
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
		
		// Nous avons pu récupérer différentes clés pour l'utilisation de l'API GoogleMaps.
		$cleAPIGoogleMaps = 'AIzaSyCSKYf8twyuQfJYZYIgE8qHxcrOhZJsuDo';
		$cleZak = 'AIzaSyBnLHV0Uq6qBmHs-O0ThzpzB1Ma7CZIdvs';
		$autreCle = 'AIzaSyA9DXz98lN5tdgO8vHkqy7jfrQypfdr_zU';

		/* file_get_contents(.) nous permet ici de récupérer au format chaîne de caractères le contenu affiché de la page web ci-dessous. Dans notre cas, le contenu est au format JSON.*/
		$reponseJSON = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=$adresse&key=$cleAPIGoogleMaps"); 
		
		/* Nous convertissons la chaîne de caractère en tableau. */
		$reponseTab = json_decode($reponseJSON, true);
		
		$coordonnees = array();
		$coordonnees['status'] = false; // Valeur par défaut (si nous ne trouvons pas de coordonnées).
		
		if ($reponseTab == false){ // Cela signifie qu'il n'y a pas eu de format JSON retourné par la requête.
			echo ("Erreur lors de la récupération des coordonnées.\n");
		}else{
			echo("Statut de la réponse de GoogleMaps : " . $reponseTab['status'] . ".\n");
			if ($reponseTab['status'] == "OK"){
				for($i = 0; $i < count($reponseTab['results']); $i++){ // Nous parcourrons chaque lieu retourné par GoogleMaps.
					for($j=0; $j < count($reponseTab['results'][$i]['types']); $j++){ // Pour chaque lieu, nous analysons ses types.
						if($reponseTab['results'][$i]['types'][$j] == "political"){ // Si "political" fait parti de la liste des types du lieu, alors nous récupérons les coordonnées géographiques associées.
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
		$phrase = str_replace(",", " ", $phrase);
		$phrase = str_replace("(", " ", $phrase);
		$phrase = str_replace(")", " ", $phrase);
		
		$liste_des_mots = explode(' ', $phrase);
		
		// Nous supprimons les mots vides.
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
	///////////////////////////////////// comparaison et ajout des dépêches à notre liste de dépêches ///////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	echo("Récupération du contenu du fichier \"depeches.json\".\n");
	$depechesJSON = file_get_contents('/home/tibo/public_html/CommentVaLeMonde/depeches.json'); // Récupère le contenu du fichier .json sous forme d'une chaine de caractères.
	$nos_depeches = json_decode($depechesJSON, true); // Converti cette chaine de caractère en une liste ($liste = false si le fichier .json est mal écrit).
	$premiere_date_de_nos_depeches = convertionDate($nos_depeches[0]['date']); // Nous utilisons cette fonction pour pouvoir comparer la date de la première dépêche enregistrée dans notre fichier JSON avec les dates des dépêches récupérées.
	
	$depeches_du_site = recupereDepeches();

	
	if ($nos_depeches != false){ // Si le fichier .json est bien écrit.
		$i = count($depeches_du_site); // Cette valeur vaut toujours 20.
		
		/* Nous allons parcourrir en sens inverse le tableau $depeches_du_site[], c-à-d, de sa dépêche la plus ancienne à sa dépêche la plus récente. Nous arrêtons le parcours dès que nous trouvons une dépêche postérieure à la première de nos dépêches enregistrées. */ 
		do{
			$i--;
			$date_a_comparer = convertionDate($depeches_du_site[$i]['date']);
		}while ( ($i > 0) && ($date_a_comparer <= $premiere_date_de_nos_depeches) );
		
		if($i > 0){ // Si $i est strictement positif, cela veut dire que la date de la dépêche n° $i de $depeches_du_site est postérieur à la première date de nos dépêches enregistrées. Nous pouvons donc ajouter toutes les autres dépêches du site (de 0 à $i) à nos dépêches. 
			for($j= $i; $j > -1; $j--){
				for($k =0; $k < 10; $k ++){
					if( $nos_depeches[$k]['titre'] == $depeches_du_site[$j]['titre'] ){
						unset($nos_depeches[$k]);
					}
				}
				
				array_unshift($nos_depeches, $depeches_du_site[$j]); // Nous rajoutons au début de notre liste de dépêches les dépêches récupérées dont la date est postérieure à la première dépêche de notre liste.
			 	$nos_depeches[0]['coordonnees'] = analyseToponyme($nos_depeches[0]['mots_du_titre']);
			 	$nos_depeches[0]['coordonnees'] = array_merge($nos_depeches[0]['coordonnees'], analyseToponyme(decomposeMots($nos_depeches[0]['description'])));
			 	$nos_depeches[0]["lemmes"] = recupereLemmes($nos_depeches[0]["mots_du_titre"]);
			 	$nos_depeches[0]['polarite'] = calculePolarite($nos_depeches[0]["lemmes"]);		
			}
		}else{
			if($i == 0){ // Si $i est nulle, alors il faut comparer les dates, et en fonction, ajouter ou non $depeche_du_site[0] à nos dépêches.
				if( ($date_a_comparer > $premiere_date_de_nos_depeches) ){
					for($k=0; $k < 10; $k++){
						if($nos_depeches[$k]['titre'] == $depeches_du_site[0]['titre']){
							unset($nos_depeches[$k]);
						}
					}
					array_unshift($nos_depeches, $depeches_du_site[0]);
					$nos_depeches[0]['coordonnees'] = analyseToponyme($nos_depeches[0]['mots_du_titre']);
					$nos_depeches[0]['coordonnees'] = array_merge($nos_depeches[0]['coordonnees'], analyseToponyme(decomposeMots($nos_depeches[0]['description'])));
			 		$nos_depeches[0]["lemmes"] = recupereLemmes($nos_depeches[0]["mots_du_titre"]);
			 		$nos_depeches[0]['polarite'] = calculePolarite($nos_depeches[0]["lemmes"]);
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
	file_put_contents('/home/tibo/public_html/CommentVaLeMonde/depeches.json', $depechesJSON); // Enregistre la chaîne de caractères dans le fichier .json
	
	echo("Ecriture terminée.\n");

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>

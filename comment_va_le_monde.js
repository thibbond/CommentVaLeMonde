var currentPopup = null; // Cette variable nous servira pour récupérer la popup "courante".

var carte;

/********************************************************************************************************************************************
*********************************************************************************************************************************************
****************************************** définition de la fonction initialisation() *******************************************************
*********************************************************************************************************************************************
********************************************************************************************************************************************/

/** Cette fonction est appelée lors de la création de la page web. C'est elle qui affiche la carte du monde ainsi que tous les marqueurs et les popup concernées par les dépêches. **/
function initialisation() {
	tableau_marqueurs = []; // Nous enregistrerons dans ce tableau tous les marqueurs que nous créerons, afin de ne pas positionner deux marqueurs au même endroit.

	var coordonnees_centre = new google.maps.LatLng(48.8534, 2.3488);
	
	var options_de_la_carte = {
		zoom: 2.5,
		center: coordonnees_centre,
		scaleControl: true
	};
	
	carte = new google.maps.Map(document.getElementById('carte'), options_de_la_carte); 			
	
	var request = new XMLHttpRequest(); // Cette requête nous sert à récupérer le tableau enregistré dans le fichier "depeches.json".
	
	request.addEventListener('load', function(data){	
		
		var depeches = JSON.parse(data.target.responseText); // Nous convertissons le texte au format JSON retourné par le fichier "affiche_depeches.php" en un tableau. 
	
		for (var j=0; (j < depeches.length) && (j < 100); j++) { // Nous parcourons le tableau des dépêches afin de créer, pour chacune, des marqueurs sur la carte.
			
			var depeche = depeches[j];
			var titre_depeche = depeche["titre"];
			
			for (lieu in depeche["coordonnees"]){ // Pour chaque nom propre trouvé dans la dépêches.
				if (depeche["coordonnees"][lieu]["status"]){ // Si le nom propre est un toponyme.
					
					// Nous récupérons les coordonnées géographiques du lieu.
					var latitude_lieu = depeche["coordonnees"][lieu]["latitude"];
					var longitude_lieu = depeche["coordonnees"][lieu]["longitude"];
					var coordonnees_marqueur = new google.maps.LatLng(latitude_lieu, longitude_lieu);
					
					// Ces deux variables nous servirons à tester si les coordonnées géographiques récupérées possèdent déjà un marqueur sur la carte.
					var sommet_polygone = [coordonnees_marqueur];
					var monPolygone = new google.maps.Polygon( {
						map: carte,
						paths: sommet_polygone
					});
					
					// Cette boucle sert à ne pas positionner deux marqueurs au même endroit (auquel cas, seulement un seul des marqueurs apparaitra sur la carte).
					for (var k =0; k< tableau_marqueurs.length; k++){
						// Pour chaque marqueur enregistré dans notre tableau, nous testons s'il se trouve au même endroit que notre nouveau marqueur.
						if ( google.maps.geometry.poly.containsLocation(tableau_marqueurs[k], monPolygone ) ) {
							// Si c'est le cas, nous changeons la latitude et la longitude.
							latitude_lieu += 0.05;
							longitude_lieu += 0.05;
						}
						
					}
					
					// Nous créons ainsi un nouveau marqueurs avec les latitudes et longitudes finales.
					var coordonnees_definitives = new google.maps.LatLng(latitude_lieu, longitude_lieu);
					var marqueur_depeche = new google.maps.Marker({
							position: coordonnees_definitives , // Nous lui donnons les coordonnées définies plus haut.
							map: carte, // Nous plaçons le marqueur sur notre carte.
							title: titre_depeche, // Nous lui donnons le titre que nous avons récupéré dans notre fichier JSON.
							animation: google.maps.Animation.DROP
					});
					
					marqueur_depeche.setIcon(calculeCouleur(depeche["polarite"])); // Nous attribuons une couleur au marqueur grâce à la fonction calculeCouleur(.) définie plus bas. 
					
					creationPopUp(titre_depeche, depeche["description"], depeche["lien"], marqueur_depeche, carte); // Nous créons une popup qui contiendra des informations concernant la dépêche.
					tableau_marqueurs.push(new google.maps.LatLng(latitude_lieu, longitude_lieu)); // Nous rajoutons à notre tableau de marqueurs le marqueur que nous venons de créer pour pouvoir ensuite comparer sa position.
				}
			}
		}
		setInterval(function(){ depeches = ajouteMarqueur(depeches);}, 10000); // Nous faisons appel à la fonction ajouteMarqueur(.,.) toutes les minutes, afin qu'apparaisse sur notre carte de nouveaux marqueurs associés à de nouvelles dépêches.
 	});
 	
 	request.open("GET", "affiche_depeches.php");
	request.send();	
}

/********************************************************************************************************************************************
*********************************************************************************************************************************************
****************************************** définition de la fonction ajouteMarqueur(.,.) ****************************************************
*********************************************************************************************************************************************
********************************************************************************************************************************************/

/** Cette fonction, grâce à la fonction setInterval(.,.) est appelée toutes les minutes, elle se charge de vérifier si une nouvelle dépêche a été enregistrée dans le fichier "depeches.json", et si oui, elle rajoute à la carte les marqueurs et popup associés. **/
function ajouteMarqueur(anciennes_depeches){
	console.log("Anlayse de la présence de nouvelles dépêches :::");
	var requete = new XMLHttpRequest(); // Cette requête nous sert à récupérer le tableau enregistré dans le fichier "depeches.json".
	
	requete.addEventListener('load', function(donnee){	
		nouvelles_depeches = JSON.parse(donnee.target.responseText); // Nous convertissons le texte au format JSON retourné par le fichier "affiche_depeches.php" en un tableau.
		
		if (nouvelles_depeches[0]["titre"] == anciennes_depeches[0]["titre"]){ // Nous regardons ici si le nouveau tableau récupéré a changé ou non.
			console.log("Pas de nouvelles dépêches.");
		}else{ // Si nous avons une nouvelle dépêche,
			console.log("Nous avons de nouvelles dépêches !");
			var i =0;
			// alors, nous en avons possiblement plusieurs nouvelles.
			
			while (nouvelles_depeches[i+1]["titre"] != anciennes_depeches[0]["titre"]){
				i ++;			
			}
			console.log("Nous avons " + (i+1) + " nouvelle(s) dépêche(s).");
			// A ce moment, i correspond au plus haut indice de notre nouveau tableau pour lequelle la dépêche n'a pas été affiché.
			for(var k = i; k > -1; k--){ // Nous allons donc parcourir notre nouveau tableau en sens inverse, en partant de i pour redescendre à 0, indice qui corespond à la dépêche la plus récente.
			
				// Les lignes suivantes ajoutent des nouveaux marqueurs ansi que de nouveaux popups comme dans la fonction initialisation().
				var titre_nouvelle_depeche = nouvelles_depeches[k]["titre"];
				console.log("Ajout de la dépêche dont le titre est \"" + titre_nouvelle_depeche + "\".");
			
				for (nouveau_lieu in nouvelles_depeches[k]["coordonnees"]){
					if (nouvelles_depeches[k]["coordonnees"][nouveau_lieu]["status"]){
						
						var nouvelle_latitude = nouvelles_depeches[k]["coordonnees"][nouveau_lieu]["latitude"];
						var nouvelle_longitude = nouvelles_depeches[k]["coordonnees"][nouveau_lieu]["longitude"];
						var nouvelles_coordonnees_marqueur = new google.maps.LatLng(nouvelle_latitude, nouvelle_longitude);
						var nouveaux_sommets = [nouvelles_coordonnees_marqueur];
						var nouveau_polygone = new google.maps.Polygon( {
							map: carte,
							paths: nouveaux_sommets
						});
						
						for (var j =0; j< tableau_marqueurs.length; j++){
							if ( google.maps.geometry.poly.containsLocation(tableau_marqueurs[k], nouveau_polygone ) ) {
								console.log("Nous déplaçons le marqueur de la dépêche.");
								nouvelle_latitude += 0.05;
								nouvelle_longitude += 0.05;
							}
							
						}
						var nouvelles_coordonnees_definitives = new google.maps.LatLng(nouvelle_latitude, nouvelle_longitude);
						
						var marqueur_nouvelle_depeche = new google.maps.Marker({
							position: nouvelles_coordonnees_definitives,
							map: carte,
							title: titre_nouvelle_depeche,
							animation: google.maps.Animation.DROP
						});
						marqueur_nouvelle_depeche.setIcon(calculeCouleur(nouvelles_depeches[k]["polarite"]));
					
						creationPopUp(titre_nouvelle_depeche, nouvelles_depeches[k]["description"], nouvelles_depeches[k]["lien"], marqueur_nouvelle_depeche, carte);
						
						tableau_marqueurs.push(new google.maps.LatLng(nouvelle_latitude, nouvelle_longitude));
					}
				}		
			}
			
		}
	
	});
 	
 	requete.open("GET", "affiche_depeches.php");
	requete.send();
	
	return nouvelles_depeches; // Nous retournons nouvelles_depeches afin que soit actualisé chaque minute notre tableau anciennes_depeches.
}

/********************************************************************************************************************************************
*********************************************************************************************************************************************
****************************************** définition de la fonction calculeCouleur(.) ******************************************************
*********************************************************************************************************************************************
********************************************************************************************************************************************/

/** Cette fonction est appelée à chaque création de marqueur. Elle retourne un lien vers l'icone (sa couleur) choisi(e) pour marqueur, en fonction de la polarié de la dépêche associée. **/
function calculeCouleur(polarite) {
	var couleur = '';
	if (polarite == -3){
		couleur += 'http://maps.google.com/mapfiles/ms/icons/red-dot.png';
	}else{
		if (polarite ==  -2) {
			couleur += 'http://maps.google.com/mapfiles/ms/icons/orange-dot.png';
		}else{
			if (polarite == -1){
				couleur += 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png';
			}else{
				if (polarite == 0) {
					couleur += 'http://maps.google.com/mapfiles/ms/icons/green-dot.png';
				}else{
					if (polarite == 1){
						couleur += 'http://maps.google.com/mapfiles/ms/icons/pink-dot.png';
					}else{
						if (polarite == 2){
							couleur += 'http://maps.google.com/mapfiles/ms/icons/purple-dot.png';
						}else{
							if (polarite == 3){
								couleur += 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png';
				 			}
						}
					}
				 }
			}
		}
	}
	return couleur;
}

/********************************************************************************************************************************************
*********************************************************************************************************************************************
****************************************** définition de la fonction créationPopUp((.,.) ****************************************************
*********************************************************************************************************************************************
********************************************************************************************************************************************/

/** Cette fonction est appelée à chaque création de marqueur. Elle crée une popup associé au marqueur créé qui s'ouvre lors d'un clic sur ce dernier. Cette popup comprend le titre de la dépêche associée au marqueur, sa description ainsi qu'un lien vers l'article complet. **/
function creationPopUp(titre, description, lien, marqueur, map){
	// Nous créons la popup et déterminons son contenu.
	var popup = new google.maps.InfoWindow({
		content:"<h1>" + titre + "</h1><p>" + description + "</p><br/>En savoir plus : <a href=\"" + lien + "\">lien vers l'article</a>"
	});
	
	// Nous ajoutons un écouteur d'évennement.
	marqueur.addListener('click', function(){ // Lorsque nous cliquons sur un marqueur,
		if(currentPopup != null){ // si une popup est déjà ouverte,
			currentPopup.close(); // alors nous la fermons (désaffichons).
			currentPopup = null;
		}
		
		popup.open(map, marqueur); // Nous ouvrons (affichons) ensuite la popup associée au marqueur.
		
		currentPopup = popup;
	});
	
	// Nous ajoutons un écouteur d'évenement.
	marqueur.addListener(popup, "closeclick", function() { // Lorsque nous cliquons sur le bouton en forme de croix en haut à droite de la popup,
		currentPopup = null; // cette dernière se désaffiche.
	});
}

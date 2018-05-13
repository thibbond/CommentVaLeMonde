var currentPopup = null;

function initialisation() {

	var coordonnees_centre = new google.maps.LatLng(48.8534, 2.3488);
	var options_de_la_carte = {
		zoom: 2.5,
		center: coordonnees_centre,
		scaleControl: true
	};
	var carte = new google.maps.Map(document.getElementById('carte'), options_de_la_carte); 			
	
	var request = new XMLHttpRequest();
	request.addEventListener('load', function(data){	
		
		var depeches = JSON.parse(data.target.responseText);
	
		for (var j=0; (j < depeches.length) && (j < 100); j++) {
			
			var depeche = depeches[j];
			var titre_depeche = depeche["titre"];
			
			for (lieu in depeche["coordonnees"]){
				if (depeche["coordonnees"][lieu]["status"]){
					var coordonnees_marqueur = new google.maps.LatLng(depeche["coordonnees"][lieu]["latitude"], depeche["coordonnees"][lieu]["longitude"]);
					var marqueur_depeche = new google.maps.Marker({ // on crée un nouveau marker pour chaques résultats
							position: coordonnees_marqueur , // on lui donne les coordonées de son élements
							map: carte, // on l'applique a la map
							title: titre_depeche, // on lui donne le titre  inclus dans le JSON pour chaque coordonnées !
							animation: google.maps.Animation.DROP
					});
					marqueur_depeche.setIcon(calculeCouleur(depeche["positivite"]));
					
					creationPopUp(titre_depeche, depeche["description"], depeche["lien"], marqueur_depeche, carte);
				}
			}
			//depeches[j]["analysee"] = true;
		}
		setInterval(function(){ depeches = ajouteMarqueur(depeches, carte);}, 60000); //
 	});
 	
 	request.open("GET", "affiche_depeches.php");
	request.send();	
}

function ajouteMarqueur(anciennes_depeches, map){
	console.log("Coucou");
	
	var requete = new XMLHttpRequest();
	requete.addEventListener('load', function(donnee){	
		nouvelles_depeches = JSON.parse(donnee.target.responseText);
		//reponse = nouvelles_depeches;
		if (nouvelles_depeches[0]["titre"] == anciennes_depeches[0]["titre"]){
			console.log("Elles sont égales !");
		}else{
			var i =0;
			while (nouvelles_depeches[i+1]["titre"] != anciennes_depeches[0]["titre"]){
				i ++;			
			}
			for(var k = i; k > -1; k--){
				var titre_nouvelle_depeche = nouvelles_depeches[k]["titre"];
			
				for (nouveau_lieu in nouvelles_depeches[k]["coordonnees"]){
					if (nouvelles_depeches[k]["coordonnees"][nouveau_lieu]["status"]){
						var nouvelles_coordonnees_marqueur = new google.maps.LatLng(nouvelles_depeches[k]["coordonnees"][nouveau_lieu]["latitude"], nouvelles_depeches[k]["coordonnees"][nouveau_lieu]["longitude"]);
						var marqueur_nouvelle_depeche = new google.maps.Marker({ // on crée un nouveau marker pour chaques résultats	
							position: nouvelles_coordonnees_marqueur , // on lui donne les coordonées de son élements
							map: map, // on l'applique a la map
							title: titre_nouvelle_depeche, // on lui donne le titre  inclus dans le JSON pour chaque coordonnées !
							animation: google.maps.Animation.DROP
						});
						marqueur_nouvelle_depeche.setIcon(calculeCouleur(nouvelles_depeches[k]["positivite"]));
					
						creationPopUp(titre_nouvelle_depeche, nouvelles_depeches[k]["description"], nouvelles_depeches[k]["lien"], marqueur_nouvelle_depeche, map);
					}
				}		
			}
			
		}
	
	});
 	
 	requete.open("GET", "affiche_depeches.php");
	requete.send();
	
	return nouvelles_depeches;
}

function calculeCouleur(positivite) {
	var couleur = '';
	if (positivite == -3){
		couleur += 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png';
	}else{
		if (positivite ==  -2) {
			couleur += 'http://maps.google.com/mapfiles/ms/icons/purple-dot.png';
		}else{
			if (positivite == -1){
				couleur += 'http://maps.google.com/mapfiles/ms/icons/pink-dot.png';
			}else{
				if (positivite == 0) {
					couleur += 'http://maps.google.com/mapfiles/ms/icons/green-dot.png';
				}else{
					if (positivite == 1){
						couleur += 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png';
					}else{
						if (positivite == 2){
							couleur += 'http://maps.google.com/mapfiles/ms/icons/orange-dot.png';
						}else{
							if (positivite == 3){
								couleur += 'http://maps.google.com/mapfiles/ms/icons/red-dot.png';
				 			}
						}
					}
				 }
			}
		}
	}
	return couleur;
}

function creationPopUp(titre, description, lien, marqueur, map){
	var popup = new google.maps.InfoWindow({
		content:"<h1>" + titre + "</h1><p>" + description + "</p><br/>En savoir plus : <a href=\"" + lien + "\">lien vers l'article</a>"
	});
	
	marqueur.addListener('click', function(){
		if(currentPopup != null){
			currentPopup.close();
			currentPopup = null;
		}
		
		popup.open(map, marqueur);
		
		currentPopup = popup;
	});
	
	marqueur.addListener(popup, "closeclick", function() {
		currentPopup = null;
	});
}

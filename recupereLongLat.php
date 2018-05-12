<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
		<meta charset="UTF-8" />
		<title>Comment va le Monde</title>
		<!--integration de la bibliothèque jquery-->
		<script
			  src="http://code.jquery.com/jquery-3.3.1.min.js"
			  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
			  crossorigin="anonymous">
		</script>
		
		<style>
			html, body {
				height: 100%;
				margin: 0;
				padding: 0
			}
			#EmplacementDeMaCarte {
				height: 100%
			}
		</style>
	</head>
	
	<body>
		
	
		<div id="EmplacementDeMaCarte"></div>
		
			
		<script>



			// fonction qui récupère l'interieur du JSON
			
			var json;// les reponses serons stockée dans cette variable !
			function getmyjson(callback){
				$.getJSON('depeches.json',callback);
			}
			//initialisation basique google map.
			var map;
			var currentPopup = null; // contiendra l'information de la bulle actuellement ouverte
			function initialisation() {
				coord= new google.maps.LatLng(48.8534, 2.3488);
 				var mapOptions = {
  			  		zoom: 2.5,
  			  		center: coord,
  			  		scaleControl: true,
      					mapTypeId: google.maps.MapTypeId.ROADMAP
				};	

  				map = new google.maps.Map(document.getElementById('EmplacementDeMaCarte'),mapOptions); 
				//geocoder = new google.maps.Geocoder(); 
				//Marker Pour le point du centre 
 

				//Fonction pour afficher les points grace aux coordonnées dans le fichier JSON
 				getmyjson(function(data){
					console.log(data);
					var point;
					var coordjson;
					var positi;
					var lie;
					var titre
					var desc;
					// Une boucle pour parcourir toutes les dépêche
					for (var j=0; (j < data.length) && (j < 100); j++) {
						var depeche = data[j];
						//Une boucle pour parcourir les coordonnées
						titre = depeche.titre;
						positi = depeche.positivite;
						lie = depeche.lien;
						desc = depeche.description
						for (lieu in depeche.coordonnees){
							if (depeche.coordonnees[lieu]["status"]){
								coordjson = new google.maps.LatLng(depeche.coordonnees[lieu]["latitude"], depeche.coordonnees[lieu]["longitude"]);
								var marker= new google.maps.Marker({ // on crée un nouveau marker pour chaques résultats
					 				position: coordjson , // on lui donne les coordonées de son élements
					 				map: map, // on l'applique a la map
					 				title: titre, // on lui donne le titre  inclus dans le JSON pour chaque coordonnées !
				 				});
				 				if (positi == -3){
				 					marker.setIcon('http://maps.google.com/mapfiles/ms/icons/blue-dot.png');
				 				}else{
				 					if (positi ==  -2) {
				 						marker.setIcon('http://maps.google.com/mapfiles/ms/icons/purple-dot.png');
				 					}else{
				 						if (positi == -1){
				 							marker.setIcon('http://maps.google.com/mapfiles/ms/icons/pink-dot.png');
				 						}else{
				 							if (positi == 0) {
					 							marker.setIcon('http://maps.google.com/mapfiles/ms/icons/green-dot.png');
				 							}else{
				 								if (positi == 1){
				 									marker.setIcon('http://maps.google.com/mapfiles/ms/icons/yellow-dot.png');
				 								}else{
				 									if (positi == 2){
				 										marker.setIcon('http://maps.google.com/mapfiles/ms/icons/orange-dot.png');
				 									}else{
				 										if (positi == 3){
				 											marker.setIcon('http://maps.google.com/mapfiles/ms/icons/red-dot.png');
				 										}
				 									}
				 								}
				 							}
				 						}
				 					}
				 				}

				 				
				 				var popup = new google.maps.InfoWindow({
				 					content:"Le lien du titre ="+lie+'<br/>'+'<p><a href="http://www.dhnet.be/rss/infos.xml">lien ver le site officiel</a></p>'+titre+'<br/>'+"coordonneé GPS"+" "+coordjson
               							});
								
								//nous activons l'écouteur d'évenement "click" sur notre marqueur(apparition d'un' infobulle sur le maqueur
						 		google.maps.event.addListener(marker, 'click', function(){
				 					//alert(map.marker);
				 					// si la bulle est déjaà ouverte
				 					if(currentPopup != null){
				 						//on la ferme
				 						currentPopup.close();
				 						//on vide la variable
				 						currentPopup = null;
				 					}
				 					// On ouvre la bulle correspondant à notre marqueur
				 					popup.open(map, marker);
				 					// On enregistre cette bulle dans la variable currentPopup
									currentPopup = popup;
								});
								// Nous activons l'écouteur d'évènement "closeclick" sur notre bulle
								// pour surveiller le clic sur le bouton de fermeture
								google.maps.event.addListener(popup, "closeclick", function() {
									// On vide la variable
									currentPopup = null;
								});
				 			}
		 				}
					}
 				})
			}
		</script>
		<script async defer  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDg2msHxBsGxdrwjJAgTkrZMnEmSMcWOmc&callback=initialisation">
		</script>
	</bodyonload="initialisation()">
</html>


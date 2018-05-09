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
			var json;
			function getmyjson(callback){
				$.getJSON('depeches.json',callback);
			}
			
			//initialisation basique google map.
			var map;
			function initialisation() {
				coord= new google.maps.LatLng(47.605821, -122.323546);
 				var mapOptions = {
  			  		zoom: 4,
  			  		center: coord
				};	

  				map = new google.maps.Map(document.getElementById('EmplacementDeMaCarte'),
				mapOptions);  
				//Marker Pour le point du centre 
 

				//Fonction pour afficher les points grace aux coordonnées dans le fichier JSON
 				getmyjson(function(data){
					console.log(data);	
					var depeche;
					var point;
					var latLng;
					var coordjson;
					var positi;
					// Une boucle pour parcourir toutes les dépêche
					for (var j=0; j < data.length; j++) {
						depeche = data[j];
						//Une boucle pour parcourir les coordonnées
						var titre = depeche.titre[0];
						positi = depeche.positivite;
						for (i in depeche.coordonnees){
							point = depeche.coordonnees[i]; // point prend chaques element du JSON
							if (point.status) {
								coordjson = new google.maps.LatLng(point.latitude,point.longitude);
								var marker= new google.maps.Marker({ // on crée un nouveau marker pour chaques résultats
					 				position: coordjson , // on lui donne les coordonées de son élements
					 				map: map, // on l'applique a la map
					 				title: titre // on lui donne le titre  inclus dans le JSON pour chaque coordonnées !
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


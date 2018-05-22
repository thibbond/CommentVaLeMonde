affiche_depeches.php --> sert à afficher les dépêches sur le navigateur

calcule_lemmes.py --> sert à calculer les lemmes dans une phrase passée en argument (ex : "./calcule_lemmes La Maison Blanche est blanche")

comment_va_le_monde.css --> fichier css associé au fichier html

comment_va_le_monde.html --> fichier html à lancer pour observer l'application

comment_va_le_monde.js --> fichier javascript associé au fichier html pour l'affichage de la carte et des marqueurs

depeches.json --> fichier json dans lequel sont stockées toutes les informations concernant les dépêches

lemmes.json --> fichier écrit par le script python et utilisé par recupere_rss.php pour récupérer les lemmes des titres des dépêches

lexique.tct --> lexique des lemmes avec leurs polarités récupéré des travaux de M. Lafourcade

recupere_rss.php --> fichier récupérant les dépêches sur le net et calculant toutes les informations les concernant pour les enregistrer dans 
le fichier depeches.json



1 . Pour l'exécution de recupere_rss.php il vous faut installer treetagger
2 . Afin de pouvoir exécuter automatiquement nos scripts grâce à CRON, il nous a fallu nommer les noms des chemins des fichiers à lire/écrire/exécuter dans tous les fichiers de façon entière ("/home/..."). Il vous faut donc les renommer.
3 . Si vous ne pouvez pas installer treetagger, vous pouvez quand même observer l'apparition de nouveaux marqueurs sur la carte de la façon suivante :
	- coupez la/les premières dépêches du fichier depeches.json
	- lancer l'application web
	- coller les dépêches à leur emplacement initial
	- TADA !
4 . Les codes ont été commentés, nous restons disponibles pour toutes questions.



 

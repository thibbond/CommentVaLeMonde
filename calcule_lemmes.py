#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json, os, re, sys

nos_lemmes = {}

#############################################################################################################################################
#############################################################################################################################################
################################# Récupération de la liste de mots envoyée en argument à notre fichier ######################################
#############################################################################################################################################
#############################################################################################################################################

#### Nous créons un tableau "mots_du_titre". Pour chaque indice i de mots_du_titre, mots_du_titre[i] est un tableau de taille 3 où :     ####
#### mots_du_titre[i][0] correspond au ième mot passé en paramètre, mots_du_titre[1] correspond à son type retourné par treetagger       ####
#### ("ADJ"/"NOM"/"DET"/...) et mots_du_titre[2] correspond à la forme "dictionnaire" du mot retournée par treetagger (ex : pour le mot  ####
#### "annulent" mots_du_titre[2]="annuler", pour "habitants" mots_du_titre[2]="habitant").                                               ####

taille_liste = len(sys.argv) -1 # Le premier argurment est le nom du fichier python.

# Nous allons créer la commande treetagger afin d'analyser syntaxiquement les mots de notre liste.
commande_treetagger = 'echo "'
for i in range (0, taille_liste) :
	commande_treetagger +=  sys.argv[i+1] + '\\n'	
commande_treetagger += '" |/home/tibo/TreeTagger/bin/tree-tagger -token -lemma -quiet /home/tibo/TreeTagger/lib/french-utf8.par'

# Nous enregistrons dans un tableau les lignes résultant de l'exécution de la commande "commande_treetagger".
resultat_commande = os.popen(commande_treetagger).readlines()

#############################################################################################################################################
#############################################################################################################################################
############ Création de la liste mots_du_titre comprenant les mots, leurs natures, ainsi que les mots sans déclinaisons du titre ###########
#############################################################################################################################################
#############################################################################################################################################

### Nous allons parcourir les lignes résultantes de l'exécution de la commande treetagger pour récuperer, grâce aux expressions régulières, les informations qui nous intéressent.

mots_du_titre = []
i = 0
for ligne_res in resultat_commande :
	res = re.search('^([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+', ligne_res)
	if res :
		mots_du_titre.append([]) # Nous rajoutons un tableau vide à notre tableau.
		mots_du_titre[i].append(res.group(1)) # Dans ce tableau vide, nous rajoutons en indice 0 le mot passé en argument du fichier.
		mots_du_titre[i].append(res.group(2)) # Puis son type.
		
		# Pour la forme dico, il se peut que treetagger suggère deux choix de réponse (pour les mots genrés) de la façon suivante nom_masculin|nom_féminin (ex: chien|chienne), il faut donc choisir lequel des deux nous voulons rajouter à notre tableau.
		mot_dico = res.group(3)
		recherche  = re.search("^([^\|]+)\|([^\|]+)$", mot_dico)
		if recherche :
			if (mots_du_titre[i-1]) : # Afin de sélectionner le genre, nous regardons le genre de l'article (s'il y en a un) qui le précède.
				if mots_du_titre[i-1][0].lower() in {"la", "une", "ma", "ta", "sa", "cette"} :
					mots_du_titre[i].append(recherche.group(2))
				elif mots_du_titre[i-1][2] != "<unknown>" :
					if mots_du_titre[i-1][2].lower() in {"la", "une", "ma", "ta", "sa", "cette"} :
						mots_du_titre[i].append(recherche.group(2))
					else : # Si nous ne trouvons pas d'article féminin précédent notre mot, nous rajoutons la forme dico masculine.
						mots_du_titre[i].append(recherche.group(1))
				else :
					mots_du_titre[i].append(recherche.group(1))
			else :
				mots_du_titre[i].append(recherche.group(1))
		else :
			mots_du_titre[i].append(mot_dico)
	else	: # Ce cas n'arrive jamais, puisque l'expression régulière est bien écrite, nous pourrions nous en passer. 
		mot_du_titre[i].append("<unknown>")
		mot_du_titre[i].append("<unknown>")
	i += 1

################################################## Affichage du résultat ####################################################################

print("\nLe résultat de l'exécution de la commande treetagger est le suivant :")
for i in range (0, taille_liste) :
	ligne = mots_du_titre[i][0] + "			" + mots_du_titre[i][1]
	if len(mots_du_titre[i][1]) > 3 and i != 0:
		if len(mots_du_titre[i][1]) > 6 :
			ligne += "		"
		else :
			ligne += "		"
	else:
		ligne += "			"
	ligne += mots_du_titre[i][2]
	print(ligne)
print("")

#############################################################################################################################################
#############################################################################################################################################
#################################### Définition de la fonction constructionExpressions(.,.,.) ###############################################
#############################################################################################################################################
#############################################################################################################################################

# Cette fonction appelée dans la fonction récuperePolarité(.,.) retourne les huit expressions régulières différentes (leurs noms parlent d'eux-même) qui nous serviront à repérer des lemmes présents ou non dans le titre.
def constructionExpressions(j, i, liste_de_mots) :
	tableau = []
	k = j
	expression_inchangee = "^"
	expression_avec_lettres_en_minuscule = "^"
	expression_avec_verbes_a_l_infinitif = "^"
	expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif = "^"		
	expression_avec_mots_au_singulier = "^"
	expression_avec_verbes_a_l_infinitif_et_mots_au_singulier = "^"
	expression_avec_lettres_en_minuscule_et_mots_au_singulier = "^"
	expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier = "^"
	
	while k < j+i -1 :
		print("		k = " + str(k))
		expression_inchangee += liste_de_mots[k][0] + "[\s*|']"
		expression_avec_lettres_en_minuscule += liste_de_mots[k][0].lower() + "[\s*|']"
		if liste_de_mots[k][1][0:3] == "VER" :
			expression_avec_mots_au_singulier += liste_de_mots[k][0] + "[\s*|']"
			expression_avec_lettres_en_minuscule_et_mots_au_singulier += liste_de_mots[k][0].lower() + "[\s*|']"
				
			if liste_de_mots[k][2] != "<unknown>" :
				expression_avec_verbes_a_l_infinitif += liste_de_mots[k][2] + "[\s*|']"
				expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif += liste_de_mots[k][2].lower() + "[\s*|']"
				expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][2] + "[\s*|']"
				expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][2].lower() + "[\s*|']"
			else :
				expression_avec_verbes_a_l_infinitif += liste_de_mots[k][0] + "[\s*|']"
				expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif += liste_de_mots[k][0].lower() + "[\s*|']"
				expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0] + "[\s*|']"
				expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0].lower() + "[\s*|']"
		elif (liste_de_mots[k][1][0:3] in {"DET", "PRP", "NOM", "ADJ"}) :
			expression_avec_verbes_a_l_infinitif += liste_de_mots[k][0] + "[\s*|']"
			expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif += liste_de_mots[k][0].lower() + "[\s*|']"
			
			if liste_de_mots[k][2] != "<unknown>" :
				expression_avec_mots_au_singulier += liste_de_mots[k][2] + "[\s*|']"
				expression_avec_lettres_en_minuscule_et_mots_au_singulier += liste_de_mots[k][2].lower() + "[\s*|']"
				expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][2] + "[\s*|']"
				expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][2].lower() + "[\s*|']"
			else :
				expression_avec_mots_au_singulier += liste_de_mots[k][0] + "[\s*|']"
				expression_avec_lettres_en_minuscule_et_mots_au_singulier += liste_de_mots[k][0].lower() + "[\s*|']"
				expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0] + "[\s*|']"
				expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0].lower() + "[\s*|']"
		else :
			expression_avec_verbes_a_l_infinitif += liste_de_mots[k][0] + "[\s*|']"
			expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif += liste_de_mots[k][0].lower() + "[\s*|']"
			expression_avec_mots_au_singulier += liste_de_mots[k][0] + "[\s*|']"
			expression_avec_lettres_en_minuscule_et_mots_au_singulier += liste_de_mots[k][0].lower() + "[\s*|']"
			expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0] + "[\s*|']"
			expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0].lower() + "[\s*|']"
		k +=1
	
	print("		k = " + str(j+i-1))
	
	expression_inchangee += liste_de_mots[j+i-1][0] + ":(-?\d+)\s*$"
	expression_avec_lettres_en_minuscule += liste_de_mots[j+i-1][0].lower() + ":(-?\d+)\s*$"
	
	if liste_de_mots[k][1][0:3] == "VER" :
		expression_avec_mots_au_singulier += liste_de_mots[k][0] + ":(-?\d+)\s*$"
		expression_avec_lettres_en_minuscule_et_mots_au_singulier += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
		
		if liste_de_mots[k][2] != "<unknown>" :
			expression_avec_verbes_a_l_infinitif += liste_de_mots[k][2] + ":(-?\d+)\s*$"
			expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif += liste_de_mots[k][2].lower() + ":(-?\d+)\s*$"
			expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][2] + ":(-?\d+)\s*$"
			expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][2].lower() + ":(-?\d+)\s*$"
		else :
			expression_avec_verbes_a_l_infinitif += liste_de_mots[k][0] + ":(-?\d+)\s*$"
			expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
			expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0] + ":(-?\d+)\s*$"
			expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
	elif (liste_de_mots[k][1][0:3] in {"DET", "PRP", "NOM", "ADJ"}) :
		expression_avec_verbes_a_l_infinitif += liste_de_mots[k][0] + ":(-?\d+)\s*$"
		expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
		
		if liste_de_mots[k][2] != "<unknown>" :
			expression_avec_mots_au_singulier += liste_de_mots[k][2] + ":(-?\d+)\s*$"
			expression_avec_lettres_en_minuscule_et_mots_au_singulier += liste_de_mots[k][2].lower() + ":(-?\d+)\s*$"
			expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][2] + ":(-?\d+)\s*$"
			expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][2].lower() + ":(-?\d+)\s*$"
		else :
			expression_avec_mots_au_singulier += liste_de_mots[k][0] + ":(-?\d+)\s*$"
			expression_avec_lettres_en_minuscule_et_mots_au_singulier += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
			expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0] + ":(-?\d+)\s*$"
			expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
	else :
		expression_avec_verbes_a_l_infinitif += liste_de_mots[k][0] + ":(-?\d+)\s*$"
		expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
		expression_avec_mots_au_singulier += liste_de_mots[k][0] + ":(-?\d+)\s*$"
		expression_avec_lettres_en_minuscule_et_mots_au_singulier += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
		expression_avec_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0] + ":(-?\d+)\s*$"
		expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier += liste_de_mots[k][0].lower() + ":(-?\d+)\s*$"
	
	tableau.append(expression_inchangee)
	tableau.append(expression_avec_lettres_en_minuscule)
	tableau.append(expression_avec_verbes_a_l_infinitif)
	tableau.append(expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif)
	tableau.append(expression_avec_mots_au_singulier)
	tableau.append(expression_avec_verbes_a_l_infinitif_et_mots_au_singulier)
	tableau.append(expression_avec_lettres_en_minuscule_et_mots_au_singulier)
	tableau.append(expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier)
	
	for i in range (0, len(tableau)):
		print(tableau[i])
	return tableau

#############################################################################################################################################
#############################################################################################################################################
############################################ Définition de la fonction recuperePolarites(.,.) ###############################################
#############################################################################################################################################
#############################################################################################################################################

# Nous récupérons les lignes du fichier lexique.txt dans la liste lexique.
with open('/home/tibo/public_html/CommentVaLeMonde/lexique.txt', 'r') as lexiqueTXT :
	lexique = lexiqueTXT.readlines()
lexiqueTXT.close()

################################################################ définition #################################################################

def recuperePolarites(liste_de_mots, position) :
	liste_de_lemmes = {} # Nous allons enregistrer tous nos lemmes ainsi que leurs polarités dans un dictionnaire.
	print("Nous avons une nouvelle phrase de taille " + str(len(liste_de_mots)) + " :")
	
	# Les quatres lignes suivantes servent simplement l'affichage dans le teminal de la phrase analysée.
	nouvelle_phrase = ''
	for l in range (0, len(liste_de_mots)) :
		nouvelle_phrase += liste_de_mots[l][0]  + " "
	print(nouvelle_phrase)
	
	i = min(5, len(liste_de_mots)) # Par soucis de complexité, nous ne rechercherons pas dans le lexique les expressions composées de plus de cinq mots.
	while i > 0 :
		print("i = " + str(i))
		j = 0
		while j < len(liste_de_mots) -i + 1 :
			print("	j = " + str(j) + ", position = " + str(position + j))
						
			tableau_des_expressions = constructionExpressions(j,i,liste_de_mots) # Nous récupérons les huits expressions que nous allons utiliser.
			expression_inchangee = tableau_des_expressions[0]
			
			for ligne_lex in lexique : # Nous parcourrons toutes les lignes du lexique
				res = re.search(expression_inchangee, ligne_lex) # Nous regardons si notre expression correspond au lemme de la ligne
				if res :
					print("Nous avons un résultat pour l'expression inchangée " + expression_inchangee + " : sa polarité est " + res.group(1) + "\n")
					lemme = ''
					k = j
					while k < j+i-1 :
						lemme += liste_de_mots[k][0] + " "
						k += 1
					lemme += liste_de_mots[j+i-1][0]
					
					liste_de_lemmes[lemme] = {}
					liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
					liste_de_lemmes[lemme]["taille"] = i
					liste_de_lemmes[lemme]["forme_trouvee"] = "INC"
					liste_de_lemmes[lemme]["position"] = position + j
					if j > 0 : # Si j > 0, alors il reste des mots à gauche du lemme dans le titre à analyser.
						tab = liste_de_mots[0:j]
						liste_de_lemmes.update(recuperePolarites(tab, position))
					if j +i -1 < len(liste_de_mots) -1 :
						# Si j< len(liste_de_mots) - 1, alors il reste des mots à droite du lemme dans le titre à analyser.
						tab = liste_de_mots[j+i:len(liste_de_mots)]
						liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
					i = -1 # Pour sortir de la boucle du $i.
					j = 100 # Pour sortir de la boucle $j.
					break # Pour sortir de la boucle du lexique.
				else :
					# De la même façon que précédemment, nous effectuons nos 7 autres recherches avec nos expressions régulières.
					expression_avec_lettres_en_minuscule = tableau_des_expressions[1]
					res = re.search(expression_avec_lettres_en_minuscule, ligne_lex)
					if res :
						print("Nous avons un résultat pour l'expression en minuscule" + expression_avec_lettres_en_minuscule + " : sa polarité est " + res.group(1))
						lemme = ''
						k = j
						while k < j+i-1 :
							lemme += liste_de_mots[k][0] + " "
							k += 1
						lemme += liste_de_mots[j+i-1][0]
						
						if lemme in liste_de_lemmes.keys() :
							if liste_de_lemmes[lemme]["forme_trouvee"] != "INC" :
								liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
								liste_de_lemmes[lemme]["taille"] = i
								liste_de_lemmes[lemme]["forme_trouvee"] = "MIN"
								liste_de_lemmes[lemme]["position"] = position + j
								if j > 0 :
									tab = liste_de_mots[0:j]
									liste_de_lemmes.update(recuperePolarites(tab, position))
								if j +i -1 < len(liste_de_mots) -1 :
									tab = liste_de_mots[j+i:len(liste_de_mots)]
									liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
								i = -1
								j = 100
								break
						else :
							liste_de_lemmes[lemme] = {}
							liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
							liste_de_lemmes[lemme]["taille"] = i
							liste_de_lemmes[lemme]["forme_trouvee"] = "MIN"
							liste_de_lemmes[lemme]["position"] = position + j
							if j > 0 :
								tab = liste_de_mots[0:j]
								liste_de_lemmes.update(recuperePolarites(tab, position))
							if j +i -1 < len(liste_de_mots) -1 :
								tab = liste_de_mots[j+i:len(liste_de_mots)]
								liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
							i = -1
							j = 100
							break
					else :
						expression_avec_verbes_a_l_infinitif = tableau_des_expressions[2]
						res = re.search(expression_avec_verbes_a_l_infinitif, ligne_lex)
						if res :
							print("Nous avons un résultat pour l'expression avec verbes à l'infinitif " + expression_avec_verbes_a_l_infinitif + " : sa polarité est " + res.group(1))
							
							lemme = ''
							k = j
							while k < j+i-1 :
								lemme += liste_de_mots[k][0] + " "
								k += 1
							lemme += liste_de_mots[j+i-1][0]
							
							if lemme in nos_lemmes.keys() :
								if nos_lemmes[lemme]["forme_trouvee"] not in {"INC", "MIN"} :
									liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
									liste_de_lemmes[lemme]["taille"] = i
									liste_de_lemmes[lemme]["forme_trouvee"] = "INF"
									liste_de_lemmes[lemme]["position"] = position + j
									if j > 0 :
										tab = liste_de_mots[0:j]
										liste_de_lemmes.update(recuperePolarites(tab, position))
									if j +i -1 < len(liste_de_mots) -1 :
										tab = liste_de_mots[j+i:len(liste_de_mots)]
										liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
									i = -1
									j = 100
									break
							else :
								liste_de_lemmes[lemme] = {}
								liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
								liste_de_lemmes[lemme]["taille"] = i
								liste_de_lemmes[lemme]["forme_trouvee"] = "INF"
								liste_de_lemmes[lemme]["position"] = position + j
								if j > 0 :
									tab = liste_de_mots[0:j]
									liste_de_lemmes.update(recuperePolarites(tab, position))
								if j +i -1 < len(liste_de_mots) -1 :
									tab = liste_de_mots[j+i:len(liste_de_mots)]
									liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
								i = -1
								j = 100
								break
						else :
							expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif = tableau_des_expressions[3]
							res = re.search(expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif, ligne_lex)	
							if recherche :
								print("Nous avons un résultat pour l'expression avec verbes à l'infinitif et lettres en minuscule" + expression_avec_lettres_en_minuscule_et_verbes_a_l_infinitif + " : sa polarité est " + res.group(1))
								
								lemme = ''
								k = j
								while k < j+i-1 :
									lemme += liste_de_mots[k][0] + " "
									k += 1
								lemme += liste_de_mots[j+i-1][0]
								
								if lemme in nos_lemmes.keys() :
									if nos_lemmes[lemme]["forme_trouvee"] not in {"INC", "MIN", "INF"} :
										nos_lemmes[lemme]["polarite"] = int(recherche.group(1))
										nos_lemmes[lemme]["taille"] = i
										nos_lemmes[lemme]["forme_trouvee"] = "INFMIN"
										liste_de_lemmes[lemme]["position"] = position + j
										if j > 0 :
											tab = liste_de_mots[0:j]
											liste_de_lemmes.update(recuperePolarites(tab, position))
										if j +i -1 < len(liste_de_mots) -1 :
											tab = liste_de_mots[j+i:len(liste_de_mots)]
											liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
										i = -1
										j = 100
										break
								else :
									nos_lemmes[lemme] = {}
									nos_lemmes[lemme]["polarite"] = int(recherche.group(1))
									nos_lemmes[lemme]["taille"] = i
									nos_lemmes[lemme]["forme_trouvee"] = "INFMIN"
									liste_de_lemmes[lemme]["position"] = position + j
									if j > 0 :
										tab = liste_de_mots[0:j]
										liste_de_lemmes.update(recuperePolarites(tab, position))
									if j +i -1 < len(liste_de_mots) -1 :
										tab = liste_de_mots[j+i:len(liste_de_mots)]
										liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
									i = -1
									j = 100
									break
							else :
								expression_avec_mots_au_singulier = tableau_des_expressions[4]
								res = re.search(expression_avec_mots_au_singulier, ligne_lex)
								if recherche :
									print("Nous avons un résultat pour l'expression avec mots au singulier " + expression_avec_mots_au_singulier + " : sa polarité est " + res.group(1))
									
									lemme = ''
									k = j
									while k < j+i-1 :
										lemme += liste_de_mots[k][0] + " "
										k += 1
									lemme += liste_de_mots[j+i-1][0]
									
									if lemme in nos_lemmes.keys() :
										if nos_lemmes[lemme]["forme_trouvee"] not in {"INC", "MIN", "INF", "INFMIN"} :	
											liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
											liste_de_lemmes[lemme]["taille"] = i
											liste_de_lemmes[lemme]["forme_trouvee"] = "SING"
											liste_de_lemmes[lemme]["position"] = position + j
											if j > 0 :
												tab = liste_de_mots[0:j]
												liste_de_lemmes.update(recuperePolarites(tab, position))
											if j +i -1 < len(liste_de_mots) -1 :
												tab = liste_de_mots[j+i:len(liste_de_mots)]
												liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
											i = -1
											j = 100
											break
									else :
										liste_de_lemmes[lemme] = {}
										liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
										liste_de_lemmes[lemme]["taille"] = i
										liste_de_lemmes[lemme]["forme_trouvee"] = "SING"
										liste_de_lemmes[lemme]["position"] = position + j
										if j > 0 :
											tab = liste_de_mots[0:j]
											liste_de_lemmes.update(recuperePolarites(tab, position))
										if j +i -1 < len(liste_de_mots) -1 :
											tab = liste_de_mots[j+i:len(liste_de_mots)]
											liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
										i = -1
										j = 100
										break
								else :
									expression_avec_verbes_a_l_infinitif_et_mots_au_singulier = tableau_des_expressions[5]
									res = re.search(expression_avec_verbes_a_l_infinitif_et_mots_au_singulier, ligne_lex)
									if res :
										print("Nous avons un résultat pour l'expression avec mots au singulier et verbes a l'infinitif " + expression_avec_verbes_a_l_infinitif_et_mots_au_singulier + " : sa polarité est " + res.group(1))
										
										lemme = ''
										k = j
										while k < j+i-1 :
											lemme += liste_de_mots[k][0] + " "
											k += 1
										lemme += liste_de_mots[j+i-1][0]
										
										if lemme in nos_lemmes.keys() :
											if nos_lemmes[lemme]["forme_trouvee"] not in {"INC", "MIN", "INF", "INFMIN", "SING"} :
												liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
												liste_de_lemmes[lemme]["taille"] = i
												liste_de_lemmes[lemme]["forme_trouvee"] = "INFSING"
												liste_de_lemmes[lemme]["position"] = position + j
												if j > 0 :
													tab = liste_de_mots[0:j]
													liste_de_lemmes.update(recuperePolarites(tab, position))
												if j +i -1 < len(liste_de_mots) -1 :
													tab = liste_de_mots[j+i:len(liste_de_mots)]
													liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
												i = -1
												j = 100
												break
										else :
											liste_de_lemmes[lemme] = {}
											liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
											liste_de_lemmes[lemme]["taille"] = i
											liste_de_lemmes[lemme]["forme_trouvee"] = "INFSING"
											liste_de_lemmes[lemme]["position"] = position + j
											if j > 0 :
												tab = liste_de_mots[0:j]
												liste_de_lemmes.update(recuperePolarites(tab, position))
											if j +i -1 < len(liste_de_mots) -1 :
												tab = liste_de_mots[j+i:len(liste_de_mots)]
												liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
											i = -1
											j = 100
											break
									else :
										expression_avec_lettres_en_minuscule_et_mots_au_singulier = tableau_des_expressions[6]
										res = re.search(expression_avec_lettres_en_minuscule_et_mots_au_singulier, ligne_lex)
										if res :
											print("Nous avons un résultat pour l'expression avec mots au singulier et lettres en minuscule " + expression_avec_lettres_en_minuscule_et_mots_au_singulier + " : sa polarité est " + res.group(1))
											lemme = ''
											k = j
											while k < j+i-1 :
												lemme += liste_de_mots[k][0] + " "
												k += 1
											lemme += liste_de_mots[j+i-1][0]
											
											if lemme in nos_lemmes.keys() :
												if nos_lemmes[lemme]["forme_trouvee"] not in {"INC", "MIN", "INF", "INFMIN", "SING", "INFSING"} :
													liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
													liste_de_lemmes[lemme]["taille"] = i
													liste_de_lemmes[lemme]["forme_trouvee"] = "MINSING"
													liste_de_lemmes[lemme]["position"] = position + j
													if j > 0 :
														tab = liste_de_mots[0:j]
														liste_de_lemmes.update(recuperePolarites(tab, position))	
													if j +i -1 < len(liste_de_mots) -1 :
														tab = liste_de_mots[j+i:len(liste_de_mots)]		
														liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
													i = -1
													j = 100
													break
											else :
												liste_de_lemmes[lemme] = {}
												liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
												liste_de_lemmes[lemme]["taille"] = i
												liste_de_lemmes[lemme]["forme_trouvee"] = "MINSING"
												liste_de_lemmes[lemme]["position"] = position + j
												if j > 0 :
													tab = liste_de_mots[0:j]
													liste_de_lemmes.update(recuperePolarites(tab, position))
												if j +i -1 < len(liste_de_mots) -1 :
													tab = liste_de_mots[j+i:len(liste_de_mots)]
													liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
												i = -1
												j = 100
												break
										else :
											expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier = tableau_des_expressions[7]
											res = re.search(expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier, ligne_lex)
											if res :
												print("Nous avons un résultat pour l'expression avec mots au singulier, verbes à l'infinitif et lettres en minuscule " + expression_avec_lettres_en_minuscule_verbes_a_l_infinitif_et_mots_au_singulier + " : sa polarité est " + res.group(1))
												
												lemme = ''
												k = j
												while k < j+i-1 :
													lemme += liste_de_mots[k][0] + " "
													k += 1
												lemme += liste_de_mots[j+i-1][0]
												
												if lemme in nos_lemmes.keys() :
													if nos_lemmes[lemme]["forme_trouvee"] not in {"INC", "MIN", "INF", "INFMIN", "SING", "INFSING", "MINSING"} :
														liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
														liste_de_lemmes[lemme]["taille"] = i
														liste_de_lemmes[lemme]["forme_trouvee"] = "MININFSING"
														liste_de_lemmes[lemme]["position"] = position + j
														if j > 0 :
															tab = liste_de_mots[0:j]
															liste_de_lemmes.update(recuperePolarites(tab, position))	
														if j +i -1 < len(liste_de_mots) -1 :
															tab = liste_de_mots[j+i:len(liste_de_mots)]		
															liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
														i = -1
														j = 100
														break
												else :
													liste_de_lemmes[lemme] = {}
													liste_de_lemmes[lemme]["polarite"] = int(res.group(1))
													liste_de_lemmes[lemme]["taille"] = i
													liste_de_lemmes[lemme]["forme_trouvee"] = "MININFSING"
													liste_de_lemmes[lemme]["position"] = position + j	
													if j > 0 :
														tab = liste_de_mots[0:j]
														liste_de_lemmes.update(recuperePolarites(tab, position))
													if j +i -1 < len(liste_de_mots) -1 :
														tab = liste_de_mots[j+i:len(liste_de_mots)]
														liste_de_lemmes.update(recuperePolarites(tab, position+i+j))
													i = -1
													j = 100
													break
			j+=1
		i = i-1
	return liste_de_lemmes
	
#############################################################################################################################################
#############################################################################################################################################
################################################# Appel de la fonction recuperePolarites(.,.) ###############################################
#############################################################################################################################################
#############################################################################################################################################

nos_lemmes = recuperePolarites(mots_du_titre,0)

########################################################### Affichage du résultat ###########################################################

for lemme in nos_lemmes :
	print("La polarité du lemme \"" + lemme + "\" est " + str(nos_lemmes[lemme]["polarite"]) + ". Il est composé de " + str(nos_lemmes[lemme]["taille"]) + " mots. Il a été récupéré grâce à une expression de type " + nos_lemmes[lemme]["forme_trouvee"] + ".")

############################################# Ajout de la clé "nature à la liste à enregistrer ##############################################

for lemme in nos_lemmes :	
	if nos_lemmes[lemme]["taille"] == 1 :
		for i in range(0, len(mots_du_titre)) :
			if mots_du_titre[i][0] == lemme :
				nos_lemmes[lemme]["nature"] = mots_du_titre[i][1]
	else :
		nos_lemmes[lemme]["nature"] = "SUITE_DE_MOTS"

########################################## Enregistrement de la liste dans le fichier lemmes.json ###########################################

with open('/home/tibo/public_html/CommentVaLeMonde/lemmes.json', 'w', encoding='utf-8') as fichier :
	json.dump(nos_lemmes, fichier, indent=4)
fichier.close()

#############################################################################################################################################
#############################################################################################################################################

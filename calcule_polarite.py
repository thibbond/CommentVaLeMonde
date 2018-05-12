#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json, os, re, sys

liste_des_mots_du_titre = []
for i in range (0, (len(sys.argv) -1) ) :
	liste_des_mots_du_titre.append(sys.argv[i+1])

with open('polarites.txt', 'r') as lexiqueTXT :
	lexique = lexiqueTXT.readlines()
lexiqueTXT.close()

def analyse_positivite(liste_de_mots) :
	somme_des_polarites = 0
	nb_de_mots_trouves = 0
	ma_liste_de_mots = []
	
	commande_treetagger = 'echo "'
	for j in range (0, len(liste_de_mots)) :
		commande_treetagger += liste_de_mots[j] + '\\n'	
	commande_treetagger += '"  |/home/tibo/TreeTagger/bin/tree-tagger -token -lemma -quiet /home/tibo/TreeTagger/lib/french-utf8.par'

	resultat_commande = os.popen(commande_treetagger).readlines()
	
	for ligne_res in resultat_commande :
		print(ligne_res[0:len(ligne_res)-1])
		res = re.search('^(.+)\s+(.+)\s+(.+)\s+', ligne_res) 
		if res :
			dico_du_mot = {}
			mot_a_rechercher = res.group(1)
			type_de_mot = res.group(2)
			mot_de_secour = res.group(3)
			dico_du_mot["mot"] = mot_a_rechercher
			dico_du_mot["type"] = type_de_mot
			dico_du_mot["mot_associe"] = mot_de_secour
			if mot_a_rechercher not in {"l", "d", "qu", "L", "D", "Qu", "s", "S", "t", "T", "m", "M"} :
				if (type_de_mot in {"NOM", "ADJ", "ADV"}) or type_de_mot[0:3] == "VER" :
					print("Recherche du mot " + mot_a_rechercher + " dans le lexique ...")
					le_mot_a_ete_trouve = False
					for ligne_lex in lexique :
						recherche_lexique = re.search("^" + mot_a_rechercher + ":(-?\d+)\s+$", ligne_lex)
						if recherche_lexique :
							polarite_du_mot = recherche_lexique.group(1)
							somme_des_polarites += int(polarite_du_mot)
							nb_de_mots_trouves += 1
							le_mot_a_ete_trouve = True
							dico_du_mot["polarite"] = int(polarite_du_mot)
					if le_mot_a_ete_trouve :
						print ("Le mot \"" + mot_a_rechercher + "\" a été trouvé dans le lexique, sa polarité est : " + polarite_du_mot + ".")
						pass
					else :
						print ("Le mot \"" + mot_a_rechercher + "\" n'a pas été trouvé.")
						if mot_de_secour != "<unknown>" and mot_de_secour != mot_a_rechercher :
							print("Recherche du mot " + mot_de_secour + " dans le lexique ...")
							for ligne_lex in lexique :
								recherche_lexique = re.search("^" + mot_de_secour + ":(-?\d+)\s+$", ligne_lex)
								if recherche_lexique :
									polarite_du_mot = recherche_lexique.group(1)
									somme_des_polarites += int(polarite_du_mot)
									nb_de_mots_trouves += 1
									le_mot_a_ete_trouve = True
									dico_du_mot["polarite"] = int(polarite_du_mot)
							if le_mot_a_ete_trouve :
								print ("Le mot \"" + mot_de_secour + "\" a été trouvé dans le lexique, sa polarité est : " + polarite_du_mot + ".")
							else :
								print("Le mot " + mot_de_secour + " n'a pas été trouvé.")
			ma_liste_de_mots.append(dico_du_mot)
	return calcul_polarite(ma_liste_de_mots)

def calcul_polarite(liste) :
	taille_liste = len(liste)
	somme_des_polarites = 0
	coefficient = 1
	somme_des_coefficients = 0
	premier_coef_adverbe = True
	
	for i in range (0, taille_liste) :
		if "polarite" in liste[i].keys() :
			if liste[i]["type"] == "ADV" :
				if premier_coef_adverbe :
					coefficient = 0
					premier_coef_adverbe = False
				coefficient += liste[i]["polarite"]
			else :
				if coefficient >= 0 :
					somme_des_polarites += (liste[i]["polarite"] * coefficient)
					somme_des_coefficients += coefficient
				else :
					if liste[i]["polarite"] > 0 :
						somme_des_polarites += (liste[i]["polarite"] * coefficient)
					else :
						somme_des_polarites += -(liste[i]["polarite"] * coefficient)
					somme_des_coefficients += (-coefficient)
				coefficient = 1
				premier_coef_adverbe = True
		else :
			if (coefficient != 1) : # => i > 0 && liste[i-1]["type"] == "ADV"
				j = i-1
				while liste[j]["type"] == "ADV" and j>=1 :
					j += -1
				if liste[j]["type"] == "ADV" : # => j = 0 Les i-1 premiers mots de la phrases sont des adverbes et le ième n'est pas polarisé. On ajoute alors seulement la positivité des adverbes comme s'ils étaient des noms.
					somme_des_polarites += coefficient
					somme_des_coefficients += 1
				else : 
					if "polarite" in liste[j].keys() :
						if coefficient >= 0 :
							somme_des_polarites += (liste[j]["polarite"] * coefficient)
							somme_des_coefficients += coefficient
						else :
							if liste[j]["polarite"] >= 0 :
								somme_des_polarites += (liste[j]["polarite"] * coefficient)
							else :
								somme_des_polarites += -(liste[j]["polarite"] * coefficient)
							somme_des_coefficients += -coefficient
					else :
						somme_des_polarites += coefficient
						somme_des_coefficients += 1
						
				coefficient = 1
				premier_coef_adverbe = True
	if somme_des_coefficients == 0 :
		moyenne = somme_des_polarites
	else :
		moyenne = somme_des_polarites / somme_des_coefficients
	return round(moyenne)

print(str(analyse_positivite(liste_des_mots_du_titre)))

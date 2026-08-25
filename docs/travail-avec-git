# 📝 AIDE-MÉMOIRE GIT AU QUOTIDIEN (CMS GRAV)

Ce document résume les commandes indispensables pour gérer l'historique et la sauvegarde de mon site internet local (/var/www/grav) vers mon compte GitHub.

---

## 🔄 LE CYCLE DE TRAVAIL CLASSIQUE
À exécuter à chaque fois que j'ai terminé de modifier mes pages ou mes configurations dans VS Code.

### Étape 1 : Vérifier les modifications en cours
Avant de sauvegarder, je regarde ce qui a changé sur mon PC.
```bash
git status
```
* Résultat : Affiche les fichiers modifiés en rouge. Les fichiers ignorés (caches, vidéos, audios) n'apparaissent pas.

### Étape 2 : Préparer les fichiers pour la sauvegarde (L'Index)
Je sélectionne les fichiers à ajouter à ma prochaine "photo".
```bash
# Pour ajouter TOUTES les modifications d'un coup (le plus fréquent) :
git add .

# Pour ajouter un fichier précis de manière chirurgicale :
git add user/pages/03.spectacles/default.fr.md
```
* Résultat : Les fichiers passent au vert dans le "git status".

### Étape 3 : Valider la sauvegarde locale (Le Commit)
Je fige mes modifications dans l'histoire de mon site avec un message explicatif.
```bash
git commit -m "Mon message explicatif ici"
```
* Règle d'or : Écrire un message court mais clair (ex: "Correction orthographe accueil", "Ajout tarifs prestations").

### Étape 4 : Envoyer sur GitHub
Je propulse ma sauvegarde locale vers mon compte GitHub en ligne.
```bash
git push
```

---

## 🛠️ EN CAS DE PROBLÈME (LA MACHINE À REMONTER LE TEMPS)

### Annuler les modifications non enregistrées sur un fichier
Je veux effacer mes erreurs du jour sur un fichier et le remettre EXACTEMENT dans l'état de mon dernier commit.
```bash
git checkout -- user/pages/03.spectacles/default.fr.md
```

### Consulter l'historique de mes sauvegardes
Je veux voir la liste chronologique de tous les commits effectués sur mon site.
```bash
git log --oneline
```
* Note : Affiche une liste compacte. Pour quitter cet écran et revenir au terminal, j'appuie sur la touche 'q'.

---

## 🔒 ASTUCE : RETENIR MON TOKEN VERT SUR DEBIAN
Pour éviter que Git ne me demande mon pseudo "pidlas" et mon Token vert "ghp_..." à chaque fois que je fais un "git push", je tape cette commande UNE SEULE FOIS dans mon terminal :
```bash
git config --global credential.helper store
```
*(Au prochain git push, je saisis mes identifiants une dernière fois et Debian les mémorisera pour toujours).*

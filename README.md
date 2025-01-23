# TP-Symfony

## Description des Pages

### Page d'Accueil (Liste des Véhicules)
- Affichage de tous les véhicules disponibles
- Chaque véhicule affiche : marque, modèle, prix par jour et disponibilité
- Les administrateurs ont accès aux boutons modifier et supprimer

### Page Détail du Véhicule
- Informations détaillées du véhicule
- Section commentaires des anciens locataires
- Bouton pour accéder au calendrier des disponibilités
- Bouton de réservation si l'utilisateur est connecté
- Les utilisateurs peuvent commenter uniquement s'ils ont déjà loué le véhicule

### Calendrier des Disponibilités
- Vue mensuelle avec les dates disponibles (vert) et indisponibles (rouge)
- Sélection d'un véhicule via un menu déroulant
- Bouton de réservation rapide qui redirige vers le formulaire de réservation

### Page de Réservation
- Formulaire de réservation avec :
  - Sélection du véhicule (pré-rempli si venant du calendrier)
  - Choix des dates de début et fin
  - Calcul automatique du prix total
- Vérification automatique des disponibilités

### Mes Réservations
- Liste de toutes les réservations de l'utilisateur
- Statut de chaque réservation (En attente, Confirmée, Terminée, Annulée)
- Possibilité d'annuler une réservation en cours
- Les administrateurs voient toutes les réservations et peuvent changer leur statut

### Espace Administrateur
- Gestion des véhicules (Ajout, Modification, Suppression)
- Gestion des réservations (Changement de statut)
- Modération des commentaires (Suppression possible)

### Authentification
- Page de connexion
- Page d'inscription
- Déconnexion

## Statuts des Réservations
- EN_ATTENTE : Nouvelle réservation
- CONFIRMEE : Validée par l'administrateur
- TERMINEE : Location terminée
- ANNULEE : Réservation annulée

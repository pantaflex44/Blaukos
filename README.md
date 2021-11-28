# <img align="left" width="32" src="./public/images/logo32.png">&nbsp;&nbsp;Blaukos

## Micro Framwork PHP 7+

+ Licence [MIT](LICENSE.txt)
+ Copyright (c)2021 Christophe LEMOINE
+ Version 0.1.0 (en cours de développement)
+ Projet initié le 23 octobre 2021


+ <u>Auteur</u>: Christophe LEMOINE (<pantaflex@hotmail.fr>)
+ <u>Github</u>: https://github.com/pantaflex44/Blaukos
  <br>
  <br>

### Fonctionnalités

**Framework**

- Configuration par fichier d'environement (.env - renomer le fichier .env.sample en .env)
- Gestion des paramètres utilisateurs
- Gestion des traductions
- Mode DEBUG
- 2 modes d'utilisations: Web et/ou API
- Système de Routage maison intégré
- Système d'annotations maison basé sur Docbook (Routes / ORM / Enums)
- Développé sur le modèle MVC (Models / Views / Controllers)
- Aide à l'utilisation des formulaires
- Sécurisation des formulaires par jetons CSRF
- Protection anti-flood des pages du site
- ...
  <br>

**Moteur de template**

- Utilisation du moteur de rendu Twig
- Mise en cache
- Filtres et fonctions personnalisées
- Minification de la sortie HTML avant envoie aux naviguateurs
- Minification des feuilles CSS et scripts Javascript
- ...
  <br>

**Base de données**

- Compatible MySQL et SqLite (drivers indépendants supportés par l'extension PDO)
- Micro ORM (Tto) basic simplifiant l'utilisation du CRUD et reposant sur les annotations DocBook
- Protection XSS
- ...
  <br>

**Authentification**

- Gestion complète des utilisateurs avec gestion des roles
- Séparation des procedures de connexions et d'enregistrements
- Sécurisation des connexions par jetons json web (JWT : Json Web Tokens)
- Gestion extensibles des profiles
- Accès à un tableau de bord extensible
- Utilisation ou non du javascript pour dynamiser l'enregistrement et l'authentification
- Système de réinitialisation du mot de passe en cas d'oublie
- ...
  <br>
# Base de Connaissances


**Pour utiliser cette base de connaissances, il faudra suivre toutes les étapes de ce README.**
---
Commencez par cloner le repo quelque part où vous voulez l'héberger
```
git clone git@github.com:ArthyStone/Base-de-Connaissances.git
```
Et vu que le projet utilise vendor, il vous faudra effectuer cette commande
```
composer install
```
Il est important que vous créiez un config.json à la racine de la base de connaissances (à côté des .gitignore, composer.json/.lock et dossiers vendor, src, public puis views)
votre config.json devra ressembler à ça:
```JSON
{
    "database": {
        "host": "",
        "dbname": "",
        "username": "",
        "password": ""
    }
}
```
Si vous avez créé une base de données POSTGRESQL, utilisez les commandes suivantes pour créer les tables avec les clés nécessaires, vous pourrez utiliser la bdc sans plus ample modification.
Au contraire si vous utilisez un autre type de bdd, vous devrez créer les tables vous-même et modifier toutes les requêtes dans `src\Database.php`, pour les adapter à ce que vous utilisez.
Remplacez bien pgsql par ce que vous utilisez, ligne 13 et ensuite il restera 13 autres champs à adapter.
Si vous vivez dans un fuseau horaire autre que celui de l'Europe, modifiez aussi la ligne 25 pour mettre votre propre fuseau !

- Tables :
```SQL
CREATE TABLE kb.users (
    user_id serial NOT NULL,
    user_name character varying(50) NOT NULL,
    created timestamp with time zone NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id)
);

CREATE TABLE kb.articles (
    article_id serial NOT NULL,
    user_id serial NOT NULL,
    created timestamp with time zone NOT NULL DEFAULT now(),
    modified timestamp with time zone NOT NULL DEFAULT now(),
    title text NOT NULL,
    text text NOT NULL,
    PRIMARY KEY (article_id),
    CONSTRAINT fk_user_id FOREIGN KEY (user_id)
        REFERENCES kb.users (user_id) MATCH SIMPLE
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
        NOT VALID
);

CREATE TABLE kb.tags (
    tag_id serial NOT NULL,
    text text NOT NULL,
    PRIMARY KEY (tag_id)
);

CREATE TABLE kb.tag_links (
    link_id serial NOT NULL,
    tag_id serial NOT NULL,
    article_id serial NOT NULL,
    PRIMARY KEY (link_id),
    CONSTRAINT uc_tag_id_article_id UNIQUE (tag_id, article_id),
    CONSTRAINT fk_tag_id FOREIGN KEY (tag_id)
        REFERENCES kb.tags (tag_id) MATCH SIMPLE
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
        NOT VALID,
    CONSTRAINT fk_article_id FOREIGN KEY (article_id)
        REFERENCES kb.articles (article_id) MATCH SIMPLE
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
        NOT VALID
);
```

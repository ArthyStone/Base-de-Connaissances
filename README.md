# Base de Connaissances
pour le moment il n'y a que la fonction d'édition de fiches préexistantes.



- Tables :

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



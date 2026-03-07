<?php
declare(strict_types=1);

namespace Src;
use PDO;
class Database {
    private static $pdo = null;

    private static function getConnection() {
        $config = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                "pgsql:host=".$config['database']['host'].";dbname=".$config['database']['dbname'],
                $config['database']['username'],
                $config['database']['password']
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }
    private static function prepare($sql) {
        return self::getConnection()->prepare($sql);
    }
    private static function normalizeDate($date) {
        return date('d/m/Y H:i', strtotime($date));
    }

    public static function listTags() {
        $stmt = self::prepare("SELECT tag_id, text FROM kb.tags");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function listArticles() {
        $stmt = self::prepare("SELECT a.article_id, u.user_name as creator, a.title, a.created, a.modified, string_agg(t.text, ',') AS tags FROM kb.articles a LEFT JOIN kb.tag_links tl ON a.article_id = tl.article_id LEFT JOIN kb.tags t ON tl.tag_id = t.tag_id LEFT JOIN kb.users u ON a.user_id = u.user_id GROUP BY a.article_id, a.user_id, a.title, a.created, a.modified, u.user_name ORDER BY a.modified DESC;");
        $stmt->execute();
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($articles as &$article) { // & pour modifier directement les éléments du tableau, au lieu de créer des variables copies
            $article['created'] = self::normalizeDate($article['created']);
            $article['modified'] = self::normalizeDate($article['modified']);
            unset($article); // visiblement, sans unset, des documents en remplacent d'autres.
        }
        return $articles;
    }
    public static function getArticle($id) {
        $stmt = self::prepare("SELECT a.title, a.created, a.modified, a.text, string_agg(t.text, ',') AS tags, u.user_name AS creator FROM kb.articles a LEFT JOIN kb.tag_links tl ON a.article_id = tl.article_id LEFT JOIN kb.tags t ON tl.tag_id = t.tag_id LEFT JOIN kb.users u ON a.user_id = u.user_id WHERE a.article_id = :id GROUP BY a.article_id, a.title, a.created, a.modified, a.text, u.user_name;");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($article) {
            $article['created'] = self::normalizeDate($article['created']);
            $article['modified'] = self::normalizeDate($article['modified']);
        }
        return $article;
    }

    
    public static function editDocument($id, $text, $title, $tags) {
        $req = "UPDATE kb.articles SET text = :text, title = :title, modified = NOW() WHERE article_id = :id";
        $stmt = Database::prepare($req);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':text', $text, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $stmt = Database::prepare("DELETE FROM kb.tag_links WHERE article_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        foreach($tags as $tagId) {
            $stmt = Database::prepare("INSERT INTO kb.tag_links (article_id, tag_id) VALUES (:article_id, :tag_id)");
            $stmt->bindParam(':article_id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
            $stmt->execute();
        }
        return true;
    }
}
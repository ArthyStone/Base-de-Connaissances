<?php
declare(strict_types=1);

namespace Src;
use Src\Database;
use PDO;
class ViewController {
    public function render($view, $data = []) {
        switch($view) {
            case 'main':
                $req = "SELECT a.article_id, u.user_name as creator, a.title, a.created, a.modified, string_agg(t.text, ',') AS tags FROM kb.articles a LEFT JOIN kb.tag_links tl ON a.article_id = tl.article_id LEFT JOIN kb.tags t ON tl.tag_id = t.tag_id LEFT JOIN kb.users u ON a.user_id = u.user_id GROUP BY a.article_id, a.user_id, a.title, a.created, a.modified, u.user_name ORDER BY a.modified DESC;";
                break;
            case 'document':
                $req = "SELECT a.title, a.created, a.modified, a.text, string_agg(t.text, ',') AS tags, u.user_name AS creator FROM kb.articles a LEFT JOIN kb.tag_links tl ON a.article_id = tl.article_id LEFT JOIN kb.tags t ON tl.tag_id = t.tag_id LEFT JOIN kb.users u ON a.user_id = u.user_id WHERE a.article_id = " . $data['documentId'] . " GROUP BY a.article_id, a.title, a.created, a.modified, a.text, u.user_name;";
                break;
            default:
                echo "Vue non trouvée.";
        }
        $stmt = Database::prepare($req);
        $stmt->execute();
        $data['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = Database::prepare("SELECT tag_id, text FROM kb.tags");
        $stmt->execute();
        $data['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        extract($data);
        include __DIR__ . '/../views/' . $view . '.php';
    }
}
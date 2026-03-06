<?php
declare(strict_types=1);

namespace Src;
use Src\Database;
use PDO;
class DocumentController {
    public function editDocument($id, $text, $title, $tags) {
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
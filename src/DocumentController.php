<?php
declare(strict_types=1);

namespace Src;
use Src\Database;
use PDO;
class DocumentController {
    public function editDocument($id, $text) {
        $req = "UPDATE kb.articles SET text = :text, modified = NOW() WHERE article_id = :id";
        $stmt = Database::prepare($req);
        $stmt->bindParam(':text', $text, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
}
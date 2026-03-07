<?php
declare(strict_types=1);

namespace Src;
use Src\Database;
use PDO;
class ViewController {
    public static function render($view, $data = []) {
        switch($view) {
            case 'main':
                $data['documents'] = Database::listArticles();
                break;
            case 'document':
                $data['document'] = Database::getArticle($data['documentId']);
                break;
            default:
                echo "Vue non trouvée.";
        }
        $data['tags'] = Database::listTags();
        extract($data);
        include __DIR__ . '/../views/' . $view . '.php';
    }
}
<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use Src\Database;
use Src\ViewController;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    switch ($path) {
        case '/':
            ViewController::render('main', ['data' => []]);
            break;
        case '/document':
            ViewController::render('document', ['documentId' => $_GET['id'] ?? null]);
            break;
        case '/create':
            ViewController::render('document', ['documentId' => null]);
            break;
        default:
            http_response_code(404);
            echo "Page non trouvée.";
    }
}
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    switch ($path) {
        case '/document/edit':
                $input = json_decode(file_get_contents('php://input'), true);
                $documentId = $input['documentId'] ?? null;
                $title = $input['title'] ?? null;
                $text = $input['text'] ?? null;
                $tags = $input['tags'] ?? null;
                if(!$documentId) {
                    http_response_code(400);
                    echo "ID du document manquant.";
                    exit;
                }
                if(!$title) {
                    http_response_code(400);
                    echo "Titre du document manquant.";
                    exit;
                }
                if(!$text) {
                    http_response_code(400);
                    echo "Contenu du document manquant.";
                    exit;
                }
                if(Database::editDocument((int)$documentId, $text, $title, $tags)) {
                    echo "Document mis à jour avec succès.";
                } else {
                    http_response_code(500);
                    echo "Erreur lors de la mise à jour du document.";
                }
            break;
        default:
            http_response_code(404);
            echo "Page non trouvée.";
    }
}
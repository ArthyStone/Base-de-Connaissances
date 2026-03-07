<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use Src\Session;
use Src\Database;
use Src\ViewController;
Session::start();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    switch ($path) {
        case '/':
            ViewController::render('main', ['data' => []]);
            break;
        case '/document':
            if(!($_GET['id'] ?? null)) {
                header('Location: /');
                exit;
            }
            ViewController::render('document', ['documentId' => $_GET['id'] ?? null]);
            break;
        case '/create':
            if(!Session::has('user_id')) {
                header('Location: /login');
                exit;
            }
            ViewController::render('document', ['documentId' => $_GET['id'] ?? null]);
            break;
        case '/login':
            ViewController::render('login');
            break;
        case '/logout':
            Session::destroy();
            header('Location: /');
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
        case '/document/create':
            $input = json_decode(file_get_contents('php://input'), true);
            $user_id = $input['user_id'] ?? null;
            $title = $input['title'] ?? null;
            $text = $input['text'] ?? null;
            $tags = $input['tags'] ?? null;
            if(!$user_id) {
                http_response_code(400);
                echo "Créateur du document manquant.";
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
            $documentId = Database::createDocument($user_id, $text, $title, $tags);
            if($documentId) {
                echo json_encode(["documentId" => $documentId]);
            } else {
                http_response_code(500);
                echo "Erreur lors de la création du document.";
            }
            break;
        case '/document/delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $documentId = $input['documentId'] ?? null;
            if(!$documentId) {
                http_response_code(400);
                echo "Identifiant du document manquant.";
                exit;
            }
            if(Database::deleteDocument($documentId)) {
                echo "Document supprimé avec succès.";
            } else {
                http_response_code(500);
                echo "Erreur lors de la suppression du document.";
            }
            break;
        case '/login':
            $input = json_decode(file_get_contents('php://input'), true);
            $user_id = $input['user_id'] ?? null;
            if(!$user_id) {
                http_response_code(400);
                echo "user_id manquant.";
                exit;
            }
            Session::set('user_id', (int) $user_id);
            $username = Database::getUser($user_id);
            Session::set('user_name', (string) $username);
            break;
        default:
            http_response_code(404);
            echo "Page non trouvée.";
    }
}
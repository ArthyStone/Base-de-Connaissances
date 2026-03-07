<?php
declare(strict_types=1);

namespace Src;
use Src\Session;
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
                if(Session::has('user_id')) {
                    $data['Session']['user_id'] = Session::get('user_id');
                    $data['Session']['user_name'] = Session::get('user_name');
                }
                break;
            case 'login':
                $data['secure'] = Database::hasPasswordField();
                if($data['secure']) {
                    // authentification
                    $data['error'] = "Salut, ce repo n'a pas de système d'authentification, tu peux en créer un si tu veux, ou alors tu peux directement supprimer le champ password dans la table users, ou aller dans Database.php et modifier la fonction hasPasswordField(), met juste un return false; et tu auras une liste d'utilisateurs à la place de l'authentification.Attention ! Vu que le projet ne visait pas à utiliser des mots de passe, il manque des sécurités (par exemple, on peut supprimer un document sans être connecté)";
                } else {
                    // pas d'authentification, liste d'utilisateurs

                    // si quelqu'un utilise ce repo, si tu veux mettre une authentification, il faut un champ qui s'appelle "password" dans la table "users".
                    // Tu peux aussi faire une authentification à ta sauce ou via OAuth, mais ça sort du cadre de ce projet.
                    // en tout cas, si tu veux modifier le système d'auth, il faut modifier Database.php, index.php et potentiellement login.php
                    // pour le fonctionnement actuel, si la table "users" contient un champ "password", on fait une authentification basique, sinon on affiche la liste des utilisateurs dans un select.
                    // l'utilisateur choisit son profil, zéro sécurité mais dans mon cas de figure c'est un projet local donc j'ai pas besoin de plus.
                    $data['users'] = Database::listUsers();
                }
                break;
            default:
                echo "Vue non trouvée.";
        }
        $data['tags'] = Database::listTags();
        extract($data);
        include __DIR__ . '/../views/' . $view . '.php';
    }
}
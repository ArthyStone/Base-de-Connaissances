<?php declare(strict_types=1); ?>
<html>
<head>
    <title>Base de Connaissances</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="options">
    </div>
    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>id</th>
                    <th>titre</th>
                    <th>tags</th>
                    <th>créateur</th>
                    <th>créé le</th>
                    <th>modifié le</th>
                </tr>
            </thead>
            <tbody>
<?php
// aura besoin de données de la base de données
// il recevra une grande liste de documents et les affichera
if(isset($documents) && is_array($documents) && count($documents) > 0){
    foreach($documents as $document){
        echo "<tr>";
        echo "<td>" . $document['article_id'] . "</td>";
        echo "<td><a href='document?id=" . $document['article_id'] . "'>" . htmlspecialchars($document['title']) . "</a></td>";
        echo "<td>" . htmlspecialchars($document['tags']) . "</td>";
        echo "<td>" . $document['creator'] . "</td>";
        echo "<td>" . htmlspecialchars($document['created']) . "</td>";
        echo "<td>" . htmlspecialchars($document['modified']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>Aucun document trouvé.</td></tr>";
}
?>
            </tbody>
        </table>
    </div>
</body>
</html>
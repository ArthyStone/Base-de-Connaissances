<?php declare(strict_types=1); ?>
<html>
<head>
    <title>Base de Connaissances</title>
    <link rel="stylesheet" href="doc.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php
    if(isset($documents)) {
        $document = $documents[0];
    }
    ?>
    <div class="other">
    </div>
    <div class="document-container">
        <div class="document-header">
            <span><?= htmlspecialchars($document['title'] ?? 'Document Inconnu') ?></span>
            <span><?= htmlspecialchars($document['tags'] ?? 'Aucun tag') ?></span>
        </div>
        <div class="buttons-container">
            <button id="saveButton"><i class="fa-solid fa-floppy-disk"></i></button>
            <button id="toggleButton"><i class="fa-solid fa-pen-to-square"></i></button>
        </div>
        <div class="document-content">
            <textarea id="editor" class="disabled"><?= $document['text'] ?? '' ?></textarea>
            <div id="preview"></div>
        </div>
        <div class="document-meta">
            <p><strong>Créateur:</strong> <?= $document['creator'] ?? 'Inconnu' ?></p>
            <p><strong>Créé le:</strong> <?= htmlspecialchars($document['created'] ?? 'Inconnu') ?></p>
            <p><strong>Modifié le:</strong> <?= htmlspecialchars($document['modified'] ?? 'Inconnu') ?></p>
        </div>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/markdown-it@14/dist/markdown-it.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script src="scripts/markdown&highlight.js"></script>
<script>

const body = document.querySelector("body");
const editor = document.getElementById("editor");
const preview = document.getElementById("preview");
const toggleButton = document.getElementById("toggleButton");
const saveButton = document.getElementById("saveButton");

// Fonction pour auto-ajuster la hauteur du textarea
function autoResizeEditor() {
    const scrollHeight = editor.scrollHeight;
    editor.style.height = scrollHeight + 'px';
}

function renderPreview() {
    const html = md.render(editor.value);
    preview.innerHTML = DOMPurify.sanitize(html);
    
    // Appliquer le highlighting à tous les code blocks
    preview.querySelectorAll('pre code').forEach((block) => {
        if (!block.classList.contains('hljs')) {
            hljs.highlightElement(block);
        }
    });
}

renderPreview();

// Ajuster la hauteur initiale après un petit délai
setTimeout(() => {
    autoResizeEditor();
}, 50);

editor.addEventListener("input", () => {
    renderPreview();
    autoResizeEditor();
});
editor.addEventListener("focus", autoResizeEditor);

toggleButton.addEventListener("click", () => {
    const isEditing = !editor.classList.contains("disabled");
    editor.classList.toggle("disabled");
    preview.classList.toggle("disabled");
    toggleButton.innerHTML = isEditing ? '<i class="fa-solid fa-pen-to-square"></i>' : '<i class="fa-solid fa-eye"></i>';
    autoResizeEditor();
});
saveButton.addEventListener("click", () => {
    const content = editor.value;
    const documentId = <?= json_encode($data['documentId'] ?? null) ?>;
    
    if (!documentId) {
        alert("ID du document manquant.");
        return;
    }
    
    fetch(`document/edit`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ text: content, documentId })
    })
    .then(response => {
        console.log(response);
        if (response.ok) {
            if(preview.classList.contains("disabled")) {
                editor.classList.toggle("disabled");
                preview.classList.toggle("disabled");
                toggleButton.textContent = "Éditer";
                autoResizeEditor();
            }
            const lastBgColor = body.style.backgroundColor;
            body.style.backgroundColor = "#d3ffde";
            setTimeout(() => {
                body.style.backgroundColor = lastBgColor;
            }, 1000);
        } else {
            alert("Erreur lors de l'enregistrement du document.");
        }
    })
    .catch(error => {
        console.error("Erreur:", error);
        alert("Erreur lors de l'enregistrement du document.");
    });
});
</script>
</html>
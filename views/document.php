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
            <input type="text" id="titleInput" class="disabled" value="<?= htmlspecialchars($document['title'] ?? 'Document Inconnu') ?>">
            <span id="titleShow"><?= htmlspecialchars($document['title'] ?? 'Document Inconnu') ?></span>
            <span class="tags">
                <?php foreach(explode(',', $document['tags'] ?? 'Aucun tag') as $tag): ?>
                    <span><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </span>
            <form id="tagsSelect" class="disabled">
                <?php foreach($tags as $tag): ?>
                    <span>
                        <input type="checkbox" value="<?= $tag['tag_id'] ?>" id="tag_<?= $tag['tag_id'] ?>" <?= in_array($tag['text'], explode(',', $document['tags'] ?? '')) ? 'checked' : '' ?>>
                        <label for="tag_<?= $tag['tag_id'] ?>"><?= htmlspecialchars($tag['text']) ?></label>
                    </span>
                <?php endforeach; ?>
            </form>
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
const titleShow = document.getElementById("titleShow");
const titleInput = document.getElementById("titleInput");
const tagsShow = document.querySelector(".document-header .tags");
const tagsSelect = document.getElementById("tagsSelect");
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
}, 100);

editor.addEventListener("input", () => {
    renderPreview();
    autoResizeEditor();
});
editor.addEventListener("focus", autoResizeEditor);

toggleButton.addEventListener("click", () => {
    const isEditing = !editor.classList.contains("disabled");
    editor.classList.toggle("disabled");
    preview.classList.toggle("disabled")
    tagsShow.classList.toggle("disabled");
    titleShow.classList.toggle("disabled");
    titleInput.classList.toggle("disabled");
    tagsSelect.classList.toggle("disabled");
    toggleButton.innerHTML = isEditing ? '<i class="fa-solid fa-pen-to-square"></i>' : '<i class="fa-solid fa-eye"></i>';
    autoResizeEditor();
});
titleInput.addEventListener("input", () => {
    titleShow.textContent = titleInput.value;
});
document.querySelectorAll("#tagsSelect input[type='checkbox']").forEach(checkbox => {
    checkbox.addEventListener("change", () => {
        const selectedTags = Array.from(tagsSelect.querySelectorAll("input[type='checkbox']:checked")).map(cb => cb.nextElementSibling.textContent);
        tagsShow.innerHTML = selectedTags.map(tag => `<span>${tag}</span>`).join('');
    });
});
saveButton.addEventListener("click", () => {
    const content = editor.value;
    const documentId = <?= json_encode($data['documentId'] ?? null) ?>;
    const tags = Array.from(tagsSelect.querySelectorAll("input[type='checkbox']:checked")).map(checkbox => parseInt(checkbox.value));
    
    if (!documentId) {
        alert("ID du document manquant.");
        return;
    }
    
    fetch(`document/edit`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ text: content, documentId, title: titleInput.value, tags })
    })
    .then(response => {
        console.log(response);
        if (response.ok) {
            if(preview.classList.contains("disabled")) {
                editor.classList.toggle("disabled");
                preview.classList.toggle("disabled")
                tagsShow.classList.toggle("disabled");
                titleShow.classList.toggle("disabled");
                titleInput.classList.toggle("disabled");
                tagsSelect.classList.toggle("disabled");
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
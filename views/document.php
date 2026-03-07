<?php declare(strict_types=1); ?>
<html>
<head>
    <title>Base de Connaissances</title>
    <link rel="stylesheet" href="styles/doc.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="other">
        <a href="/" class="back-button"><i class="fa-solid fa-arrow-left"></i> Retour à la liste</a>
    </div>
    <div class="document-container">
        <div class="document-header">
            <input type="text" id="titleInput" class="disabled" value="<?= htmlspecialchars($document['title'] ?? 'Nouveau document') ?>">
            <span id="titleShow"><?= htmlspecialchars($document['title'] ?? 'Nouveau document') ?></span>
            <span class="tags">
                <?php foreach(explode(',', $document['tags'] ?? 'Aucun tag') as $tag): ?>
                    <span><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </span>
            <div id="tagsSelect" class="disabled">
                <?php foreach($tags as $tag): ?>
                    <span>
                        <input type="checkbox" value="<?= $tag['tag_id'] ?>" id="tag_<?= $tag['tag_id'] ?>" <?= in_array($tag['text'], explode(',', $document['tags'] ?? '')) ? 'checked' : '' ?>>
                        <label for="tag_<?= $tag['tag_id'] ?>"><?= htmlspecialchars($tag['text']) ?></label>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="buttons-container">
            <button id="importButton" title="Importer une connaissance" onclick="importKnowledge();"><i class="fa-solid fa-file-import"></i></button>
            <button id="downloadButton" title="Télécharger cette connaissance"><i class="fa-solid fa-download"></i></button>
            <button id="deleteButton" title="Supprimer cette connaissance"><i class="fa-solid fa-trash-can"></i></button>
            <button id="saveButton" title="Sauvegarder cette connaissance"><i class="fa-solid fa-floppy-disk"></i></button>
            <button id="toggleButton"><i class="fa-solid fa-pen-to-square"></i></button>
        </div>
        <div class="markdown-toolbar disabled" id="markdownToolbar">
            <button type="button" data-format="bold" title="Gras (Ctrl+B)"><i class="fa-solid fa-bold"></i></button>
            <button type="button" data-format="italic" title="Italique (Ctrl+I)"><i class="fa-solid fa-italic"></i></button>
            <button type="button" data-format="underline" title="Souligné (Ctrl+U)"><i class="fa-solid fa-underline"></i></button>
            <button type="button" data-format="strike" title="Barré"><i class="fa-solid fa-strikethrough"></i></button>
            <div class="toolbar-separator"></div>
            <button type="button" data-format="h1" title="Titre H1"><i class="fa-solid fa-heading"></i> 1</button>
            <button type="button" data-format="h2" title="Titre H2"><i class="fa-solid fa-heading"></i> 2</button>
            <button type="button" data-format="h3" title="Titre H3"><i class="fa-solid fa-heading"></i> 3</button>
            <div class="toolbar-separator"></div>
            <button type="button" data-format="ul" title="Liste à puces"><i class="fa-solid fa-list-ul"></i></button>
            <button type="button" data-format="ol" title="Liste numérotée"><i class="fa-solid fa-list-ol"></i></button>
            <div class="toolbar-separator"></div>
            <button type="button" data-format="code" title="Code en ligne"><i class="fa-solid fa-terminal"></i></button>
            <button type="button" data-format="codeblock" title="Bloc de code"><i class="fa-solid fa-code"></i></button>
            <div class="toolbar-separator"></div>
            <button type="button" data-format="blockquote" title="Citation"><i class="fa-solid fa-quote-left"></i></button>
            <button type="button" data-format="link" title="Lien (Ctrl+K)"><i class="fa-solid fa-link"></i></button>
            <button type="button" data-format="image" title="Image"><i class="fa-solid fa-image"></i></button>
            <div class="toolbar-separator"></div>
            <button type="button" data-format="hr" title="Séparation"><i class="fa-solid fa-minus"></i></button>
        </div>
        <div class="document-content">
            <textarea id="editor" class="disabled" spellcheck="false" autocorrect="off" autocapitalize="off"><?= $document['text'] ?? '# Nouveau document' ?></textarea>
            <div id="preview"></div>
        </div>
        <div class="import-zone disabled">
            <div id="dropZone">Glissez-déposez vos fichiers ici</div>
            <input type="file" id="fileInput" accept=".md,.txt" style="display: none;">
        </div>
        <div class="document-meta">
            <p><strong>Créateur:</strong> <?= $document['creator'] ?? $Session['user_name'] ?? 'Inconnu' ?></p>
            <p><strong>Créé:</strong> <?= htmlspecialchars($document['created'] ?? 'Maintenant') ?></p>
            <p><strong>Modifié:</strong> <?= htmlspecialchars($document['modified'] ?? 'Maintenant') ?></p>
        </div>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/markdown-it@14/dist/markdown-it.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script src="scripts/markdown&highlight.js"></script>
<script src="scripts/toolbar.js"></script>
<script>

const body = document.querySelector("body");
const editor = document.getElementById("editor");
const preview = document.getElementById("preview");
const dropZone = document.getElementById("dropZone");
const titleShow = document.getElementById("titleShow");
const fileInput = document.getElementById("fileInput");
const titleInput = document.getElementById("titleInput");
const tagsSelect = document.getElementById("tagsSelect");
const saveButton = document.getElementById("saveButton");
const importZone = document.querySelector(".import-zone");
const deleteButton = document.getElementById("deleteButton");
const toggleButton = document.getElementById("toggleButton");
const tagsShow = document.querySelector(".document-header .tags");
const markdownToolbar = document.getElementById("markdownToolbar");
const documentContent = document.querySelector(".document-content");
let didEditorChange = false;

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
    didEditorChange = true;
    renderPreview();
    autoResizeEditor();
});
editor.addEventListener("focus", autoResizeEditor);

toggleButton.addEventListener("click", () => {
    setTimeout(() => {
        autoResizeEditor();
    }, 50);
    const isEditing = !editor.classList.contains("disabled");
    editor.classList.toggle("disabled");
    preview.classList.toggle("disabled")
    tagsShow.classList.toggle("disabled");
    titleShow.classList.toggle("disabled");
    titleInput.classList.toggle("disabled");
    tagsSelect.classList.toggle("disabled");
    markdownToolbar.classList.toggle("disabled");
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
    const documentId = <?= json_encode($documentId ?? null) ?>;
    const tags = Array.from(tagsSelect.querySelectorAll("input[type='checkbox']:checked")).map(checkbox => parseInt(checkbox.value));
    
    <?php if ($documentId): ?>
    fetch("document/edit", {
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
                markdownToolbar.classList.toggle("disabled");
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
    <?php else: ?>
    fetch("document/create", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ user_id: <?= $Session['user_id'] ?>,text: content, title: titleInput.value, tags })
    })
    .then(response => {
        if(response.ok) {
            console.log(response);
            return response.json();
        } else {
            throw new Error("Erreur lors de la création du document.");
        }
    }).then(data => {
        if(data && data.documentId) {
            didEditorChange = false;
            window.location.href = `/document?id=${data.documentId}`;
        } else {
            throw new Error("ID du document créé manquant dans la réponse.");
        }
    })
    <?php endif; ?>
    .catch(error => {
        console.error("Erreur:", error);
        alert("Erreur lors de l'enregistrement du document.");
    });
});

deleteButton.addEventListener("click", () => {
    if(!confirm('Es-tu sûr de vouloir supprimer ce document ?\n(Cette action est irréversible)')) return;
    <?php if ($documentId): ?>
    const documentId = <?= json_encode($documentId ?? null) ?>;
    const tags = Array.from(tagsSelect.querySelectorAll("input[type='checkbox']:checked")).map(checkbox => parseInt(checkbox.value));
    fetch("document/delete", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ documentId })
    })
    .then(response => {
        console.log(response);
        if (response.ok) {
            didEditorChange = false;
            window.location.href = `/`;
            
        } else {
            alert("Erreur lors de la suppression du document.");
        }
    })
    .catch(error => {
        console.error("Erreur:", error);
        alert("Erreur lors de l'enregistrement du document.");
    });
    <?php else: ?>
    didEditorChange = false;
    window.location.href = `/`;
    <?php endif; ?>
});

window.addEventListener('beforeunload', (event) => {
  if (didEditorChange) {
    event.preventDefault();
    // Pour les anciens navigateurs
    event.returnValue = '';
  }
});

function importKnowledge() {
    documentContent.classList.toggle('disabled');
    importZone.classList.toggle('disabled');
    if(preview.classList.contains("disabled")) {
        editor.classList.toggle("disabled");
        preview.classList.toggle("disabled")
        tagsShow.classList.toggle("disabled");
        titleShow.classList.toggle("disabled");
        titleInput.classList.toggle("disabled");
        tagsSelect.classList.toggle("disabled");
        markdownToolbar.classList.toggle("disabled");
        toggleButton.textContent = "Éditer";
        autoResizeEditor();
    }
}

function download() {
    const fileName = titleInput.value + '.md';
    const fileContent = editor.value;
    const blob = new Blob([fileContent], { type: 'text/markdown' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

downloadButton.addEventListener('click', download);

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', e => {
    e.preventDefault();
    dropZone.classList.add('draggedOver');
});
dropZone.addEventListener('dragleave', e => {
    e.preventDefault();
    dropZone.classList.remove('draggedOver');
});
function handleFile(e) {
    e.preventDefault();
    dropZone.classList.remove('draggedOver');
    dropZone.style.border = '2px dashed #aaa';
    console.log(e);
    const files = e.type === 'drop' ? Array.from(e.dataTransfer.files) : Array.from(fileInput.files);
    if (files.length === 0) return;
    console.log(files[0]);
    const file = files[0];
    const reader = new FileReader();
    
    reader.onload = (event) => {
        const fileName = files[0].name.replace('.txt', '').replace('.md', '');
        const fileText = event.target.result;
        titleShow.textContent = fileName;
        titleInput.value = fileName;
        editor.value = fileText;
        renderPreview();
        autoResizeEditor();
        documentContent.classList.remove('disabled');
        importZone.classList.add('disabled');
    };
    reader.readAsText(file);
}
dropZone.addEventListener('drop', e => handleFile(e));
fileInput.addEventListener('change', e => handleFile(e));
</script>
</html>
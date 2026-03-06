/**
 * Gestion de la barre d'outils markdown, raccourcis clavier,
 * et mise en forme en direct.
 * (à la manière de Discord)
 */

// ─── Helpers insertion ───────────────────────────────────────────────────────

function wrapSelection(textarea, before, after = before, placeholder = '') {
    const start    = textarea.selectionStart;
    const end      = textarea.selectionEnd;
    const value    = textarea.value;
    const selected = value.slice(start, end) || placeholder;

    const alreadyBefore = value.slice(start - before.length, start) === before;
    const alreadyAfter  = value.slice(end, end + after.length)       === after;

    let newText, newStart, newEnd;
    if (alreadyBefore && alreadyAfter) {
        newText  = value.slice(0, start - before.length) + selected + value.slice(end + after.length);
        newStart = start - before.length;
        newEnd   = newStart + selected.length;
    } else {
        newText  = value.slice(0, start) + before + selected + after + value.slice(end);
        newStart = start + before.length;
        newEnd   = newStart + selected.length;
    }

    applyText(textarea, newText, newStart, newEnd);
}

function prefixLines(textarea, prefix, placeholder = 'Texte') {
    const start     = textarea.selectionStart;
    const end       = textarea.selectionEnd;
    const value     = textarea.value;
    const lineStart = value.lastIndexOf('\n', start - 1) + 1;
    const lineEnd   = value.indexOf('\n', end);
    const lastChar  = lineEnd === -1 ? value.length : lineEnd;
    const lines     = value.slice(lineStart, lastChar).split('\n');
    const allPrefixed = lines.every(l => l.startsWith(prefix));
    const newLines  = allPrefixed
        ? lines.map(l => l.slice(prefix.length))
        : lines.map(l => l.startsWith(prefix) ? l : prefix + (l || placeholder));
    const newBlock  = newLines.join('\n');
    const newText   = value.slice(0, lineStart) + newBlock + value.slice(lastChar);
    applyText(textarea, newText, lineStart, lineStart + newBlock.length);
}

function insertCodeBlock(textarea) {
    const start    = textarea.selectionStart;
    const end      = textarea.selectionEnd;
    const value    = textarea.value;
    const selected = value.slice(start, end) || 'code';
    const block    = '```\n' + selected + '\n```';
    const newText  = value.slice(0, start) + block + value.slice(end);
    applyText(textarea, newText, start + 4, start + 4 + selected.length);
}

function insertLink(textarea) {
    const start    = textarea.selectionStart;
    const end      = textarea.selectionEnd;
    const value    = textarea.value;
    const selected = value.slice(start, end) || 'texte';
    const link     = `[${selected}](url)`;
    const newText  = value.slice(0, start) + link + value.slice(end);
    const urlStart = start + selected.length + 3;
    applyText(textarea, newText, urlStart, urlStart + 3);
}

function insertImage(textarea) {
    const start    = textarea.selectionStart;
    const end      = textarea.selectionEnd;
    const value    = textarea.value;
    const selected = value.slice(start, end) || 'description';
    const img      = `![${selected}](url)`;
    const newText  = value.slice(0, start) + img + value.slice(end);
    const urlStart = start + selected.length + 4;
    applyText(textarea, newText, urlStart, urlStart + 3);
}

/** Applique le texte en s'intégrant dans l'historique undo/redo natif. */
function applyText(textarea, newText, selStart, selEnd) {
    textarea.focus();
    textarea.select();
    const success = document.execCommand('insertText', false, newText);
    if (!success || textarea.value !== newText) {
        textarea.value = newText;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }
    textarea.setSelectionRange(selStart, selEnd);
}

// ─── Map des actions ─────────────────────────────────────────────────────────

const FORMAT_ACTIONS = {
    bold       : (ta) => wrapSelection(ta, '**', '**', 'gras'),
    italic     : (ta) => wrapSelection(ta, '*', '*', 'italique'),
    underline  : (ta) => wrapSelection(ta, '_', '_', 'souligné'),
    strike     : (ta) => wrapSelection(ta, '~~', '~~', 'barré'),
    h1         : (ta) => prefixLines(ta, '# '),
    h2         : (ta) => prefixLines(ta, '## '),
    h3         : (ta) => prefixLines(ta, '### '),
    ul         : (ta) => prefixLines(ta, '- '),
    ol         : (ta) => prefixLines(ta, '1. '),
    code       : (ta) => wrapSelection(ta, '`', '`', 'code'),
    codeblock  : (ta) => insertCodeBlock(ta),
    blockquote : (ta) => prefixLines(ta, '> '),
    link       : (ta) => insertLink(ta),
    image      : (ta) => insertImage(ta),
    hr         : (ta) => {
        const pos    = ta.selectionStart;
        const val    = ta.value;
        const before = (pos > 0 && val[pos - 1] !== '\n') ? '\n' : '';
        const after  = (pos < val.length && val[pos] !== '\n') ? '\n' : '';
        const hr     = before + '---' + after;
        applyText(ta, val.slice(0, pos) + hr + val.slice(pos), pos + hr.length, pos + hr.length);
    },
};

// ─── Live highlighting ───────────────────────────────────────────────────────
//
// Une <div> miroir est superposé au textarea (pointer-events:none, z-index:1).
// Le textarea reçoit color:transparent donc son texte est invisible.
// La div affiche le même texte avec des <span> colorés.
// Le curseur du textarea reste visible via caret-color.
//
// Pour que les positions soient pixel perfect, la div miroir copie
// tous les styles typographiques et de boîte du textarea.
// Il utilise inset:0 pour couvrir le textarea border incluse, et
// les mêmes paddings → le texte tombe au même endroit.

const HIGHLIGHT_CSS = `
.editor-wrapper {
    position: relative;
}

/* Texte du textarea rendu transparent, curseur conservé */
#editor {
    position: relative;
    z-index: 2;
    color: transparent !important;
    -webkit-text-fill-color: transparent !important;
    caret-color: #abb2bf;
    background: transparent !important;
}

#editor::-webkit-selection,
#editor::-moz-selection {
    background: rgba(97, 175, 239, 0.2);
}

/* Div miroir */
#editor-highlight {
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    overflow: hidden;
    white-space: pre-wrap;
    word-wrap: break-word;
    color: #3a3a3a;
    /* border transparent pour garder le même box-model que le textarea */
    border-color: transparent;
    border-style: solid;
}

/* ── Styles des tokens markdown ── */

/* Gras : le contenu seul est en gras, les marqueurs ** sont atténués */
#editor-highlight .md-bold      { font-weight: bold; }
/* Italique */
#editor-highlight .md-italic    { font-style: italic; }
/* Souligné */
#editor-highlight .md-underline { text-decoration: underline; }
/* Barré */
#editor-highlight .md-strike    { text-decoration: line-through; }

/* Code inline */
#editor-highlight .md-code {
    color: rgb(228, 114, 123);
    background: rgba(224, 108, 117, 0.1);
    border-radius: 3px;
}

/* Marqueurs de syntaxe : visibles mais très atténués */
#editor-highlight .md-marker      { opacity: 1; color: #abb2bf; font-weight: bold; }
#editor-highlight .md-marker-code { opacity: 1; color: #e06c75; font-weight: bold; }
#editor-highlight .md-marker-cb   { opacity: 1; color: #006b05; font-weight: bold; }

/* Lignes dans un bloc de code */
#editor-highlight .md-codeblock-line { color: #98c379; }
`;

// ─── Échappement HTML ─────────────────────────────────────────────────────────

function escHtml(s) {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// ─── Highlight markdown ───────────────────────────────────────────────────────

function highlightMarkdown(raw) {
    // Sépare blocs de code et texte normal
    const CB_RE = /```([\w]*)\n?([\s\S]*?)```/g;
    const segments = [];
    let last = 0, m;

    while ((m = CB_RE.exec(raw)) !== null) {
        if (m.index > last) segments.push({ type: 'text', content: raw.slice(last, m.index) });
        segments.push({ type: 'codeblock', lang: m[1], body: m[2] });
        last = m.index + m[0].length;
    }
    if (last < raw.length) segments.push({ type: 'text', content: raw.slice(last) });

    let html = '';
    for (const seg of segments) {
        if (seg.type === 'codeblock') {
            const openTag = '```' + escHtml(seg.lang);
            const bodyHtml = (typeof hljs !== 'undefined')
                ? hljsCached(seg.lang, seg.body)
                : escHtml(seg.body);
            html += `<span class="md-marker-cb">${openTag}\n</span><span class="md-codeblock-wrap">${bodyHtml}</span><span class="md-marker-cb">\`\`\`</span>`;
        } else {
            html += highlightInline(seg.content);
        }
    }
    return html;
}

function highlightInline(text) {
    const RULES = [
        // Code inline (prioritaire, backticks multiples supportés)
        { re: /(`+)([\s\S]+?)\1/g,                  cls: 'md-code',      codeStyle: true },
        // Gras **…**
        { re: /(\*\*)((?:[^*]|\*(?!\*))+?)(\*\*)/g, cls: 'md-bold',      marker: 'md-marker' },
        // Italique *…* (après gras)
        { re: /(\*)((?:[^*\n])+?)(\*)/g,            cls: 'md-italic',    marker: 'md-marker' },
        // Souligné _…_
        { re: /(_)((?:[^_\n])+?)(_)/g,              cls: 'md-underline', marker: 'md-marker' },
        // Barré ~~…~~
        { re: /(~~)((?:[^~\n]|~(?!~))+?)(~~)/g,     cls: 'md-strike',    marker: 'md-marker' },
    ];

    let result    = '';
    let remaining = text;

    while (remaining.length > 0) {
        let bestMatch = null, bestIdx = Infinity, bestRule = null;

        for (const rule of RULES) {
            rule.re.lastIndex = 0;
            const hit = rule.re.exec(remaining);
            if (hit && hit.index < bestIdx) {
                bestIdx   = hit.index;
                bestMatch = hit;
                bestRule  = rule;
            }
        }

        if (!bestMatch) {
            result += escHtml(remaining);
            break;
        }

        // Texte brut avant le token
        result += escHtml(remaining.slice(0, bestIdx));

        if (bestRule.codeStyle) {
            // Code inline : [full, ticks, inner]
            const [full, ticks, inner] = bestMatch;
            result += `<span class="md-code">`
                    + `<span class="md-marker-code">${escHtml(ticks)}</span>`
                    + `${escHtml(inner)}`
                    + `<span class="md-marker-code">${escHtml(ticks)}</span>`
                    + `</span>`;
            remaining = remaining.slice(bestIdx + full.length);
        } else {
            // Inline classique : [full, open, inner, close]
            const [full, open, inner, close] = bestMatch;
            // Récursion sur le contenu pour gérer les styles imbriqués
            result += `<span class="${bestRule.cls}">`
                    + `<span class="${bestRule.marker}">${escHtml(open)}</span>`
                    + highlightInline(inner)
                    + `<span class="${bestRule.marker}">${escHtml(close)}</span>`
                    + `</span>`;
            remaining = remaining.slice(bestIdx + full.length);
        }
    }

    return result;
}

// ─── Cache hljs ──────────────────────────────────────────────────────────────
//
// hljs.highlight() et surtout hljs.highlightAuto() sont coûteux.
// On mémoïse le résultat par clé "lang\x00body" pour ne recalculer
// que les blocs qui ont réellement changé depuis le dernier rendu.

const _hljsCache = new Map();

function hljsCached(lang, body) {
    const key = lang + '\x00' + body;
    if (_hljsCache.has(key)) return _hljsCache.get(key);

    let result;
    try {
        result = (lang && hljs.getLanguage(lang))
            ? hljs.highlight(body, { language: lang, ignoreIllegals: true }).value
            : hljs.highlightAuto(body).value;
    } catch (_) {
        result = escHtml(body);
    }

    // Limite arbitraire pour ne pas garder des milliers d'entrées en mémoire
    if (_hljsCache.size > 200) {
        // Supprime la première entrée insérée (ordre d'insertion Map)
        _hljsCache.delete(_hljsCache.keys().next().value);
    }
    _hljsCache.set(key, result);
    return result;
}

// ─── Synchronisation des styles textarea → miroir ────────────────────────────

const COPY_PROPS = [
    'fontFamily', 'fontSize', 'fontStyle', 'fontVariant', 'fontWeight',
    'lineHeight', 'letterSpacing', 'textTransform',
    'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
    'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',
    'boxSizing', 'wordWrap', 'whiteSpace', 'overflowWrap', 'tabSize',
];

// syncStyles est coûteux (getComputedStyle) → on ne l'appelle qu'une fois
// au premier rendu, puis uniquement si la hauteur change.
let _stylesSynced = false;

function syncStyles(textarea, highlight) {
    if (!_stylesSynced) {
        const cs = getComputedStyle(textarea);
        for (const p of COPY_PROPS) highlight.style[p] = cs[p];
        _stylesSynced = true;
    }
    if (textarea.style.height) highlight.style.height = textarea.style.height;
}

// ─── Rendu différentiel par innerHTML ────────────────────────────────────────
//
// On évite de toucher au DOM si le HTML produit est identique au précédent.
// Sur un document de 200 lignes où l'utilisateur tape à la fin,
// seule la dernière ligne change → le HTML global change peu,
// mais on économise quand même la passe de parsing/layout du navigateur
// si rien n'a changé du tout (ex : Ctrl+B sur texte déjà gras).

let _lastHtml = '';

function updateHighlight(textarea, highlight) {
    syncStyles(textarea, highlight);
    const html = highlightMarkdown(textarea.value + '\n');
    if (html !== _lastHtml) {
        highlight.innerHTML = _lastHtml = html;
    }
    highlight.scrollTop  = textarea.scrollTop;
    highlight.scrollLeft = textarea.scrollLeft;
}

// ─── Initialisation ───────────────────────────────────────────────────────────

function initToolbar() {
    const editor  = document.getElementById('editor');
    const toolbar = document.getElementById('markdownToolbar');
    if (!editor || !toolbar) return;

    // 1. CSS
    const styleEl = document.createElement('style');
    styleEl.textContent = HIGHLIGHT_CSS;
    document.head.appendChild(styleEl);

    // 2. Wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'editor-wrapper';
    editor.parentNode.insertBefore(wrapper, editor);
    wrapper.appendChild(editor);

    // 3. Div miroir (inséré avant le textarea dans le DOM)
    const highlight = document.createElement('div');
    highlight.id = 'editor-highlight';
    wrapper.insertBefore(highlight, editor);

    // 4. Scroll sync
    editor.addEventListener('scroll', () => {
        highlight.scrollTop  = editor.scrollTop;
        highlight.scrollLeft = editor.scrollLeft;
    });

    // 5. Mise à jour
    //
    // Stratégie à deux vitesses :
    //   - Les actions toolbar/raccourcis déclenchent un rendu IMMÉDIAT
    //     (l'utilisateur vient de faire une action volontaire, il s'attend
    //     à voir le résultat tout de suite).
    //   - La frappe clavier normale utilise un DEBOUNCE de 80 ms
    //     (délai imperceptible pour l'œil, mais qui évite de recalculer
    //     le highlight à chaque caractère sur un long document).

    let _debounceTimer = null;

    const doUpdateImmediate = () => updateHighlight(editor, highlight);

    const doUpdateDebounced = () => {
        clearTimeout(_debounceTimer);
        _debounceTimer = setTimeout(doUpdateImmediate, 80);
    };

    editor.addEventListener('input', doUpdateDebounced);
    new ResizeObserver(doUpdateImmediate).observe(editor);
    doUpdateImmediate();

    // 6. Boutons toolbar
    toolbar.querySelectorAll('button[data-format]').forEach(btn => {
        btn.addEventListener('mousedown', e => {
            e.preventDefault();
            const action = FORMAT_ACTIONS[btn.dataset.format];
            if (action) {
                action(editor);
                editor.dispatchEvent(new Event('input', { bubbles: true }));
                doUpdateImmediate(); // rendu immédiat après une action toolbar
            }
        });
    });

    // 7. Raccourcis clavier
    editor.addEventListener('keydown', e => {
        if (!(e.ctrlKey || e.metaKey)) return;
        const map = { b: 'bold', i: 'italic', u: 'underline', k: 'link' };
        const action = FORMAT_ACTIONS[map[e.key.toLowerCase()]];
        if (action) {
            e.preventDefault();
            action(editor);
            editor.dispatchEvent(new Event('input', { bubbles: true }));
            doUpdateImmediate(); // rendu immédiat après un raccourci
        }
        // Ctrl+Z / Ctrl+Y → navigateur
    });

    // 8. Affichage conditionnel (mode édition vs préview)
    const modeObserver = new MutationObserver(() => {
        const editing = !editor.classList.contains('disabled');
        highlight.style.display = editing ? 'block' : 'none';
        if (editing) doUpdateImmediate();
    });
    modeObserver.observe(editor, { attributes: true, attributeFilter: ['class'] });
    highlight.style.display = editor.classList.contains('disabled') ? 'none' : 'block';
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initToolbar);
} else {
    initToolbar();
}
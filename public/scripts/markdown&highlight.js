
const md = window.markdownit({
    html: false,
    linkify: true,
    typographer: true,
    breaks: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return '<pre class="hljs"><code class="language-' + lang + '">' +
                       hljs.highlight(str, { language: lang, ignoreIllegals: true }).value +
                       '</code></pre>';
            } catch (__) {}
        }
        return '<pre class="hljs"><code>' + md.utils.escapeHtml(str) + '</code></pre>';
    }
});

md.enable('table');

// Plugin personnalisé pour les underscores = soulignement
// Désactiver d'abord le traitement des underscores par emphasis
md.inline.ruler.disable('emphasis');

md.inline.ruler.push('underline', function(state, silent) {
    const start = state.pos;
    
    // Vérifier le marqueur (_  ou __)
    if (state.src[start] !== '_') {
        return false;
    }
    
    let delim;
    if (state.src[start + 1] === '_') {
        delim = '__'; // Double underscore
    } else {
        delim = '_'; // Simple underscore
    }
    
    let pos = start + delim.length;
    let found = false;
    
    // Chercher le délimiteur fermant correspondant
    while (pos <= state.src.length - delim.length) {
        if (state.src.substr(pos, delim.length) === delim && 
            state.src[pos - 1] !== '\\') {
            found = true;
            break;
        }
        pos++;
    }
    
    if (!found) {
        return false;
    }
    
    if (!silent) {
        state.push('underline_open', 'u', 1);
        const text = state.push('text', '', 0);
        text.content = state.src.slice(start + delim.length, pos);
        state.push('underline_close', 'u', -1);
    }
    
    state.pos = pos + delim.length;
    return true;
});

// Plugin personnalisé pour les astérisques = gras et italique
md.inline.ruler.push('emphasis_custom', function(state, silent) {
    const start = state.pos;
    
    if (state.src[start] !== '*') {
        return false;
    }
    
    let delim;
    if (state.src.substr(start, 3) === '***') {
        delim = '***'; // Triple astérisque = gras + italique
    } else if (state.src[start + 1] === '*') {
        delim = '**'; // Double astérisque = gras
    } else {
        delim = '*'; // Simple astérisque = italique
    }
    
    let pos = start + delim.length;
    let found = false;
    
    // Chercher le délimiteur fermant correspondant
    while (pos <= state.src.length - delim.length) {
        if (state.src.substr(pos, delim.length) === delim && 
            state.src[pos - 1] !== '\\') {
            found = true;
            break;
        }
        pos++;
    }
    
    if (!found) {
        return false;
    }
    
    if (!silent) {
        const text = state.src.slice(start + delim.length, pos);
        
        if (delim === '***') {
            state.push('strong_open', 'strong', 1);
            state.push('em_open', 'em', 1);
            const token = state.push('text', '', 0);
            token.content = text;
            state.push('em_close', 'em', -1);
            state.push('strong_close', 'strong', -1);
        } else if (delim === '**') {
            state.push('strong_open', 'strong', 1);
            const token = state.push('text', '', 0);
            token.content = text;
            state.push('strong_close', 'strong', -1);
        } else {
            state.push('em_open', 'em', 1);
            const token = state.push('text', '', 0);
            token.content = text;
            state.push('em_close', 'em', -1);
        }
    }
    
    state.pos = pos + delim.length;
    return true;
});

// Règles pour rendre les balises u, strong, em
md.renderer.rules.underline_open = () => '<u>';
md.renderer.rules.underline_close = () => '</u>';
md.renderer.rules.strong_open = () => '<strong>';
md.renderer.rules.strong_close = () => '</strong>';
md.renderer.rules.em_open = () => '<em>';
md.renderer.rules.em_close = () => '</em>';
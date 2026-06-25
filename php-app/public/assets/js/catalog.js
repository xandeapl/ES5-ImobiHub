import { listProperties } from './api.js';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const DEAL_TYPE_LABELS = { comprar: 'Comprar', alugar: 'Alugar', todos: 'Todos' };
const PROPERTY_TYPE_LABELS = {
    apartamento: 'Apartamento',
    casa: 'Casa',
    'imovel-comercial': 'Comercial',
    studio: 'Studio',
    cobertura: 'Cobertura',
    terreno: 'Terreno',
};

const PIN_SVG = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>`;

function escHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatCurrency(value, dealType) {
    const n = 'R$\u00a0' + Number(value).toLocaleString('pt-BR', { maximumFractionDigits: 0 });
    return dealType === 'alugar' ? n + '/m\u00eas' : n;
}

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------

function renderCard(item) {
    const article = document.createElement('article');
    article.className = 'card' + (item.sold ? ' is-sold' : '');

    const hasPhoto = item.photos && item.photos.length > 0;
    const isSold   = !!item.sold;
    const typeSlug = item.property_type;
    const showSpecs = typeSlug !== 'terreno';

    const tagsHtml = [
        DEAL_TYPE_LABELS[item.deal_type] ?? item.deal_type,
        PROPERTY_TYPE_LABELS[typeSlug] ?? typeSlug,
        item.sustainability_tag,
    ].filter(Boolean).map(t => `<span class="tag">${escHtml(t)}</span>`).join('');

    const specsHtml = showSpecs
        ? `<span class="card-specs">${escHtml(item.area)}m² &middot; ${escHtml(item.bedrooms)} qtos &middot; ${escHtml(item.bathrooms)} ban</span>`
        : `<span class="card-specs">${escHtml(item.area)}m²</span>`;

    article.innerHTML = `
        <div class="card-photo">
            ${hasPhoto
                ? `<img src="${escHtml(item.photos[0])}" alt="${escHtml(item.title)}">`
                : `<div class="card-photo-placeholder">Sem foto</div>`}
            <span class="card-photo-badge badge ${isSold ? 'badge-sold' : 'badge-available'}">
                ${isSold ? 'Vendido' : 'Disponível'}
            </span>
            <button class="card-heart" aria-label="Favoritar" type="button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
        </div>
        <div class="card-body">
            <h3 class="card-title">${escHtml(item.title)}</h3>
            <p class="card-location">${PIN_SVG} ${escHtml(item.neighborhood)}, ${escHtml(item.city)}</p>
            <div class="tags">${tagsHtml}</div>
            <p class="card-desc">${escHtml(item.description)}</p>
            <div class="card-footer-row">
                <span class="card-price">${escHtml(formatCurrency(item.price, item.deal_type))}</span>
                ${specsHtml}
            </div>
            <button class="btn btn-outline btn-sm btn-full" type="button" style="margin-top:10px">Ver detalhes</button>
        </div>
    `;
    return article;
}

// ---------------------------------------------------------------------------
// Load
// ---------------------------------------------------------------------------

async function loadCatalog(params) {
    const cardsEl     = document.getElementById('cards');
    const emptyState  = document.getElementById('empty-state');
    const resultCount = document.getElementById('result-count');

    try {
        const data = await listProperties(params);
        cardsEl.replaceChildren(...data.map(renderCard));
        resultCount.textContent = `Resultados (${data.length})`;
        emptyState.hidden = data.length > 0;
    } catch {
        cardsEl.innerHTML = '<p style="color:var(--red)">Erro ao carregar im\u00f3veis. Tente novamente.</p>';
    }
}

// ---------------------------------------------------------------------------
// Filter form
// ---------------------------------------------------------------------------

function getSearchParams() {
    const sp = new URLSearchParams(location.search);
    return {
        dealType:     sp.get('dealType')     ?? 'todos',
        propertyType: sp.get('propertyType') ?? 'todos',
        q:            sp.get('q')            ?? '',
        sort:         sp.get('sort')         ?? 'default',
        showSold:     sp.get('showSold')     === '1' ? '1' : '',
    };
}

const form   = document.getElementById('filter-form');
const params = getSearchParams();

// Restore values from URL
for (const name of ['dealType', 'propertyType', 'q', 'sort']) {
    const el = form.elements[name];
    if (el) el.value = params[name];
}
if (params.showSold === '1') {
    const sw = form.elements['showSold'];
    if (sw) sw.checked = true;
}

form.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(form);
    const sp = new URLSearchParams();
    for (const [k, v] of fd.entries()) sp.set(k, String(v));
    history.pushState({}, '', '?' + sp.toString());
    loadCatalog(Object.fromEntries(sp));
});

loadCatalog(params);

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
const WHATSAPP_NUMBER = (document.body.dataset.whatsapp || '').replace(/\D+/g, '') || '5541999998888';

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

function buildWhatsAppLink(item) {
        const text = [
                `Olá! Tenho interesse no imóvel: ${item.title}`,
                `Local: ${item.neighborhood}, ${item.city}`,
                `Preço: ${formatCurrency(item.price, item.deal_type)}`,
        ].join('\n');

        return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(text)}`;
}

function openDetailsModal(item) {
        const existing = document.getElementById('property-detail-modal');
        if (existing) existing.remove();

        const hasPhoto = item.photos && item.photos.length > 0;
        const specs = item.property_type !== 'terreno'
                ? `${escHtml(item.area)}m² • ${escHtml(item.bedrooms)} quartos • ${escHtml(item.bathrooms)} banheiros`
                : `${escHtml(item.area)}m²`;

        const html = `
            <div class="detail-overlay" id="property-detail-modal" role="dialog" aria-modal="true" aria-label="Detalhes do imóvel">
                <div class="detail-modal">
                    <button type="button" class="detail-close" aria-label="Fechar">×</button>
                    <div class="detail-media">
                        ${hasPhoto
                                ? `<img src="${escHtml(item.photos[0])}" alt="${escHtml(item.title)}">`
                                : `<div class="card-photo-placeholder">Sem foto</div>`}
                    </div>
                    <div class="detail-content">
                        <h3>${escHtml(item.title)}</h3>
                        <p class="detail-location">${PIN_SVG} ${escHtml(item.neighborhood)}, ${escHtml(item.city)}</p>
                        <div class="tags">
                            <span class="tag">${escHtml(DEAL_TYPE_LABELS[item.deal_type] ?? item.deal_type)}</span>
                            <span class="tag">${escHtml(PROPERTY_TYPE_LABELS[item.property_type] ?? item.property_type)}</span>
                            <span class="tag">${escHtml(item.sustainability_tag)}</span>
                        </div>
                        <p class="detail-description">${escHtml(item.description)}</p>
                        <div class="detail-price-row">
                            <strong class="card-price">${escHtml(formatCurrency(item.price, item.deal_type))}</strong>
                            <span class="card-specs">${specs}</span>
                        </div>
                        <a href="${buildWhatsAppLink(item)}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-full">
                            Entrar em contato no WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
        const modal = document.getElementById('property-detail-modal');

        modal.querySelector('.detail-close').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', e => {
                if (e.target === modal) modal.remove();
        });
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
            <button class="btn btn-outline btn-sm btn-full" type="button" data-action="details" style="margin-top:10px">Ver detalhes</button>
        </div>
    `;

    article.querySelector('[data-action="details"]').addEventListener('click', () => openDetailsModal(item));
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

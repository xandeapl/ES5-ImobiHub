import { listAll, createProperty, updateProperty, updatePrice, toggleSold, deleteProperty } from './api.js';

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

// ---------------------------------------------------------------------------
// Notices
// ---------------------------------------------------------------------------

function showNotice(message, type = 'success') {
    const el = document.getElementById('notice');
    el.className = `notice ${type}`;
    el.textContent = message;
    el.hidden = false;
    clearTimeout(el._timer);
    el._timer = setTimeout(() => { el.hidden = true; }, 5000);
}

// ---------------------------------------------------------------------------
// Stats
// ---------------------------------------------------------------------------

function updateStats(properties) {
    const sold      = properties.filter(p => p.sold).length;
    const available = properties.length - sold;
    document.getElementById('stat-total').textContent     = properties.length;
    document.getElementById('stat-sold').textContent      = sold;
    document.getElementById('stat-available').textContent = available;
}

// ---------------------------------------------------------------------------
// Property card (template cloning)
// ---------------------------------------------------------------------------

function buildPropertyItem(item) {
    const tpl     = document.getElementById('tpl-property');
    const clone   = tpl.content.cloneNode(true);
    const article = clone.querySelector('article');

    // Header fields
    article.querySelector('[data-field="title"]').textContent    = item.title;
    article.querySelector('[data-field="location"]').textContent = `${item.neighborhood}, ${item.city}`;

    const badge = article.querySelector('[data-field="sold-badge"]');
    badge.textContent = item.sold ? 'Vendido' : 'Disponível';
    badge.className   = `badge ${item.sold ? 'badge-sold' : 'badge-available'}`;

    article.querySelector('[data-field="sold-btn"]').textContent =
        item.sold ? 'Marcar como disponível' : 'Marcar como vendido';

    // Tags
    const tagsDiv = article.querySelector('[data-field="tags"]');
    const tagValues = [
        DEAL_TYPE_LABELS[item.deal_type] ?? item.deal_type,
        PROPERTY_TYPE_LABELS[item.property_type] ?? item.property_type,
        item.sustainability_tag,
    ].filter(Boolean);
    tagsDiv.innerHTML = tagValues.map(t => `<span class="tag">${t.replace(/</g,'&lt;')}</span>`).join('');

    // Photo column
    const imgCol = article.querySelector('.prop-img-col');
    if (item.photos && item.photos.length > 0) {
        const img = document.createElement('img');
        img.className = 'prop-thumb';
        img.src = item.photos[0];
        img.alt = 'Foto do imóvel';
        imgCol.appendChild(img);
    } else {
        const ph = document.createElement('div');
        ph.className = 'prop-thumb-ph';
        ph.textContent = 'Sem foto';
        imgCol.appendChild(ph);
    }

    // Price update form
    const priceForm = article.querySelector('[data-action="update-price"]');
    priceForm.querySelector('input[name="price"]').value = Math.round(item.price);
    priceForm.addEventListener('submit', async e => {
        e.preventDefault();
        const price = Number(priceForm.querySelector('input[name="price"]').value);
        try {
            await updatePrice(item.id, price);
            showNotice('Preço atualizado com sucesso.');
            reload();
        } catch (err) {
            showNotice(err.error ?? 'Erro ao atualizar preço.', 'error');
        }
    });

    // Toggle sold form
    const soldForm = article.querySelector('[data-action="toggle-sold"]');
    soldForm.addEventListener('submit', async e => {
        e.preventDefault();
        try {
            await toggleSold(item.id);
            showNotice('Status atualizado com sucesso.');
            reload();
        } catch (err) {
            showNotice(err.error ?? 'Erro ao atualizar status.', 'error');
        }
    });

    // Edit form — pre-fill with current values
    const editForm = article.querySelector('[data-action="edit"]');
    const fields = ['title', 'deal_type', 'property_type', 'city', 'neighborhood', 'price', 'area', 'bedrooms', 'bathrooms', 'sustainability_tag', 'description'];
    for (const field of fields) {
        const el = editForm.elements[field];
        if (el) el.value = field === 'price' ? Math.round(item.price) : (item[field] ?? '');
    }
    editForm.addEventListener('submit', async e => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(editForm));
        try {
            await updateProperty(item.id, data);
            showNotice('Imóvel atualizado com sucesso.');
            reload();
        } catch (err) {
            showNotice(err.error ?? 'Erro ao salvar edição.', 'error');
        }
    });

    // Delete form
    const deleteForm = article.querySelector('[data-action="delete"]');
    deleteForm.addEventListener('submit', async e => {
        e.preventDefault();
        try {
            await deleteProperty(item.id);
            showNotice('Anúncio excluído com sucesso.');
            reload();
        } catch (err) {
            showNotice(err.error ?? 'Erro ao excluir.', 'error');
        }
    });

    // Edit / delete toggle buttons
    const editWrap   = article.querySelector('[data-field="edit-form-wrap"]');
    const deleteConf = article.querySelector('[data-field="delete-confirm"]');

    article.querySelector('[data-action="edit-toggle"]').addEventListener('click', () => {
        editWrap.hidden = !editWrap.hidden;
        deleteConf.hidden = true;
    });
    article.querySelector('[data-action="edit-cancel"]').addEventListener('click', () => {
        editWrap.hidden = true;
    });
    article.querySelector('[data-action="delete-toggle"]').addEventListener('click', () => {
        deleteConf.hidden = !deleteConf.hidden;
        editWrap.hidden = true;
    });
    article.querySelector('[data-action="delete-cancel"]').addEventListener('click', () => {
        deleteConf.hidden = true;
    });

    // Thumbnails
    const thumbs = article.querySelector('[data-field="photos"]');
    for (const src of (item.photos ?? [])) {
        const img = document.createElement('img');
        img.src = src;
        img.alt = 'Foto do imóvel';
        thumbs.appendChild(img);
    }

    return article;
}

// ---------------------------------------------------------------------------
// Reload list
// ---------------------------------------------------------------------------

async function reload() {
    const listEl = document.getElementById('property-list');
    try {
        const data = await listAll();
        updateStats(data);
        listEl.replaceChildren(...data.map(buildPropertyItem));
    } catch {
        listEl.innerHTML = '<p>Erro ao carregar imóveis.</p>';
    }
}

// ---------------------------------------------------------------------------
// Create form
// ---------------------------------------------------------------------------

const createForm = document.getElementById('create-form');
createForm.addEventListener('submit', async e => {
    e.preventDefault();
    try {
        await createProperty(new FormData(createForm));
        showNotice('Imóvel cadastrado com sucesso.');
        createForm.reset();
        reload();
    } catch (err) {
        showNotice(err.error ?? 'Erro ao cadastrar imóvel.', 'error');
    }
});

// ---------------------------------------------------------------------------
// Boot
// ---------------------------------------------------------------------------

reload();

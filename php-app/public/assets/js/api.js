/**
 * Cliente HTTP para a API REST de imóveis.
 * Todas as funções retornam Promises e lançam objetos { error: string } em caso de falha.
 */

const BASE = '/api/properties.php';

export async function listProperties(params = {}) {
    const url = new URL(BASE, location.href);
    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== '') url.searchParams.set(key, String(value));
    }
    const res = await fetch(url);
    const body = await res.json();
    if (!res.ok) throw body;
    return body;
}

export async function listAll() {
    const res = await fetch(`${BASE}?all=1`);
    const body = await res.json();
    if (!res.ok) throw body;
    return body;
}

export async function createProperty(formData) {
    const res = await fetch(BASE, { method: 'POST', body: formData });
    const body = await res.json();
    if (!res.ok) throw body;
    return body;
}

export async function updateProperty(id, data) {
    const res = await fetch(`${BASE}?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    const body = await res.json();
    if (!res.ok) throw body;
    return body;
}

export async function updatePrice(id, price) {
    const res = await fetch(`${BASE}?id=${id}&action=price`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ price }),
    });
    const body = await res.json();
    if (!res.ok) throw body;
    return body;
}

export async function toggleSold(id) {
    const res = await fetch(`${BASE}?id=${id}&action=sold`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: '{}',
    });
    const body = await res.json();
    if (!res.ok) throw body;
    return body;
}

export async function deleteProperty(id) {
    const res = await fetch(`${BASE}?id=${id}`, { method: 'DELETE' });
    const body = await res.json();
    if (!res.ok) throw body;
    return body;
}

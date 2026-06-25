<?php

/**
 * REST API — Imóveis
 *
 * GET    /api/properties.php               Lista pública (com filtros via query string)
 * GET    /api/properties.php?all=1         Lista completa para o dashboard
 * POST   /api/properties.php               Cria imóvel (multipart/form-data, aceita fotos)
 * PUT    /api/properties.php?id=X          Atualiza dados textuais do imóvel (JSON)
 * PATCH  /api/properties.php?id=X&action=price   Atualiza preço (JSON)
 * PATCH  /api/properties.php?id=X&action=sold    Alterna status vendido (sem body)
 * DELETE /api/properties.php?id=X          Exclui imóvel
 */

declare(strict_types=1);

require_once __DIR__ . '/../../backend/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

use ImobiHub\ApiResponse;
use ImobiHub\PropertyRepository;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : null;
$action = trim((string) ($_GET['action'] ?? ''));

switch ($method) {
    case 'GET':
        if (isset($_GET['all'])) {
            requireApiAuth();
        }
        handleList($repository);
        break;

    case 'POST':
        requireApiAuth();
        handleCreate($repository, $config);
        break;

    case 'PUT':
        requireApiAuth();
        requireId($id);
        handleUpdate($repository, $id);
        break;

    case 'PATCH':
        requireApiAuth();
        requireId($id);
        match ($action) {
            'price' => handleUpdatePrice($repository, $id),
            'sold'  => handleToggleSold($repository, $id),
            default => ApiResponse::error('Acao desconhecida.', 400),
        };
        break;

    case 'DELETE':
        requireApiAuth();
        requireId($id);
        handleDelete($repository, $id);
        break;

    default:
        ApiResponse::methodNotAllowed();
}

// ---------------------------------------------------------------------------
// Handlers
// ---------------------------------------------------------------------------

function handleList(PropertyRepository $repo): never
{
    if (isset($_GET['all'])) {
        ApiResponse::json($repo->listAll());
    }

    $allowed_deal_types     = ['todos', 'comprar', 'alugar'];
    $allowed_property_types = ['todos', 'apartamento', 'casa', 'imovel-comercial', 'terreno', 'studio', 'cobertura'];
    $allowed_sorts          = ['default', 'latest', 'affordable'];

    $dealType     = sanitizeEnum($_GET['dealType']  ?? 'todos',   $allowed_deal_types,     'todos');
    $propertyType = sanitizeEnum($_GET['propertyType'] ?? 'todos', $allowed_property_types, 'todos');
    $sort         = sanitizeEnum($_GET['sort']       ?? 'default', $allowed_sorts,          'default');
    $q            = trim((string) ($_GET['q'] ?? ''));
    $showSold     = ($_GET['showSold'] ?? '0') === '1';

    ApiResponse::json($repo->listPublic([
        'deal_type'     => $dealType,
        'property_type' => $propertyType,
        'q'             => $q,
        'sort'          => $sort,
        'show_sold'     => $showSold,
    ]));
}

function handleCreate(PropertyRepository $repo, array $config): never
{
    $data = collectPostPayload(forceComprar: false);

    $errors = validatePayload($data);
    if ($errors !== []) {
        ApiResponse::error(implode(' ', $errors), 422);
    }

    $upload = processUploadedPhotos($_FILES, $config);
    if (isset($upload['error'])) {
        ApiResponse::error($upload['error'], 422);
    }

    $repo->create($data, $upload['paths']);
    ApiResponse::json(['message' => 'Imovel cadastrado com sucesso.'], 201);
}

function handleUpdate(PropertyRepository $repo, int $id): never
{
    $body = parseJsonBody();

    $data = [
        'title'            => trim((string) ($body['title'] ?? '')),
        'deal_type'        => in_array(trim((string) ($body['deal_type'] ?? '')), ['comprar', 'alugar'], true) ? trim((string) $body['deal_type']) : 'comprar',
        'property_type'    => trim((string) ($body['property_type'] ?? '')),
        'city'             => trim((string) ($body['city'] ?? '')),
        'neighborhood'     => trim((string) ($body['neighborhood'] ?? '')),
        'price'            => trim((string) ($body['price'] ?? '')),
        'area'             => trim((string) ($body['area'] ?? '')),
        'bedrooms'         => trim((string) ($body['bedrooms'] ?? '')),
        'bathrooms'        => trim((string) ($body['bathrooms'] ?? '')),
        'owner_name'       => trim((string) ($body['owner_name'] ?? '')),
        'owner_whatsapp'   => trim((string) ($body['owner_whatsapp'] ?? '')),
        'description'      => trim((string) ($body['description'] ?? '')),
        'sustainability_tag' => trim((string) ($body['sustainability_tag'] ?? '')),
    ];

    $errors = validatePayload($data);
    if ($errors !== []) {
        ApiResponse::error(implode(' ', $errors), 422);
    }

    $repo->update($id, $data);
    ApiResponse::json(['message' => 'Imovel atualizado com sucesso.']);
}

function handleUpdatePrice(PropertyRepository $repo, int $id): never
{
    $body  = parseJsonBody();
    $price = (float) ($body['price'] ?? 0);

    if ($price <= 0) {
        ApiResponse::error('Preco invalido.', 422);
    }

    $repo->updatePrice($id, $price);
    ApiResponse::json(['message' => 'Preco atualizado com sucesso.']);
}

function handleToggleSold(PropertyRepository $repo, int $id): never
{
    $repo->toggleSold($id);
    ApiResponse::json(['message' => 'Status atualizado com sucesso.']);
}

function handleDelete(PropertyRepository $repo, int $id): never
{
    $repo->delete($id);
    ApiResponse::json(['message' => 'Imovel excluido com sucesso.']);
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function requireId(?int $id): void
{
    if ($id === null || $id <= 0) {
        ApiResponse::error('ID invalido.', 400);
    }
}

function requireApiAuth(): void
{
    if (currentAdminId() !== null) {
        return;
    }

    ApiResponse::error('Não autenticado.', 401);
}

function sanitizeEnum(string $value, array $allowed, string $default): string
{
    return in_array($value, $allowed, true) ? $value : $default;
}

function collectPostPayload(bool $forceComprar = false): array
{
    $str = static fn(string $key): string => trim((string) ($_POST[$key] ?? ''));

    return [
        'title'            => $str('title'),
        'deal_type'        => $forceComprar ? 'comprar' : $str('deal_type'),
        'property_type'    => $str('property_type'),
        'city'             => $str('city'),
        'neighborhood'     => $str('neighborhood'),
        'price'            => $str('price'),
        'area'             => $str('area'),
        'bedrooms'         => $str('bedrooms'),
        'bathrooms'        => $str('bathrooms'),
        'owner_name'       => $str('owner_name'),
        'owner_whatsapp'   => $str('owner_whatsapp'),
        'description'      => $str('description'),
        'sustainability_tag' => $str('sustainability_tag'),
    ];
}

function validatePayload(array $data): array
{
    $errors                 = [];
    $allowed_deal_types     = ['comprar', 'alugar'];
    $allowed_property_types = ['apartamento', 'casa', 'imovel-comercial', 'terreno', 'studio', 'cobertura'];
    $required_text_fields   = ['title', 'city', 'neighborhood', 'owner_name', 'owner_whatsapp', 'description', 'sustainability_tag'];

    foreach ($required_text_fields as $field) {
        if ($data[$field] === '') {
            $errors[] = 'Preencha todos os campos obrigatorios.';
            break;
        }
    }

    if (!in_array($data['deal_type'] ?? 'comprar', $allowed_deal_types, true)) {
        $errors[] = 'Tipo de negocio invalido.';
    }

    if (!in_array($data['property_type'], $allowed_property_types, true)) {
        $errors[] = 'Tipo de imovel invalido.';
    }

    if ((float) $data['price'] <= 0 || (int) $data['area'] <= 0
        || (int) $data['bedrooms'] < 0 || (int) $data['bathrooms'] < 0) {
        $errors[] = 'Valores numericos invalidos.';
    }

    $digits = preg_replace('/\D+/', '', (string) ($data['owner_whatsapp'] ?? ''));
    if (strlen((string) $digits) < 10) {
        $errors[] = 'WhatsApp do dono invalido.';
    }

    return $errors;
}

function parseJsonBody(): array
{
    $raw  = (string) file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        ApiResponse::error('Corpo da requisicao invalido.', 400);
    }

    return $body;
}

function processUploadedPhotos(array $files, array $config): array
{
    if (!isset($files['photos']) || !is_array($files['photos']['name'])) {
        return ['paths' => []];
    }

    $paths = [];
    $total = count($files['photos']['name']);

    for ($i = 0; $i < $total; $i++) {
        $error = (int) $files['photos']['error'][$i];

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return ['error' => 'Falha ao enviar uma das fotos.'];
        }

        $tmpPath      = (string) $files['photos']['tmp_name'][$i];
        $originalName = (string) $files['photos']['name'][$i];
        $size         = (int)    $files['photos']['size'][$i];

        if ($size > 5 * 1024 * 1024) {
            return ['error' => 'Cada imagem deve ter no maximo 5 MB.'];
        }

        $mime = detectMimeType($tmpPath);

        if (!str_starts_with($mime, 'image/')) {
            return ['error' => 'Apenas arquivos de imagem sao permitidos.'];
        }

        $ext      = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION)) ?: 'jpg';
        $ext      = preg_replace('/[^a-z0-9]/', '', $ext);
        $filename = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest     = $config['upload_dir'] . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpPath, $dest)) {
            return ['error' => 'Nao foi possivel salvar a imagem no servidor.'];
        }

        $paths[] = $config['upload_web_prefix'] . '/' . $filename;
    }

    return ['paths' => $paths];
}

function detectMimeType(string $path): string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = (string) finfo_file($finfo, $path);
            finfo_close($finfo);
            return $mime;
        }
    }

    return (string) mime_content_type($path);
}

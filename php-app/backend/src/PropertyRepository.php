<?php

declare(strict_types=1);

namespace ImobiHub;

use PDO;

final class PropertyRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listPublic(array $filters): array
    {
        $conditions = [];
        $params = [];
        $dealType = $filters['deal_type'] ?? 'todos';
        if ($dealType !== 'todos') {
            $conditions[] = 'deal_type = :deal_type';
            $params[':deal_type'] = $dealType;
        }

        if (($filters['show_sold'] ?? false) !== true) {
            $conditions[] = 'sold = 0';
        }

        if (!empty($filters['property_type']) && $filters['property_type'] !== 'todos') {
            $conditions[] = 'property_type = :property_type';
            $params[':property_type'] = $filters['property_type'];
        }

        if (!empty($filters['q'])) {
            $conditions[] = '(title LIKE :q OR city LIKE :q OR neighborhood LIKE :q OR description LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $sortMode = $filters['sort'] ?? 'default';
        $orderBy = 'ORDER BY created_at DESC';
        if ($sortMode === 'affordable') {
            $orderBy = 'ORDER BY price ASC';
        } elseif ($sortMode === 'latest') {
            $orderBy = 'ORDER BY created_at DESC';
        }

        $whereClause = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = 'SELECT * FROM properties ' . $whereClause . ' ' . $orderBy;

        $statement = $this->pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value);
        }
        $statement->execute();

        return $this->hydrateMany($statement->fetchAll());
    }

    public function listAll(): array
    {
        $statement = $this->pdo->query('SELECT * FROM properties ORDER BY created_at DESC');

        return $this->hydrateMany($statement->fetchAll());
    }

    public function create(array $data, array $photos): void
    {
        $sql = <<<'SQL'
INSERT INTO properties
    (slug, title, deal_type, property_type, city, neighborhood, price, area, bedrooms, bathrooms, description, sustainability_tag, photos_json, sold, created_at)
VALUES
    (:slug, :title, :deal_type, :property_type, :city, :neighborhood, :price, :area, :bedrooms, :bathrooms, :description, :sustainability_tag, :photos_json, 0, :created_at)
SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':slug'             => 'imovel-' . time() . '-' . bin2hex(random_bytes(3)),
            ':title'            => $data['title'],
            ':deal_type'        => $data['deal_type'],
            ':property_type'    => $data['property_type'],
            ':city'             => $data['city'],
            ':neighborhood'     => $data['neighborhood'],
            ':price'            => (float) $data['price'],
            ':area'             => (int) $data['area'],
            ':bedrooms'         => (int) $data['bedrooms'],
            ':bathrooms'        => (int) $data['bathrooms'],
            ':description'      => $data['description'],
            ':sustainability_tag' => $data['sustainability_tag'],
            ':photos_json'      => json_encode($photos, JSON_THROW_ON_ERROR),
            ':created_at'       => gmdate('c'),
        ]);
    }

    public function updatePrice(int $id, float $price): void
    {
        $stmt = $this->pdo->prepare('UPDATE properties SET price = :price WHERE id = :id');
        $stmt->execute([':price' => $price, ':id' => $id]);
    }

    public function toggleSold(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE properties SET sold = CASE WHEN sold = 1 THEN 0 ELSE 1 END WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function update(int $id, array $data): void
    {
        $sql = <<<'SQL'
UPDATE properties
SET title              = :title,
    deal_type          = :deal_type,
    property_type      = :property_type,
    city               = :city,
    neighborhood       = :neighborhood,
    price              = :price,
    area               = :area,
    bedrooms           = :bedrooms,
    bathrooms          = :bathrooms,
    description        = :description,
    sustainability_tag = :sustainability_tag
WHERE id = :id
SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'               => $id,
            ':title'            => $data['title'],
            ':deal_type'        => $data['deal_type'],
            ':property_type'    => $data['property_type'],
            ':city'             => $data['city'],
            ':neighborhood'     => $data['neighborhood'],
            ':price'            => (float) $data['price'],
            ':area'             => (int) $data['area'],
            ':bedrooms'         => (int) $data['bedrooms'],
            ':bathrooms'        => (int) $data['bathrooms'],
            ':description'      => $data['description'],
            ':sustainability_tag' => $data['sustainability_tag'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM properties WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function seedIfEmpty(): void
    {
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $seed = [
            [
                'title'            => 'Apartamento compacto no Centro',
                'deal_type'        => 'comprar',
                'property_type'    => 'apartamento',
                'city'             => 'Curitiba',
                'neighborhood'     => 'Centro',
                'price'            => 385000,
                'area'             => 62,
                'bedrooms'         => 2,
                'bathrooms'        => 2,
                'description'      => 'Apartamento com ventilacao cruzada e acesso facil a transporte publico.',
                'sustainability_tag' => 'Mobilidade urbana',
                'photos'           => ['https://images.unsplash.com/photo-1494526585095-c41746248156?w=1200&q=80&auto=format&fit=crop'],
            ],
            [
                'title'            => 'Casa familiar com quintal permeavel',
                'deal_type'        => 'comprar',
                'property_type'    => 'casa',
                'city'             => 'Curitiba',
                'neighborhood'     => 'Bacacheri',
                'price'            => 720000,
                'area'             => 145,
                'bedrooms'         => 3,
                'bathrooms'        => 2,
                'description'      => 'Imovel com area verde, reuso de agua e espaco para horta.',
                'sustainability_tag' => 'Infraestrutura verde',
                'photos'           => ['https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=1200&q=80&auto=format&fit=crop'],
            ],
        ];

        foreach ($seed as $item) {
            $this->create($item, $item['photos']);
        }
    }

    private function hydrateMany(array $rows): array
    {
        return array_map(function (array $row): array {
            $row['id']       = (int) $row['id'];
            $row['sold']     = (bool) $row['sold'];
            $row['price']    = (float) $row['price'];
            $row['area']     = (int) $row['area'];
            $row['bedrooms'] = (int) $row['bedrooms'];
            $row['bathrooms'] = (int) $row['bathrooms'];
            $row['photos']   = json_decode((string) $row['photos_json'], true) ?: [];

            return $row;
        }, $rows);
    }
}

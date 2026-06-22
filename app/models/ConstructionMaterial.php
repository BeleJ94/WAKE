<?php

declare(strict_types=1);

class ConstructionMaterial extends Model
{
    public function active(): array
    {
        return $this->db->query('SELECT * FROM construction_materials WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
    }
}


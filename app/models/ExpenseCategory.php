<?php

declare(strict_types=1);

class ExpenseCategory extends Model
{
    public function active(): array
    {
        return $this->db->query('SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
    }
}


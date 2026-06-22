<?php

declare(strict_types=1);

class TreasuryMovementController extends Controller
{
    public function index(): void
    {
        $this->view('treasury_movements.index', [
            'title' => 'Mouvements de trésorerie',
            'movements' => (new TreasuryMovement())->all(),
        ]);
    }
}


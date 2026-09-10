<?php

namespace App\Models\Demos;

trait ConnectsToDemosDatabase
{
    public function initializeConnectsToDemosDatabase(): void
    {
        $this->setConnection((string) config('sandbox.connection', 'demos_db'));
    }
}

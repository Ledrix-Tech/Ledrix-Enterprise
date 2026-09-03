<?php

namespace App\Exceptions;

use RuntimeException;

class ImportPlanLimitException extends RuntimeException
{
    public function __construct(
        public readonly string $headline,
        public readonly string $workaround,
        public readonly string $upgrade,
        public readonly string $upgradeUrl,
    ) {
        parent::__construct(trim($headline.' '.$workaround.' '.$upgrade));
    }

    /** @return array{headline: string, workaround: string, upgrade: string, upgrade_url: string} */
    public function toFlash(): array
    {
        return [
            'headline'    => $this->headline,
            'workaround'  => $this->workaround,
            'upgrade'     => $this->upgrade,
            'upgrade_url' => $this->upgradeUrl,
        ];
    }
}

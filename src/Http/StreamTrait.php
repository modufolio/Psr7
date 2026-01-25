<?php

declare(strict_types = 1);

namespace Modufolio\Psr7\Http;

trait StreamTrait
{
    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->seek(0);
        }

        return $this->getContents();
    }
}

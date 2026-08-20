<?php

namespace App\Exceptions;

use RuntimeException;

class CatalogCacheWarmingException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Katalog sedang diperbarui.');
    }
}

<?php

namespace App\Services\MasterCatalog;

use RuntimeException;

class MasterCatalogImportException extends RuntimeException
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'El catalogo maestro contiene errores de validacion.'
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\MasterCatalog\MasterCatalogImportException;
use App\Services\MasterCatalog\MasterCatalogImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportarCatalogoMaestroCommand extends Command
{
    protected $signature = 'pos:importar-catalogo-maestro {rutaExcel=database/catalogos/catalogo_maestro.xlsx}';

    protected $description = 'Importa y sincroniza el catalogo maestro POS desde un archivo Excel';

    public function handle(MasterCatalogImportService $service): int
    {
        $path = $this->resolvePath((string) $this->argument('rutaExcel'));

        $this->line('Leyendo catalogo maestro: ' . $path);

        try {
            $stats = $service->import($path);

            $this->info('Catalogo maestro importado correctamente.');
            foreach ($stats as $key => $value) {
                $this->line(sprintf('- %s: %d', $key, $value));
            }

            return self::SUCCESS;
        } catch (MasterCatalogImportException $exception) {
            $this->error('La importacion no se pudo completar por errores de validacion:');
            foreach ($exception->errors() as $error) {
                $this->line('  - ' . $error);
            }

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Error inesperado durante la importacion: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolvePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:/', $path) === 1
            || str_starts_with($path, DIRECTORY_SEPARATOR)
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\');
    }
}

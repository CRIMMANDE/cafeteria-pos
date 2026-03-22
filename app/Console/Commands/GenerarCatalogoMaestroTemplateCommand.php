<?php

namespace App\Console\Commands;

use App\Services\MasterCatalog\MasterCatalogTemplateGenerator;
use Illuminate\Console\Command;

class GenerarCatalogoMaestroTemplateCommand extends Command
{
    protected $signature = 'pos:generar-catalogo-maestro-template
        {rutaExcel=database/catalogos/catalogo_maestro.xlsx : Ruta destino del archivo Excel}
        {--force : Sobrescribe el archivo si ya existe}';

    protected $description = 'Genera la plantilla base del catalogo maestro POS en un solo archivo Excel';

    public function handle(MasterCatalogTemplateGenerator $generator): int
    {
        $path = $this->resolvePath((string) $this->argument('rutaExcel'));

        if (is_file($path) && !$this->option('force')) {
            $this->error('El archivo ya existe. Usa --force para sobrescribirlo: ' . $path);
            return self::FAILURE;
        }

        $generatedPath = $generator->generate($path);

        $this->info('Plantilla de catalogo maestro generada correctamente:');
        $this->line($generatedPath);

        return self::SUCCESS;
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

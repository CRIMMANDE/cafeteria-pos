<?php

namespace App\Http\Controllers;

use App\Services\SalesCut\SalesCutExcelExportService;
use App\Services\SalesCut\SalesCutPrintService;
use App\Services\SalesCut\SalesCutService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminSalesCutController extends Controller
{
    public function __construct(
        private readonly SalesCutService $salesCutService,
        private readonly SalesCutPrintService $salesCutPrintService,
        private readonly SalesCutExcelExportService $salesCutExcelExportService,
    ) {}

    public function index(Request $request): View
    {
        $inicioInput = (string) $request->input('inicio', now()->startOfDay()->format('Y-m-d\\TH:i'));
        $finInput = (string) $request->input('fin', now()->format('Y-m-d\\TH:i'));
        $filter = (string) $request->input('sucursal', 'todas');
        $resumen = null;

        $request->validate([
            'sucursal' => ['sometimes', 'required', Rule::in(array_keys(SalesCutService::FILTERS))],
        ], $this->validationMessages());

        if ($request->hasAny(['inicio', 'fin'])) {
            $request->validate([
                'inicio' => ['required', 'date_format:Y-m-d\\TH:i'],
                'fin' => ['required', 'date_format:Y-m-d\\TH:i', 'after_or_equal:inicio'],
            ], $this->validationMessages());

            $inicio = $this->parseInputDate($inicioInput);
            $fin = $this->parseInputDate($finInput, true);

            if ($inicio && $fin && $inicio->lessThanOrEqualTo($fin)) {
                $resumen = $this->salesCutService->summary($inicio, $fin, $filter);
            }
        }

        return view('admin.corte-ventas.index', [
            'inicio' => $inicioInput,
            'fin' => $finInput,
            'sucursalFiltro' => $filter,
            'sucursalesFiltro' => SalesCutService::FILTERS,
            'resumen' => $resumen,
        ]);
    }

    public function print(Request $request): RedirectResponse
    {
        [$inicio, $fin, $inicioInput, $finInput, $filter] = $this->validateRange($request);

        $summary = $this->salesCutService->summary($inicio, $fin, $filter);
        $result = $this->salesCutPrintService->print($summary, $filter);
        $destination = strtoupper($this->salesCutPrintService->destinationFor($filter));

        $flashKey = $result->printed ? 'ok' : 'error';
        $message = $result->printed
            ? "Corte de ventas enviado a impresión de {$destination}."
            : ($result->message ?: 'No se pudo imprimir el corte de ventas.');

        return redirect($this->resultUrl($inicioInput, $finInput, $filter))
            ->with($flashKey, $message);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        [$inicio, $fin, $inicioInput, $finInput, $filter] = $this->validateRange($request);

        $dataset = $this->salesCutService->exportRows($inicio, $fin, $filter);

        try {
            $file = $this->salesCutExcelExportService->createFile(
                $dataset['columns'],
                $dataset['rows'],
                $inicio,
                $fin,
            );

            return response()->download(
                $file['path'],
                $file['filename'],
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        } catch (RuntimeException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'ziparchive')) {
                $csv = $this->createCsvFallback($dataset['columns'], $dataset['rows'], $inicio, $fin);

                return response()->download(
                    $csv['path'],
                    $csv['filename'],
                    ['Content-Type' => 'text/csv; charset=UTF-8']
                )->deleteFileAfterSend(true);
            }

            return redirect($this->resultUrl($inicioInput, $finInput, $filter))
                ->with('error', $exception->getMessage());
        }
    }

    private function createCsvFallback(array $columns, array $rows, Carbon $inicio, Carbon $fin): array
    {
        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $filename = sprintf(
            'corte_ventas_%s_%s_%s_%s.csv',
            $inicio->format('Y-m-d'),
            $inicio->format('Hi'),
            $fin->format('Y-m-d'),
            $fin->format('Hi')
        );

        $path = $tmpDir.DIRECTORY_SEPARATOR.'sales_cut_'.Str::uuid()->toString().'.csv';
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo crear archivo CSV de respaldo.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $columns);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return ['path' => $path, 'filename' => $filename];
    }

    private function validateRange(Request $request): array
    {
        $data = $request->validate([
            'sucursal' => ['required', Rule::in(array_keys(SalesCutService::FILTERS))],
            'inicio' => ['required', 'date_format:Y-m-d\\TH:i'],
            'fin' => ['required', 'date_format:Y-m-d\\TH:i', 'after_or_equal:inicio'],
        ], $this->validationMessages());

        $inicio = $this->parseInputDate((string) $data['inicio']);
        $fin = $this->parseInputDate((string) $data['fin'], true);

        if (! $inicio || ! $fin) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'No se pudo interpretar el rango de fechas.');
        }

        return [$inicio, $fin, (string) $data['inicio'], (string) $data['fin'], (string) $data['sucursal']];
    }

    private function validationMessages(): array
    {
        return [
            'sucursal.required' => 'La sucursal es obligatoria.',
            'sucursal.in' => 'La sucursal seleccionada no es válida.',
            'inicio.required' => 'La fecha-hora de inicio es obligatoria.',
            'inicio.date_format' => 'La fecha-hora de inicio no tiene formato valido.',
            'fin.required' => 'La fecha-hora de fin es obligatoria.',
            'fin.date_format' => 'La fecha-hora de fin no tiene formato valido.',
            'fin.after_or_equal' => 'La fecha-hora de fin debe ser mayor o igual al inicio.',
        ];
    }

    private function resultUrl(string $inicio, string $fin, string $filter): string
    {
        return '/admin/corte-ventas?'.http_build_query([
            'inicio' => $inicio,
            'fin' => $fin,
            'sucursal' => $filter,
        ]);
    }

    private function parseInputDate(string $value, bool $endOfMinute = false): ?Carbon
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        try {
            $date = Carbon::createFromFormat('Y-m-d\\TH:i', $value, $timezone);
        } catch (\Throwable) {
            return null;
        }

        return $endOfMinute ? $date->setSecond(59) : $date->setSecond(0);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ExpenseCut\ExpenseCutExcelExportService;
use App\Services\ExpenseCut\ExpenseCutPrintService;
use App\Services\ExpenseCut\ExpenseCutService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminExpenseCutController extends Controller
{
    public function __construct(
        private readonly ExpenseCutService $expenseCutService,
        private readonly ExpenseCutPrintService $expenseCutPrintService,
        private readonly ExpenseCutExcelExportService $expenseCutExcelExportService,
    ) {
    }

    public function index(Request $request): View
    {
        $inicioInput = (string) $request->input('inicio', now()->toDateString());
        $finInput = (string) $request->input('fin', now()->toDateString());
        $resumen = null;

        if ($request->filled(['inicio', 'fin'])) {
            $inicio = $this->parseInputDate($inicioInput);
            $fin = $this->parseInputDate($finInput);

            if ($inicio && $fin && $inicio->lessThanOrEqualTo($fin)) {
                $resumen = $this->expenseCutService->summary($inicio, $fin);
            }
        }

        return view('admin.corte-gastos.index', [
            'inicio' => $inicioInput,
            'fin' => $finInput,
            'resumen' => $resumen,
        ]);
    }

    public function print(Request $request): RedirectResponse
    {
        [$inicio, $fin, $inicioInput, $finInput] = $this->validateRange($request);

        $summary = $this->expenseCutService->summary($inicio, $fin);
        $result = $this->expenseCutPrintService->print($summary);

        $flashKey = $result->printed ? 'ok' : 'error';
        $message = $result->printed
            ? 'Corte de gastos enviado a impresion de cocina.'
            : ($result->message ?: 'No se pudo imprimir el corte de gastos.');

        return redirect('/admin/corte-gastos?inicio=' . urlencode($inicioInput) . '&fin=' . urlencode($finInput))
            ->with($flashKey, $message);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        [$inicio, $fin, $inicioInput, $finInput] = $this->validateRange($request);

        $dataset = $this->expenseCutService->exportRows($inicio, $fin);

        try {
            $file = $this->expenseCutExcelExportService->createFile(
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

            return redirect('/admin/corte-gastos?inicio=' . urlencode($inicioInput) . '&fin=' . urlencode($finInput))
                ->with('error', $exception->getMessage());
        }
    }

    private function createCsvFallback(array $columns, array $rows, Carbon $inicio, Carbon $fin): array
    {
        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $filename = sprintf(
            'corte_gastos_%s_%s.csv',
            $inicio->format('Y-m-d'),
            $fin->format('Y-m-d')
        );

        $path = $tmpDir . DIRECTORY_SEPARATOR . 'expense_cut_' . Str::uuid()->toString() . '.csv';
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
            'inicio' => ['required', 'date_format:Y-m-d'],
            'fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:inicio'],
        ], [
            'inicio.required' => 'La fecha de inicio es obligatoria.',
            'inicio.date_format' => 'La fecha de inicio no tiene formato valido.',
            'fin.required' => 'La fecha de fin es obligatoria.',
            'fin.date_format' => 'La fecha de fin no tiene formato valido.',
            'fin.after_or_equal' => 'La fecha de fin debe ser mayor o igual al inicio.',
        ]);

        $inicio = $this->parseInputDate((string) $data['inicio']);
        $fin = $this->parseInputDate((string) $data['fin']);

        if (!$inicio || !$fin) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'No se pudo interpretar el rango de fechas.');
        }

        return [$inicio, $fin, (string) $data['inicio'], (string) $data['fin']];
    }

    private function parseInputDate(string $value): ?Carbon
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        try {
            return Carbon::createFromFormat('Y-m-d', $value, $timezone)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}

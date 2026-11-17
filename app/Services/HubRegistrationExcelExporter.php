<?php

namespace App\Services;

use App\Models\BookingLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

class HubRegistrationExcelExporter
{
    public function download(BookingLocation $location, Collection $fields, Builder $query)
    {
        $filename = Str::slug($location->slug).'-registrations-'.now()->format('Y-m-d').'.xlsx';
        $path = tempnam(sys_get_temp_dir(), 'hub-export-');
        $writer = new Writer;
        $writer->openToFile($path);
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Registrations');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
        $sheet->setColumnWidth(18, 1, 2);
        $sheet->setColumnWidthForRange(24, 3, count($fields) + 4);

        $headers = ['Registration ID', 'Hub', 'Status', ...$fields->pluck('label')->all(), 'Submitted At'];
        $headerStyle = (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('1E3A5F');
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        $query->with('files')->orderBy('id')->chunk(200, function ($registrations) use ($writer, $fields, $location) {
            foreach ($registrations as $registration) {
                $cells = [
                    Cell::fromValue((string) $registration->id),
                    Cell::fromValue($location->name),
                    Cell::fromValue(ucfirst($registration->status)),
                ];
                $files = $registration->files->keyBy('hub_registration_field_id');

                foreach ($fields as $field) {
                    $file = $files->get($field->id);

                    if ($field->type === 'file' && $file) {
                        $url = route('admin.hub-registrations.files.download', [$location, $file]);
                        $formula = '=HYPERLINK("'.str_replace('"', '""', $url).'","Open '.str_replace('"', '""', $file->original_name).'")';
                        $cells[] = Cell::fromValue($formula);
                    } else {
                        $cells[] = Cell::fromValue((string) ($field->type === 'file' ? '' : ($registration->answers[(string) $field->id] ?? '')));
                    }
                }

                $cells[] = Cell::fromValue($registration->submitted_at?->format('Y-m-d H:i:s') ?? '');
                $writer->addRow(new Row($cells));
            }
        });

        $writer->close();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}

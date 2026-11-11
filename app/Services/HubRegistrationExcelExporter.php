<?php

namespace App\Services;

use App\Models\BookingLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
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
        $sheet->setColumnWidthForRange(24, 3, count($fields) + 3);

        $headers = ['Registration ID', 'Hub', ...$fields->pluck('label')->all(), 'Submitted At'];
        $headerStyle = (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('1E3A5F');
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        $query->with('files')->orderBy('id')->chunk(200, function ($registrations) use ($writer, $fields, $location) {
            foreach ($registrations as $registration) {
                $values = [(string) $registration->id, $location->name];
                $files = $registration->files->keyBy('hub_registration_field_id');

                foreach ($fields as $field) {
                    $values[] = (string) ($field->type === 'file'
                        ? ($files->get($field->id)?->original_name ?? '')
                        : ($registration->answers[(string) $field->id] ?? ''));
                }

                $values[] = $registration->submitted_at?->format('Y-m-d H:i:s') ?? '';
                $writer->addRow(Row::fromValues($values));
            }
        });

        $writer->close();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}

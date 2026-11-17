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
use DOMDocument;
use DOMXPath;
use ZipArchive;

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
        $linkStyle = (new Style)->setFontColor('0563C1')->setFontUnderline();
        $hyperlinks = [];
        $rowNumber = 2;

        $query->with('files')->orderBy('id')->chunk(200, function ($registrations) use ($writer, $fields, $location, $linkStyle, &$hyperlinks, &$rowNumber) {
            foreach ($registrations as $registration) {
                $cells = [
                    Cell::fromValue((string) $registration->id),
                    Cell::fromValue($location->name),
                    Cell::fromValue(ucfirst($registration->status)),
                ];
                $files = $registration->files->keyBy('hub_registration_field_id');

                foreach ($fields as $fieldIndex => $field) {
                    $file = $files->get($field->id);

                    if ($field->type === 'file' && $file) {
                        $url = route('admin.hub-registrations.files.download', [$location, $file]);
                        $cells[] = Cell::fromValue('Open '.$file->original_name, $linkStyle);
                        $hyperlinks[] = [
                            'cell' => $this->columnLetters($fieldIndex + 4).$rowNumber,
                            'url' => $url,
                        ];
                    } else {
                        $cells[] = Cell::fromValue((string) ($field->type === 'file' ? '' : ($registration->answers[(string) $field->id] ?? '')));
                    }
                }

                $cells[] = Cell::fromValue($registration->submitted_at?->format('Y-m-d H:i:s') ?? '');
                $writer->addRow(new Row($cells));
                $rowNumber++;
            }
        });

        $writer->close();
        $this->addNativeHyperlinks($path, $hyperlinks);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function addNativeHyperlinks(string $path, array $hyperlinks): void
    {
        if ($hyperlinks === []) {
            return;
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return;
        }

        $sheetPath = 'xl/worksheets/sheet1.xml';
        $relationshipsPath = 'xl/worksheets/_rels/sheet1.xml.rels';
        $sheetXml = $zip->getFromName($sheetPath);

        if (! is_string($sheetXml)) {
            $zip->close();

            return;
        }

        $sheet = new DOMDocument('1.0', 'UTF-8');
        $sheet->preserveWhiteSpace = false;
        $sheet->loadXML($sheetXml);
        $mainNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $hyperlinksNode = $sheet->createElementNS($mainNamespace, 'hyperlinks');

        $relationships = new DOMDocument('1.0', 'UTF-8');
        $relationships->preserveWhiteSpace = false;
        $existingRelationships = $zip->getFromName($relationshipsPath);

        if (is_string($existingRelationships)) {
            $relationships->loadXML($existingRelationships);
        } else {
            $relationships->appendChild($relationships->createElementNS(
                'http://schemas.openxmlformats.org/package/2006/relationships',
                'Relationships'
            ));
        }

        foreach ($hyperlinks as $index => $hyperlink) {
            $relationshipId = 'rIdHubRegistration'.($index + 1);
            $node = $sheet->createElementNS($mainNamespace, 'hyperlink');
            $node->setAttribute('ref', $hyperlink['cell']);
            $node->setAttributeNS($relationshipNamespace, 'r:id', $relationshipId);
            $hyperlinksNode->appendChild($node);

            $relationship = $relationships->createElementNS(
                'http://schemas.openxmlformats.org/package/2006/relationships',
                'Relationship'
            );
            $relationship->setAttribute('Id', $relationshipId);
            $relationship->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink');
            $relationship->setAttribute('Target', $hyperlink['url']);
            $relationship->setAttribute('TargetMode', 'External');
            $relationships->documentElement->appendChild($relationship);
        }

        $worksheet = $sheet->documentElement;
        $xpath = new DOMXPath($sheet);
        $xpath->registerNamespace('x', $mainNamespace);
        $insertBefore = $xpath->query('/x:worksheet/x:printOptions | /x:worksheet/x:pageMargins | /x:worksheet/x:pageSetup | /x:worksheet/x:headerFooter | /x:worksheet/x:drawing | /x:worksheet/x:legacyDrawing | /x:worksheet/x:tableParts | /x:worksheet/x:extLst')->item(0);
        $worksheet->insertBefore($hyperlinksNode, $insertBefore);

        $zip->addFromString($sheetPath, $sheet->saveXML());
        $zip->addFromString($relationshipsPath, $relationships->saveXML());
        $zip->close();
    }

    private function columnLetters(int $column): string
    {
        $letters = '';

        while ($column > 0) {
            $column--;
            $letters = chr(65 + ($column % 26)).$letters;
            $column = intdiv($column, 26);
        }

        return $letters;
    }
}

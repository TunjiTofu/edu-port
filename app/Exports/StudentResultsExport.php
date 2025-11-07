<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StudentResultsExport implements WithMultipleSheets
{
    protected $studentsData;

    public function __construct(array $studentsData)
    {
        $this->studentsData = $studentsData;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Summary sheet for all students
        $sheets[] = new StudentResultsSummarySheet($this->studentsData);

        // Individual sheet for each student
        foreach ($this->studentsData as $index => $studentData) {
            $sheets[] = new StudentResultDetailSheet($studentData, $index);
        }

        return $sheets;
    }
}

class StudentResultsSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $studentsData;
    protected $rowNumber = 0;

    public function __construct(array $studentsData)
    {
        $this->studentsData = $studentsData;
    }

    public function collection()
    {
        return collect($this->studentsData);
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Email',
            'Phone',
            'Church',
            'District',
            'Total Tasks',
            'Submitted',
            'Not Submitted',
            'Total Score',
            'Max Score',
            'Percentage %',
            'Score /100',
            'Score /60',
        ];
    }

    public function map($studentData): array
    {
        $this->rowNumber++;

        return [
            $studentData['student']['name'],
            $studentData['student']['email'],
            $studentData['student']['phone'],
            $studentData['student']['church'] ?? 'N/A',
            $studentData['student']['district'] ?? 'N/A',
            $studentData['summary']['total_tasks'],
            $studentData['summary']['submitted_count'],
            $studentData['summary']['not_submitted_count'],
            number_format($studentData['summary']['total_score'], 2),
            number_format($studentData['summary']['max_score'], 2),
            number_format($studentData['summary']['percentage'], 2) . '%',
            number_format($studentData['summary']['score_out_of_100'], 2),
            number_format($studentData['summary']['score_out_of_60'], 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}

class StudentResultDetailSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $studentData;
    protected $index;
    protected $currentRow = 0;

    public function __construct(array $studentData, int $index)
    {
        $this->studentData = $studentData;
        $this->index = $index;
    }

    public function collection()
    {
        $data = collect();

        // Student information
        $data->push(['section' => 'STUDENT INFORMATION']);
        $data->push(['label' => 'Name', 'value' => $this->studentData['student']['name']]);
        $data->push(['label' => 'Email', 'value' => $this->studentData['student']['email']]);
        $data->push(['label' => 'Phone', 'value' => $this->studentData['student']['phone']]);
        $data->push(['label' => 'Church', 'value' => $this->studentData['student']['church'] ?? 'N/A']);
        $data->push(['label' => 'District', 'value' => $this->studentData['student']['district'] ?? 'N/A']);
        $data->push(['section' => '']); // Empty row

        // Overall summary
        $data->push(['section' => 'OVERALL SUMMARY']);
        $data->push(['label' => 'Total Tasks', 'value' => $this->studentData['summary']['total_tasks']]);
        $data->push(['label' => 'Tasks Submitted', 'value' => $this->studentData['summary']['submitted_count']]);
        $data->push(['label' => 'Tasks Not Submitted', 'value' => $this->studentData['summary']['not_submitted_count']]);
        $data->push(['label' => 'Total Score', 'value' => number_format($this->studentData['summary']['total_score'], 2) . ' / ' . number_format($this->studentData['summary']['max_score'], 2)]);
        $data->push(['label' => 'Percentage', 'value' => number_format($this->studentData['summary']['percentage'], 2) . '%']);
        $data->push(['label' => 'Score out of 100', 'value' => number_format($this->studentData['summary']['score_out_of_100'], 2)]);
        $data->push(['label' => 'Score out of 60', 'value' => number_format($this->studentData['summary']['score_out_of_60'], 2)]);
        $data->push(['section' => '']); // Empty row

        // Section breakdown
        foreach ($this->studentData['sections'] as $section) {
            $data->push(['section' => 'SECTION: ' . strtoupper($section['name'])]);
            $data->push(['label' => 'Section Score', 'value' => number_format($section['total_score'], 2) . ' / ' . number_format($section['max_score'], 2) . ' (' . number_format($section['percentage'], 2) . '%)']);
            $data->push(['section' => '']); // Empty row

            // Tasks header
            $data->push([
                'section' => 'Task',
                'label' => 'Max Score',
                'value' => 'Score',
                'extra1' => 'Status',
                'extra2' => 'Comments'
            ]);

            foreach ($section['tasks'] as $task) {
                $data->push([
                    'section' => $task['title'],
                    'label' => $task['max_score'],
                    'value' => $task['score'] ?? 'N/A',
                    'extra1' => $task['status'],
                    'extra2' => $task['comments'] ?? 'No comments'
                ]);
            }

            $data->push(['section' => '']); // Empty row
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Field', 'Value', '', '', ''];
    }

    public function map($row): array
    {
        $this->currentRow++;

        if (isset($row['section']) && !isset($row['label'])) {
            return [$row['section'], '', '', '', ''];
        }

        if (isset($row['extra1'])) {
            return [
                $row['section'] ?? '',
                $row['label'] ?? '',
                $row['value'] ?? '',
                $row['extra1'] ?? '',
                $row['extra2'] ?? ''
            ];
        }

        return [
            $row['label'] ?? '',
            $row['value'] ?? '',
            '',
            '',
            ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(40);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        $name = $this->studentData['student']['name'];
        // Sanitize sheet name (max 31 chars, no special chars)
        $name = substr($name, 0, 31);
        $name = preg_replace('/[^A-Za-z0-9 _-]/', '', $name);
        return $name ?: 'Student ' . ($this->index + 1);
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class CompoundCalculatorExport implements FromArray, WithEvents, WithHeadings, ShouldAutoSize
{
    protected $invested;
    protected $firstReward;
    protected $signals;
    protected $days;
    protected $firstTime;
    protected $maxSignals;

    public function __construct($invested, $firstReward, $signals, $days, $firstTime)
    {
        $this->invested = $invested;
        $this->firstReward = $firstReward;
        $this->signals = max(1, $signals);
        $this->days = max(1, $days);
        $this->firstTime = (bool) $firstTime;
        $this->maxSignals = $this->firstTime ? 5 : $this->signals;
    }

    protected function colLetter($n)
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intval(($n - 1) / 26);
        }
        return $s;
    }

    public function headings(): array
    {
        $cols = ['Day', 'Start'];
        for ($s = 1; $s <= $this->maxSignals; $s++) {
            $cols[] = "Rate $s";
            $cols[] = "Amt $s";
            $cols[] = "Gain $s";
            $cols[] = "After $s";
        }
        $cols[] = 'End';
        return $cols;
    }

    public function array(): array
    {
        $rows = [];

        // Add export timestamp and inputs as first rows
        $rows[] = ['Generated At', date('Y-m-d H:i:s')];
        $rows[] = ['Inputs', 'FirstTime', 'SignalsDefault', 'Invested', 'FirstReward'];
        $rows[] = [ $this->firstTime ? 1 : 0, '', $this->signals, $this->invested, $this->firstReward ];

        // Header row will be created by headings
        $rows[] = $this->headings();

        // compute values server-side (no formulas) so Excel contains exact numbers
        $currentAssets = $this->invested + $this->firstReward;

        for ($day = 1; $day <= $this->days; $day++) {
            $row = [];
            $row[] = $day;

            // Start of day assets
            $row[] = round($currentAssets, 2);

            // Determine daySignals
            if ($this->firstTime) {
                if ($day === 1) $daySignals = 2;
                elseif ($day >= 2 && $day <= 6) $daySignals = 5;
                else $daySignals = 2;
            } else {
                $daySignals = $this->signals;
            }

            // Process each signal and compute values
            for ($s = 1; $s <= $this->maxSignals; $s++) {
                if ($s <= $daySignals) {
                    // rate between 0.50 and 0.52
                    $rate = mt_rand(5000, 5200) / 10000;
                    $signalAmount = $currentAssets * 0.01;
                    $gain = $signalAmount * $rate;
                    $currentAssets += $gain;

                    $row[] = round($rate, 4);     // rate (decimal)
                    $row[] = round($signalAmount, 2); // 1% amount
                    $row[] = round($gain, 2);     // gain
                    $row[] = round($currentAssets, 2); // after
                } else {
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                }
            }

            // End of day total
            $row[] = round($currentAssets, 2);

            $rows[] = $row;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Header styling (header row index depends on added inputs rows)
                $headerRow = 4; // we added Generated At + Inputs rows
                $lastCol = 2 + ($this->maxSignals * 4) + 1; // include End
                $range = 'A' . $headerRow . ':' . $this->colLetter($lastCol) . $headerRow;
                $sheet->getStyle($range)->getFont()->setBold(true);
                $sheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DDEBF7');

                // Shade inputs rows
                $sheet->getStyle('A1:E2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8F0E3');

                // apply thin borders over data range
                $sheet->getStyle('A4:' . $this->colLetter($lastCol) . ($this->days + 4))
                    ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Freeze panes: keep headers visible
                $sheet->freezePane('C5');
            }
        ];
    }
}

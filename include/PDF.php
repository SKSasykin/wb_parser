<?php

declare(strict_types=1);

namespace Inc;

use TCPDF;

define('K_TCPDF_THROW_EXCEPTION_ERROR', true);

class PDF
{
    private TCPDF $pdf;

    public function __construct()
    {
// create new PDF document
        $this->pdf = new TCPDF('L', PDF_UNIT, [138, 102], true, 'UTF-8', false);

// set default monospaced font
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
//$this->pdf->SetMargins(0, 0, 0);
//$this->pdf->SetHeaderMargin(0);
        $this->pdf->SetFooterMargin(0);

// set auto page breaks
        $this->pdf->SetAutoPageBreak(true, 0);
        $this->pdf->setTopMargin(1);

// set image scale factor
//$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    }

    public function addBarcodePage($svgData)
    {
        $this->pdf->AddPage();
        $this->pdf->Ln();
        $this->pdf->ImageSVG('@' . $svgData, 2, 0, 134);
        $this->pdf->Ln();
    }

    public function addOwnerPage($code, $art, $contains, $constructor = 'ИП Кравченко Д.Ю.')
    {
        $this->pdf->AddPage();

        $this->pdf->SetFont('dejavusans', 'B', 14, __DIR__ . '/../font/dejavusansb.php');

// define barcode style
        $style = [
            'position'     => '',
            'align'        => 'N',
            'stretch'      => false,
            'fitwidth'     => true,
            'cellfitalign' => 'auto',
            'border'       => false,
            'hpadding'     => 0,
            'vpadding'     => 0,
            'fgcolor'      => [0, 0, 0],
            'bgcolor'      => false, //array(255,25PDF_MARGIN_HEADER5,255),
            'text'         => false,
            'font'         => 'helvetica',
            'fontsize'     => 8,
            'stretchtext'  => 4,
        ];

        try {
            $this->pdf->write1DBarcode($code, 'EAN13', 20, 5, 130, 30, 1, $style, 'N');
            $this->pdf->Ln(5);
        } catch (\Throwable $throwable) {
            $this->pdf->Cell(0, 0, "ERROR EAN13 CODE", 2, 1);
            $this->pdf->Ln();
            echo "\n[!] error barcode: $code\n";
        }
        $this->pdf->Cell(0, 0, $code, 0, 1, 'C');

        $this->pdf->Ln();
        $this->pdf->SetFont('dejavusans', '', 12, __DIR__ . '/../font/dejavusans.php');
        $this->pdf->Cell(0, 0, "Арт. $art", 0, 1);
        $this->pdf->Cell(0, 0, "Изготовитель: $constructor", 0, 1);
        $this->pdf->Cell(0, 0, "Состав: $contains", 0, 1);
        $this->pdf->Cell(0, 0, "ТР ТС 017/2011", 0, 1);
        $this->pdf->Image(__DIR__ . '/../images/footer.png', 10, 80, 64);
        $this->pdf->Ln();
    }

    public function output($file = 'file.pdf')
    {
        $this->pdf->Output($file, 'F');
    }
}

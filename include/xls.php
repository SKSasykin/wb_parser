<?php

declare(strict_types=1);

class XLS
{
    const HEADERS = [
        'A' => "Изображение",
        'B' => "Номер задачи",
        'C' => "Баркод",
        'D' => "Артикул",
        'E' => "Нуменклатура",
        'F' => "Модель",

        'H' => "Ссылка на изображение",
        'I' => "Название",
    ];

    private $index = 2;

    public function __construct()
    {
        $this->xls = new PHPExcel();
        $this->xls->setActiveSheetIndex(0);
        $this->sheet = $this->xls->getActiveSheet();
//        $this->sheet->setTitle('Название листа');
        $this->sheet->getColumnDimension('A')->setWidth(20);
        $this->sheet->getColumnDimension('B')->setWidth(15);
        $this->sheet->getColumnDimension('C')->setWidth(20);
        $this->sheet->getColumnDimension('D')->setWidth(60);
        $this->sheet->getColumnDimension('E')->setWidth(15);
        $this->sheet->getColumnDimension('F')->setWidth(40);

        foreach (self::HEADERS as $cell => $header) {
            $this->sheet->setCellValue("{$cell}1", $header);
        }
    }

    public function addRow($file, $order, $barcode, $sku, $num, $model, $name, $fileUrl)
    {
        if ($file) {
            $logo = new PHPExcel_Worksheet_Drawing();
            $logo->setPath($file);
            $logo->setCoordinates('A' . $this->index);
            $logo->setOffsetX(0);
            $logo->setOffsetY(0);
            $logo->setHeight(133);
            $logo->setWidth(133);
            $logo->setWorksheet($this->sheet);
        }

        $this->sheet->getRowDimension($this->index)->setRowHeight(133);
        $this->sheet->setCellValue('B' . $this->index, $order);
        $this->sheet->setCellValue('C' . $this->index, $barcode);
        $this->sheet->setCellValue('D' . $this->index, $sku);
        $this->sheet->setCellValue('E' . $this->index, $num);
        $this->sheet->setCellValue('F' . $this->index, $model);

        $this->sheet->setCellValue('H' . $this->index, $fileUrl);
        $this->sheet->setCellValue('I' . $this->index, $name);

        $this->index++;
    }

    public function output($file = 'file.xls')
    {
        PHPExcel_IOFactory::createWriter($this->xls, 'Excel5')->save($file);
    }
}
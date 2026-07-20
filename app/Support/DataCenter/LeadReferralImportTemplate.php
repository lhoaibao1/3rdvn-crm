<?php

namespace App\Support\DataCenter;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Throwable;

final class LeadReferralImportTemplate
{
    private const HEADERS = [
        'Họ tên khách hàng',
        'Số điện thoại',
        'UID người xử lý',
        'Email khách hàng',
        'CCCD/CMND',
        'Ngày sinh',
        'Địa chỉ chi tiết',
        'Tỉnh/Thành phố',
        'Quận/Huyện',
        'Phường/Xã',
        'Nguồn',
    ];

    public static function create(): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'lead-referral-');

        if ($temporaryFile === false) {
            throw new RuntimeException('Không thể tạo file mẫu Lead Referral.');
        }

        @unlink($temporaryFile);
        $path = $temporaryFile.'.xlsx';
        $writer = new Writer;
        $opened = false;

        try {
            $writer->openToFile($path);
            $opened = true;

            $headerStyle = (new Style)
                ->setFontBold()
                ->setFontColor('FFFFFF')
                ->setBackgroundColor('2563EB')
                ->setShouldWrapText();

            $textStyle = (new Style)->setFormat('@');
            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Lead Referral');
            $sheet->getSheetView()?->setFreezeRow(2);
            $sheet->setColumnWidth(28, 1);
            $sheet->setColumnWidth(18, 2);
            $sheet->setColumnWidth(20, 3);
            $sheet->setColumnWidth(28, 4);
            $sheet->setColumnWidth(20, 5);
            $sheet->setColumnWidth(16, 6);
            $sheet->setColumnWidth(34, 7);
            $sheet->setColumnWidth(22, 8, 9, 10);
            $sheet->setColumnWidth(20, 11);

            $writer->addRow(Row::fromValues(self::HEADERS, $headerStyle));
            $writer->addRow(Row::fromValuesWithStyles(
                array_fill(0, count(self::HEADERS), ''),
                null,
                array_fill(0, count(self::HEADERS), $textStyle),
            ));

            $guideTitleStyle = (new Style)
                ->setFontBold()
                ->setFontColor('FFFFFF')
                ->setBackgroundColor('0F172A');

            $guide = $writer->addNewSheetAndMakeItCurrent();
            $guide->setName('Hướng dẫn');
            $guide->setColumnWidth(26, 1);
            $guide->setColumnWidth(90, 2);
            $writer->addRow(Row::fromValues(['Nội dung', 'Hướng dẫn'], $guideTitleStyle));
            $writer->addRow(Row::fromValues(['Cột bắt buộc', 'Họ tên khách hàng, Số điện thoại, UID người xử lý.']));
            $writer->addRow(Row::fromValues(['UID người xử lý', 'Nhập đúng UID đang tồn tại trong CRM và thuộc phạm vi quản lý của người import.']));
            $writer->addRow(Row::fromValues(['Ngày sinh', 'Nhập theo định dạng dd/mm/yyyy, ví dụ 31/12/2000.']));
            $writer->addRow(Row::fromValues(['Lưu ý', 'Không đổi tên cột, không gộp ô và không xóa sheet Lead Referral.']));

            $writer->close();
            $opened = false;

            return $path;
        } catch (Throwable $exception) {
            if ($opened) {
                try {
                    $writer->close();
                } catch (Throwable) {
                }
            }

            @unlink($path);

            throw $exception;
        }
    }
}

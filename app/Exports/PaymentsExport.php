<?php


namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentsExport implements FromCollection, WithHeadings
{
    protected $payments;

    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    /**
     * Возвращаем коллекцию данных для экспорта.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->payments);
    }

    /**
     * Устанавливаем заголовки для таблицы Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Номер платежа',
            'Статус',
            'Дата платежа',
            'Сумма',
            'Счет',
            'Примечание',
            'Менеджер',
        ];
    }
}

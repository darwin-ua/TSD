<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->products);
    }

    /**
     * Устанавливаем заголовки для таблицы Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Заказ',
            'Контрагент',
            'Создал',
            'Статус',
            'Запуск',
            'Группа',
            'Заказчик',
            'Дата изг.',
            'Дост. логист.',
            'Адрес',
            'Оплачен',
            'Стоимость',
            'Дейс.',
            'Готов',
        ];
    }
}


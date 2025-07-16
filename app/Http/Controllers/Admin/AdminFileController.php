<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class AdminFileController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            // Получение файла
            $file = $request->file('file');

            // Генерация уникального имени файла
            $filename = time() . '_' . $file->getClientOriginalName();

            // Сохранение файла в папку storage/app/public/files
            $path = $file->storeAs('public/files', $filename);

            // Возвращение пути к загруженному файлу
            return $path;
        } else {
            // Если файл не был загружен, вернуть сообщение об ошибке
            return "Файл не был загружен";
        }
    }

    public function allupload(Request $request)
    {
        dd($request);
        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
            $uploadedPaths = []; // Здесь мы будем хранить пути к загруженным файлам
            foreach ($photos as $photo) {
                // Пример обработки каждой загруженной фотографии
                $filename = time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('public/photos', $filename); // Измененный путь для сохранения файла
                $uploadedPaths[] = $path; // Добавляем путь к загруженному файлу в массив
            }
            return $uploadedPaths; // Возвращаем массив путей к загруженным файлам
        }
        return "No photos to upload!";
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use App\Models\Region;
use App\Models\Timework;
use App\Models\User;
use App\Models\Lesson;
use App\Models\LessonType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use App\Models\Shedule;
use App\Models\Statistic;
use App\Models\PortfolioFoto;
use Illuminate\Support\Facades\DB;
use App\Models\LessonFile;
use App\Models\Town;


class AdminEventController extends Controller
{
    public function deleteFoto(Request $request)
    {

        $fileName = $request->input('fileName');
        $fileExists = true;

        if ($fileExists) {
            return response()->json(['success' => true, 'fileName' => $fileName]);
        } else {
            return response()->json(['success' => false, 'error' => 'File not found'], 404);
        }
    }

    public function index()
    {
        $admins = User::where('role_id', 1)->get();

        $currentAdmin = auth()->user();
        if ($currentAdmin->role_id == 1) {
            $events = Event::where('status', 1)->paginate(10);
        } elseif ($currentAdmin->role_id == 3 or $currentAdmin->role_id == 2) {
            $events = Event::where('user_id', $currentAdmin->id)
                ->where('status', 1)
                ->paginate(10000);
        }

        $qrOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
        ]);

        foreach ($events as $event) {
            $qrcode = new QRCode($qrOptions);
            $qrCodeData = $qrcode->render('/' . $event->id);
            $event->qrCodeData = $qrCodeData;
        }

        return view('admin.events.index', compact('admins', 'currentAdmin', 'events'));
    }

    public function settings()
    {
        $admins = User::where('role_id', 1)->get();

        $currentAdmin = auth()->user();
        if ($currentAdmin->role_id == 1) {
            $events = Event::where('status', 1)->paginate(10);
        } elseif ($currentAdmin->role_id == 3 or $currentAdmin->role_id == 2) {
            $events = Event::where('user_id', $currentAdmin->id)
                ->where('status', 1)
                ->paginate(10);
        }
        $qrOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
        ]);

        foreach ($events as $event) {
            $qrcode = new QRCode($qrOptions);
            $qrCodeData = $qrcode->render('/' . $event->id);
            $event->qrCodeData = $qrCodeData;
        }

        return view('admin.events.settings', compact('admins', 'currentAdmin', 'events'));
    }

    public function statistic()
    {
        $admins = User::where('role_id', 1)->get();

        $currentAdmin = auth()->user();
        if ($currentAdmin->role_id == 1) {
            $events = Event::where('status', 1)->paginate(10);
        } elseif ($currentAdmin->role_id == 3 or $currentAdmin->role_id == 2) {
            $events = Event::where('user_id', $currentAdmin->id)
                ->where('status', 1)
                ->paginate(10);
        }
        $qrOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
        ]);

        foreach ($events as $event) {
            $qrcode = new QRCode($qrOptions);
            $qrCodeData = $qrcode->render('/' . $event->id);
            $event->qrCodeData = $qrCodeData;
        }

        return view('admin.events.statistic', compact('admins', 'currentAdmin', 'events'));
    }

    public function lesson($id)
    {
        $currentAdmin = auth()->user();
        $user = Auth::user();
        $admins = User::where('role_id', 1)->get();
        $events = Event::all();
        $event = Event::findOrFail($id);
        $scheduleExists = Shedule::where('event_id', $id)->where('status', 1)->exists();
        $sheduleRes = $scheduleExists ? 1 : 0;
        $schedules = [];
        foreach ($admins as $admin) {
            $schedules[$admin->id] = Shedule::where('event_id', $id)->where('status', 1)->get();
        }

        return view('admin.events.lesson', compact('admins', 'currentAdmin', 'event', 'events', 'schedules', 'sheduleRes'));
    }

    public function storeLesson(Request $request)
    {

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $event = $this->lessonSaveData($request, $user);

        if ($event) {
            $this->processAllFotos($request->file('allfoto'), $user, $event->id);
            $schedule_id = $request->input('shedule_id');
        }

        return redirect()->route('admin.events.index')->with('success', 'Event created successfully');
    }

    public function lessonSaveData(Request $request)
    {

        $user = Auth::user();
        $additional_fields = json_decode($request->additional_fields, true);

        $files = $request->file('allfoto'); // Получаем массив файлов
        foreach ($additional_fields as $index => $field) {
            $event = new Lesson();
            $event->user_id = $user->id;
            $event->events_id = $request->eventId;
            $event->lesson_chapter = $index;
            $event->terms = $request->title;
            $event->title = $field['value'];

            // Динамическое получение описания, как обсуждалось ранее
            $descriptionKey = "description_" . $index;
            $eventDescription = $request->input($descriptionKey);
            $event->description = $eventDescription ? $eventDescription : 'Значение по умолчанию';

            if (isset($files[$index])) { // Проверяем, существует ли файл для текущего индекса
                $file = $files[$index]; // Получаем файл для текущего события
                $foto_title = $file->getClientOriginalName(); // Получаем оригинальное имя файла
                $event->foto_title = $foto_title;
            } else {
                $event->foto_title = 'default_name.jpg'; // Или другое значение по умолчанию
            }

            $event->updated_at = now();
            $event->created_at = now();
            $additional_fields = json_decode($request->additional_fields);
            $event->add_fields = json_encode($additional_fields);
            $event->save();
        }

        return redirect()->route('admin.events.lesson', ['id' => $event->events_id])->with('success', 'Event created successfully');

    }

    public function uploadVideo(Request $request)
    {
        $user = Auth::user();
        $videoFiles = $request->file('allvideo');
        $fieldIndex = $request->input('fieldIndex');
        $eventId = $request->input('eventId');
        $uploadedFilesInfo = [];

        foreach ($videoFiles as $file) {

            $path = $file->store('public/videos');
            $generatedFileName = basename($path);
            $lessonFile = new LessonFile();
            $lessonFile->user_id = 1;
            $lessonFile->events_id = $eventId;
            $lessonFile->lesson_chapter = $fieldIndex;
            $lessonFile->text = $generatedFileName;
            $lessonFile->status = 1;
            $lessonFile->save();

            $uploadedFilesInfo[] = [
                'originalName' => $file->getClientOriginalName(),
                'generatedName' => $generatedFileName
            ];
        }

        return response()->json([
            'message' => 'Files uploaded successfully!',
            'files' => $uploadedFilesInfo
        ]);
    }

    public function searchTown($number){

        $crimeaRegionCode = $number;

        $crimeaRegionCode = $number; // первые две цифры


        $citiesOfCrimea = Town::where('code', 'like', $crimeaRegionCode . '%')->get();

        return $citiesOfCrimea;
    }

    public function create()
    {
        $currentAdmin = auth()->user();
        $user = Auth::user();
        $admins = User::where('role_id', 1)->get();
        $events = Event::all();
        $scheduleExists = Shedule::where('user_id', $user->id)->where('status', 0)->orWhere('status', 1)->exists();
        $sheduleRes = $scheduleExists ? 1 : 0;
        $regions = Region::take(100)->get();

        $cities = [];
        if ($regions->isNotEmpty()) {
            $firstRegion = $regions->first();
            $cities = $firstRegion->towns;
        }

        $schedules = [];
        foreach ($admins as $admin) {
            $schedules[$admin->id] = Shedule::where('user_id', $user->id)->where('status', 0)->orWhere('status', 1)->get();
        }


        return view('admin.events.create', compact('admins', 'currentAdmin', 'regions', 'events', 'schedules', 'sheduleRes'));
    }
    function generateSlug($string)
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/u', '-', $string);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = mb_strtolower($slug);
        return $slug;
    }

    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $event = $this->createEvent($request, $user);

        if ($event) {

            $this->processAllFotos($request->file('allfoto'), $user, $event->id);
//            $schedule_id = $request->input('shedule_id');
//            Shedule::where('id', $schedule_id)->update(['event_id' => $event->id, 'status' => 1]);
        }

        return redirect()->route('admin.events.index')->with('success', 'Event created successfully');
    }

    protected function createEvent($request, $user)
    {

        $event = new Event();
        $event->user_id = $user->id;
        $event->title = $request->title;
        $event->category = $request->category;
        $event->town_id = $request->town;
        $event->piple = $request->piple;
        $event->data_create_order = '';
        $event->description = $request->input('description');
        $event->slug = $this->generateSlug($request->title);
        $event->type_pay = $request->type_pay;
        $event->sub_category = $request->sub_category;
        $event->phone = $request->phone;
        $event->currency = $request->currency;
        $event->start_date = $request->shedule_date_from;
        $event->end_date = $request->shedule_date_to;
        $event->amount = $request->amount;
        //$event->shedule_id = $request->shedule_id;
        $event->foto_folder_id = $request->foto_folder_id;
        $event->discounte = $request->input('discount');
        $event->updated_at = now();
        $event->created_at = now();
        $additional_fields = json_decode($request->additional_fields);
        $event->add_fields = json_encode($additional_fields);
        $event->social_show_facebook = $request->input('social_show_facebook', '');
        $event->social_show_instagram = $request->input('social_show_instagram', '');
        $event->is_live = $request->input('is_live', '');
        $event->is_links = $request->input('is_links', '');
        $event->social_show_youtube = $request->input('social_show_youtube', '');
        $event->social_show_telegram = $request->input('social_show_telegram', '');
        $event->social_show_x = $request->input('social_show_x', '');
        $event->save();

        $userFolder = public_path('storage/files/' . $user->id);
        if (!file_exists($userFolder)) {
            mkdir($userFolder, 0777, true);
        }

        $eventFolder = $userFolder . '/' . $event->id;
        if (!file_exists($eventFolder)) {
            mkdir($eventFolder, 0777, true);
        }

        return $event;
    }

    protected function processAllFotos($allFotos, $user, $eventId)
    {
        if ($allFotos) {
            foreach ($allFotos as $foto) {
                $uniqueFilename = $user->id . '_' . time() . '_' . $foto->getClientOriginalName();
                $path = 'files/' . $user->id . '/' . $eventId;
                $foto->move(public_path($path), $uniqueFilename);

                PortfolioFoto::create([
                    'event_id' => $eventId,
                    'title' => $uniqueFilename,
                ]);
            }
        }
    }

    public function show($id)
    {
        $event = Event::find($id);
        if (!$event) {
            return abort(404);
        }
        return view('admin.events.show', compact('event',));
    }

    public function edit($id)
    {

        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();
        $event = Event::findOrFail($id);

        $qrOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
        ]);

        $qrcode = new QRCode($qrOptions);
        $qrCodeData = $qrcode->render('/' . $event->id);


//        $schedule = Shedule::where('event_id', $event->id)
//            ->where('status', 1)
//            ->firstOrFail();

        $lessonTitles = Lesson::where('events_id', $event->id)
            ->where('lesson_chapter', 0)
            ->pluck('title');

        $nearestDate = PortfolioFoto::where('event_id', $id)
            ->whereDate('created_at', '<=', now()->toDateString())
            ->orderByDesc('created_at')
            ->value('created_at');

        if ($nearestDate !== null) {
            $nearestDateFiles = PortfolioFoto::where('event_id', $id)
                ->whereDate('created_at', $nearestDate->toDateString())
                ->pluck('title')
                ->toArray();
        } else {
            $nearestDateFiles = [];
        }

        $latestFotosString = implode(', ', $nearestDateFiles);
        $lessonType = LessonType::where('events_id', $event->id)
            ->orderBy('updated_at', 'desc')
            ->first();

        return view('admin.events.edit', compact('admins','lessonType', 'lessonTitles', 'event', 'currentAdmin', 'qrCodeData',  'latestFotosString'));
    }

    public function redactLessonUpdate($id)
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();
        $event = Event::findOrFail($id);
        $lesson = 0;

        redirect()->route('admin.events.redactLesson', compact('id','lesson'));
    }

    public function redactLesson($id,$lesson)
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();
        $event = Event::findOrFail($id);

        return view('admin.events.redactLesson', compact('admins','lesson', 'event', 'currentAdmin'));
    }

    public function update(Request $request, $id)
    {

        $currentAdmin = auth()->user();
        $event = Event::findOrFail($id);
        if ($request->has('description')) {
            $event->description = $request->input('description');
        }
        //$event->title = $request->input('title');
        $event->amount = $request->input('amount');
        $event->currency = $request->input('currency');
        $event->location = $request->input('location');
        $event->type_pay = $request->input('type_pay');
        $event->online = $request->input('online');
        $event->discounte = $request->input('discount');
        $event->piple = $request->input('piple');

        $event->social_show_facebook = $request->input('social_show_facebook', '');
        $event->social_show_instagram = $request->input('social_show_instagram', '');
        $event->is_live = $request->input('is_live', '');
        $event->is_links = $request->input('is_links', '');
        $event->social_show_youtube = $request->input('social_show_youtube', '');
        $event->social_show_telegram = $request->input('social_show_telegram', '');
        $event->social_show_x = $request->input('social_show_x', '');
        $event->save();

        $lessonType = new LessonType();
        $lessonType->events_id = $id;
        $lessonType->type = 1;
        $lessonType->customCheckbox1 = isset($request->customCheckbox1) ? 1 : 0;
        $lessonType->customCheckbox2 = isset($request->customCheckbox2) ? 1 : 0;
        $lessonType->timeFrom1Group1 = $request->input('timeFrom1Group1');
        $lessonType->timeTo1Group1 = $request->input('timeTo1Group1');
        $lessonType->discount1 = $request->input('discount1');

        $lessonType->timeFrom1Group2 = $request->input('timeFrom1Group2');
        $lessonType->timeTo1Group2 = $request->input('timeTo1Group2');
        $lessonType->discount2 = $request->input('discount2');

        $lessonType->timeFrom22Group22 = $request->input('timeFrom22Group22');
        $lessonType->timeTo22Group22 = $request->input('timeTo22Group22');
        $lessonType->discount3 = $request->input('discount3');

        $lessonType->timeFrom33Group33 = $request->input('timeFrom33Group33');
        $lessonType->timeTo33Group33 = $request->input('timeTo33Group33');
        $lessonType->discount4 = $request->input('discount4');
        $lessonType->save();


        if ($request->has('deleteImages')) {
            $imagesToDelete = $request->input('deleteImages');

            PortfolioFoto::whereIn('title', $imagesToDelete)->delete();
        }

        $this->processAllFotos($request->file('allfoto'), $currentAdmin, $id); // Передайте пользователя и ID события

        return redirect()->route('admin.events.edit', ['event' => $event->id])->with('success', 'Событие успешно обновлено');
    }

    public function destroy($id)
    {
        $event = Event::find($id);
        if (!$event) {
            return abort(404);
        }

        $event->update(['status' => 0]);

        return redirect()->route('admin.events.index')->with('success', 'Event status updated successfully');
    }
}


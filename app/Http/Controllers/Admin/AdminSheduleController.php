<?php
namespace App\Http\Controllers\Admin; // Укажите правильное пространство имен

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shedule;
use App\Models\Timework;
use Illuminate\Support\Facades\Auth;

class AdminSheduleController extends Controller
{
    public function index()
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();
        $shedules = Shedule::paginate(10); // Здесь 10 - это количество элементов на странице
        return view('admin.shedule.index', compact('admins','currentAdmin', 'shedules'));
    }

    public function settings()
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();
        $shedules = Shedule::all();

        return view('admin.shedule.settings', compact('admins', 'currentAdmin','shedules'));
    }

    public function create()
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();

        return view('admin.shedule.create',compact('admins','currentAdmin'));
    }

    public function store(Request $request)
    {
        if ($request->has('reserv')) {
            $request->validate([
                'reserv' => 'required|string',
            ]);
            $user_id = Auth::id();
            $shedule = Shedule::create([
                'reserv' => $request->input('reserv'),
                'event_id' => 0,
                'mono' => $request->input('mono'),
                'datapicker' => $request->input('datapicker'),
                'user_id' =>$user_id,
                'reserv_start' => $request->input('reserv_start'),
                'reserv_end' => $request->input('reserv_end'),
                'time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $start_time_mon = $request->input('time_work_start_mon');
            $end_time_mon = $request->input('time_work_end_mon');
            $time_work_start_tue = $request->input('time_work_start_tue');
            $time_work_end_tue = $request->input('time_work_end_tue');
            $time_work_start_wed = $request->input('time_work_start_wed');
            $time_work_end_wed = $request->input('time_work_end_wed');
            $time_work_start_thu = $request->input('time_work_start_thu');
            $time_work_end_thu = $request->input('time_work_end_thu');
            $time_work_start_fri = $request->input('time_work_start_fri');
            $time_work_end_fri = $request->input('time_work_end_fri');
            $time_work_start_sat = $request->input('time_work_start_sat');
            $time_work_end_sat = $request->input('time_work_end_sat');
            $time_work_start_sun = $request->input('time_work_start_sun');
            $time_work_end_sun = $request->input('time_work_end_sun');

            Timework::create([
                'shedule_id' => $shedule->id,
                'time_work_start_mon' => $start_time_mon,
                'time_work_end_mon' => $end_time_mon,
                'time_work_start_tue' => $time_work_start_tue,
                'time_work_end_tue' =>  $time_work_end_tue,
                'time_work_start_wed' =>  $time_work_start_wed,
                'time_work_end_wed' =>  $time_work_end_wed,
                'time_work_start_thu' => $time_work_start_thu,
                'time_work_end_thu' =>   $time_work_end_thu,
                'time_work_start_fri' =>  $time_work_start_fri,
                'time_work_end_fri' => $time_work_end_fri,
                'time_work_start_sat' => $time_work_start_sat ,
                'time_work_end_sat' => $time_work_end_sat,
                'time_work_start_sun' =>   $time_work_start_sun,
                'time_work_end_sun' => $time_work_end_sun,
                'time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 1,
            ]);

            return redirect()->route('admin.shedules.index')->with('success', 'Shedule created successfully');
        }
    }

    public function destroy(Shedule $shedule)
    {
        $shedule->delete();
        return redirect()->route('admin.shedule.index')->with('success', 'Shedule deleted successfully');
    }
}


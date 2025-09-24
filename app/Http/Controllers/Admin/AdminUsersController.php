<?php
namespace App\Http\Controllers\Admin;

use App\Models\Alert;
use App\Models\Event;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\UserData;

class AdminUsersController extends Controller
{
    public function index()
    {
        $admins = User::where('role_id', 1)->get();
        $users = User::all();
        $currentAdmin = auth()->user();

        return view('admin.users.index', compact('admins','currentAdmin','users'));
    }

    public function storeData(Request $request)
    {
        // Валидация данных
        $validated = $request->validate([
            'settings' => 'required|string',
        ]);

        // Сохранение данных
        $userData = new UserData();
        $userData->user_id = $request->user_id;
        $userData->email = Auth::user()->email;
        $userData->settings = $request->settings;
        $userData->save();

        return back()->with('success', 'Данные успешно сохранены.');
    }

    public function statistic()
    {
        $user = Auth::user();
        $currentAdmin = auth()->user();

        if ($user->role_id == 1) {
            $alertCount = Alert::where('user_id', $user->id)
                ->where('status', 1)
                ->orderBy('id', 'DESC')
                ->paginate(10000);
        }

        $admins = User::where('role_id', 1)->get();

        return view('admin.users.statistic', compact('admins', 'currentAdmin', 'alertCount'));
    }

    public function create()
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 0)->get();
        $users = User::all();
        $roles = Role::all(); // Получаем список ролей для формы

        return view('admin.users.create', compact('admins', 'currentAdmin', 'users', 'roles'));
    }

    public function registerNewUser(Request $request)
    {
        // Проверка, что пользователь имеет право регистрировать новых пользователей
        if (Auth::user()->role_id != 1) {
            return redirect()->route('admin.users.index')->with('error', 'У вас нет прав для создания пользователей.');
        }

        // Валидация данных
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|numeric',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        // Создание нового пользователя
        $newUser = new User();
        $newUser->name = $request->input('name');
        $newUser->email = $request->input('email');
        $newUser->phone = $request->input('phone');
        $newUser->password = bcrypt($request->input('password')); // Хешируем пароль
        $newUser->role_id = $request->input('role_id');
        $newUser->save();

        return redirect()->route('admin.users.index')->with('success', 'Новый пользователь успешно зарегистрирован.');
    }

    public function redact($id)
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();

        $userDataRecords = UserData::where('user_id', $id)->get();

        return view('admin.users.edit', compact('admins', 'currentAdmin', 'userDataRecords'));
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return abort(404);
        }
        return view('admin.users.show', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->title = $request->input('title');
        $user->location = $request->input('location');
        $user->save();

        return redirect()->route('admin.users.edit', ['user' => $user->id])->with('success', 'Событие успешно обновлено');
    }

    public function destroyUserData($userDataId)
    {
        $userData = UserData::findOrFail($userDataId);
        $userData->delete();

        return back()->with('success', 'Данные пользователя успешно удалены.');
    }
}

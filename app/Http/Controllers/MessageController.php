<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;

class MessageController extends Controller
{
    // Отобразить список всех сообщений
    public function index()
    {
        $messages = Message::all();
        return view('messages.index', compact('messages'));
    }

    // Показать форму создания нового сообщения
    public function create()
    {
        return view('messages.create');
    }

    // Сохранить новое сообщение в базу данных
    public function store(Request $request)
    {
        // Проверка и валидация входных данных
        $request->validate([
            'user_id' => 'required|integer',
            'text' => 'required|string|max:255',
            'status' => 'integer'
            // Добавьте другие правила валидации по необходимости
        ]);

        // Создание нового сообщения
        Message::create($request->all());

        return redirect()->route('messages.index')
            ->with('success', 'Message created successfully.');
    }

    // Показать форму для редактирования сообщения
    public function edit(Message $message)
    {
        return view('messages.edit', compact('message'));
    }

    // Обновить сообщение в базе данных
    public function update(Request $request, Message $message)
    {
        // Проверка и валидация входных данных
        $request->validate([
            'user_id' => 'required|integer',
            'text' => 'required|string|max:255',
            'status' => 'integer'
            // Добавьте другие правила валидации по необходимости
        ]);

        // Обновление данных сообщения
        $message->update($request->all());

        return redirect()->route('messages.index')
            ->with('success', 'Message updated successfully.');
    }

    // Удалить сообщение из базы данных
    public function destroy(Message $message)
    {
        $message->delete();

        return redirect()->route('messages.index')
            ->with('success', 'Message deleted successfully.');
    }
}

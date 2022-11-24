<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /** Регистрация пользователя.
     * Указывается имя, почта, пароль<br>
     * Можно указать любимый цвет<br>
     * Только админ может регистрировать пользователя
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function register(Request $request)
    {
        try {

            if (!auth()->user()->tokenCan('create-users')) {
                abort(403);
            }

            $fields = $request->validate([
                'name' => 'required|string',
                'email' => 'required|string|unique:user,email',
                'password' => 'required|string|confirmed',
                'favorite_colour' => 'string'
            ]);

            $user = new User();

            $user->name = $fields['name'];
            $user->email = $fields['email'];
            $user->password = Hash::make($fields['password']);
            $user->favorite_colour = $fields['favorite_colour'];

            $user->save();

            $token = $user->createToken('api-access')->plainTextToken;

            $response = [
                'token' => $token,
                'user' => $user,
            ];

            return response($response, 201);

        } catch (Exception $ex) {
            return response('Ошибка сервиса', 500);
        }
    }

    /** Авторизация, возвращает токен и базовую информацию о пользователе
     * @param Request $request
     * @return array|Application|ResponseFactory|Response
     */
    public function login(Request $request)
    {
        try {

            $fields = $request->validate([
                'email' => 'required|string',
                'password' => 'required|string'
            ]);

            $user = User::where('email', $fields['email'])->first();

            if (!$user || !Hash::check($fields['password'], $user->password)) {
                return response(
                    ['message' => 'Неправильный логин или пароль'],
                    401
                );
            }

            $user->tokens()->delete();
            if ($user->id == 1) {
                $token = $user->createToken('api-access', ['create-users'])->plainTextToken;
            } else
                $token = $user->createToken('api-access')->plainTextToken;

            $response = [
                'token' => $token,
                'user' => $user,
            ];
            return $response;

        } catch (Exception $ex) {
            return response('Ошибка сервиса', 500);
        }
    }

    /** Разлогинивание. Удаляет токен
     * @param Request $request
     * @return string[]
     */
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        $response = [
            'message' => 'Вы были успешно разлогинены',
        ];
        return $response;
    }
}

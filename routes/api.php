<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\CloudStorageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->get('/file/list', [CloudStorageController::class, 'list']);


Route::middleware('auth:sanctum')->delete('/file/delete/{id}', [CloudStorageController::class, 'deleteFile']);

Route::middleware('auth:sanctum')->get('/volume/user', [CloudStorageController::class, 'volumeUser']);

Route::middleware('auth:sanctum')->get('/file/volume/all', [CloudStorageController::class, 'volumeAll']);

Route::middleware('auth:sanctum')->get('/file/volume/folder', [CloudStorageController::class, 'volumeFolder']);

Route::middleware('auth:sanctum')->get('/file/public/{id}', [CloudStorageController::class, 'publicLink']);

Route::middleware('auth:sanctum')->post('/file/upload', [CloudStorageController::class, 'uploadFile']);

Route::middleware('auth:sanctum')->post('/file/rename/{id}', [CloudStorageController::class, 'renameFile']);

Route::middleware('auth:sanctum')->get('/file/{id}', [CloudStorageController::class, 'getFile']);

Route::middleware('auth:sanctum')->post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::post('/login', [AuthController::class, 'login']);

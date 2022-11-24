<?php

namespace App\Http\Controllers;

use App\Models\CloudStorage\CloudStorageInterface;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Throwable;

class CloudStorageController extends Controller
{
    /**
     * @var Request
     */
    private Request $request;
    private CloudStorageInterface $storage;

    public function __construct(Request $request, CloudStorageInterface $storage)
    {
        $this->request = $request;
        $this->storage = $storage;
    }

    /** Скачивание файла
     * @param $id
     * @return Application|ResponseFactory|Response|StreamedResponse
     * @throws Throwable
     */
    public function getFile($id)
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            $result = $this->storage->setUserId($userId)->download($id);
            return Storage::download($result);
        } catch (ResourceNotFoundException $ex) {
            return response(['error' => 'Файл не найден'], 404);
        } catch (Exception $ex) {
            return response(['error' => 'Ошибка сервиса'], 500);
        }
    }

    /**
     * Удаление файла по id
     * @param $id
     * @return Application|ResponseFactory|Response
     * @throws Throwable
     */
    public function deleteFile($id)
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            $this->storage->setUserId($userId)->delete($id);
            return response(['message' => 'Успешно удалено'], 200);
        } catch (ResourceNotFoundException $ex) {
            return response(['error' => 'Файл не найден'], 404);
        } catch (Exception $ex) {
            return response(['error' => 'Ошибка сервиса'], 500);
        }
    }

    public function renameFile($id)
    {
        try {
            $newName = $this->request->validate(['newName' => 'string|required'])['newName'];
            $userId = auth()->user()->getAuthIdentifier();
            $this->storage->setUserId($userId)->rename($id, $newName);
            return response(['message' => 'Успешно переименовано'], 200);
        } catch (ResourceNotFoundException $ex) {
            return response(['error' => 'Файл не найден'], 404);
        } catch (Exception $ex) {
            return response(['error' => 'Ошибка сервиса'], 500);
        }
    }

    public function uploadFile()
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            $fields = $this->request->validate([
                'file' => 'required|file',
                'folder' => 'string',
                'ttk' => 'string'
            ]);

            if (!$this->storage->setUserId($userId)->validate($fields['file'])) {
                return response([
                    'errors' => 'Максимальный размер одного файла `20мб`. Запрещено загружать `*.php` файлы'
                ], 403);
            }

            $result = $this->storage->upload($fields['file'], $fields['folder'] ?? null, $fields['ttk'] ?? null);

            return response(['id' => $result], 200);

        } catch (UploadException $ex) {
            return response(['error' => $ex->getMessage()], 500);
        }catch (Exception $ex) {
            return response(['error' => 'Ошибка сервиса'], 500);
        }
    }

    public function publicLink($id)
    {
        // todo сделац
    }

    /**
     * @return array|Application|ResponseFactory|Response
     */
    public function list()
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            $folder = $this->request->get('folder') ?? null;
            return $this->storage->setUserId($userId)->list($folder);
        } catch (Throwable $ex) {
            return response(['error' => 'Ошибка сервиса'], 500);
        }
    }

    /** Возвращает объём всех файлов в отдельной папке пользователя
     * @return Application|ResponseFactory|Response|string
     */
    public function volumeFolder()
    {
        try {
            $folder = $this->request->validate(['folder' => 'string|required'])['folder'];
            $userId = auth()->user()->getAuthIdentifier();
            return $this->storage->setUserId($userId)->volumeFolder($folder);
        } catch (Throwable $ex) {
            return response(['error' => 'Ошибка сервиса'], 500);
        }
    }

    /** Возвращает объём всех файлов пользователя
     * @return Application|ResponseFactory|Response|string
     */
    public function volumeUser()
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            return $this->storage->setUserId($userId)->volumeUser();
        } catch (Throwable $ex) {
            return response(['error' => 'Ошибка сервиса'], 500);
        }
    }

    /**
     * Возвращает объём всех файлов на диске
     * @return Application|ResponseFactory|Response|string
     */
    public function volumeAll()
    {
        try {
            if (!auth()->user()->tokenCan('access-sensitive-info')) {
                return response(['error' => 'Доступ запрещён'], 403);
            }
            $userId = auth()->user()->getAuthIdentifier();
            return $this->storage->setUserId($userId)->volumeUser();
        } catch (Throwable $ex) {
            return response(['error' => 'Ошибка сервиса'], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CloudStorage\CloudStorageInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Guard;

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

    public function getFile($id)
    {
        if ($result = $this->storage->download($id)) {
            return Storage::download($result);
        }
        $response = [];
        foreach ($this->storage->getErrors() as $exception) {
            $response['errors'] = $exception->getMessage();
        }
        return response($response, 404);
    }

    public function deleteFile($id)
    {
        if ($result = $this->storage->download($id)) {
            return Storage::download($result);
        }
        $response = [];
        foreach ($this->storage->getErrors() as $exception) {
            $response['errors'] = $exception->getMessage();
        }
        return response($response, 404);
    }

    public function uploadFile()
    {
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

        if (!$result) {
            $response = [];
            foreach ($this->storage->getErrors() as $exception) {
                $response['errors'] = $exception->getMessage();
            }
            return response($response, 403);
        }
        return response(['id' => $result], 200);
    }
}

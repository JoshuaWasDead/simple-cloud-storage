<?php

namespace App\Http\Controllers;

use App\Models\CloudStorage\CloudStorageInterface;
use App\Models\CloudStorage\StoredFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function getFile($id)
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            if ($result = $this->storage->setUserId($userId)->download($id)) {
                return Storage::download($result);
            }
        } catch (Throwable $ex) {
            return response(['error' => $ex->getMessage()], 404);
        }
    }

    public function deleteFile($id)
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            $this->storage->setUserId($userId)->delete($id);
            return response(['message' => 'Успешно удалено'], 200);
        } catch (Throwable $ex) {
            return response(['error' => $ex->getMessage()], 404);
        }
    }

    public function renameFile($id)
    {
        try {
            $newName = $this->request->validate(['newName' => 'string|required'])['newName'];
            $userId = auth()->user()->getAuthIdentifier();
            $this->storage->setUserId($userId)->rename($id, $newName);
            return response(['message' => 'Успешно переименовано'], 200);
        } catch (Throwable $ex) {
            return response(['error' => $ex->getMessage()], 404);
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
        } catch (Throwable $ex) {

            return response(['error' => $ex->getMessage()], 403);
        }
    }

    public function publicLink($id)
    {
        // todo сделац
        $loc = StoredFile::find($id)->location;
        return Storage::url($loc);
    }

    public function list()
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            $folder = $this->request->get('folder') ?? null;
            return $this->storage->setUserId($userId)->list($folder);
        } catch (Throwable $ex) {
            return response(['error' => $ex->getMessage()], 404);
        }
    }

    public function volumeFolder()
    {
        try {
            $folder = $this->request->validate(['folder' => 'string|required'])['folder'];
            $userId = auth()->user()->getAuthIdentifier();
            return $this->storage->setUserId($userId)->volumeFolder($folder);
        } catch (Throwable $ex) {
            return response(['error' => $ex->getMessage()], 503);
        }
    }

    public function volumeUser()
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            return $this->storage->setUserId($userId)->volumeUser();
        } catch (Throwable $ex) {
            return response(['error' => $ex->getMessage()], 503);
        }
    }

    public function volumeAll()
    {
        try {
            $userId = auth()->user()->getAuthIdentifier();
            return $this->storage->setUserId($userId)->volumeUser();
        } catch (Throwable $ex) {
            return response(['error' => $ex->getMessage()], 503);
        }
    }
}

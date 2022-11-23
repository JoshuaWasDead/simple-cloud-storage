<?php

namespace App\Models\CloudStorage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Throwable;

class FileSystemStorage implements CloudStorageInterface
{

    private int $userId;

    private array $errors;

    /**
     * @inheritdoc
     */
    function download(int $id): string|false
    {
        if (is_null($file = StoredFile::find($id))) {
            $this->errors[] = new ResourceNotFoundException('Файл не найден');
            return false;
        }
        return  $file->location;
    }

    /**
     * @inheritdoc
     */
    function upload(UploadedFile $file, ?string $folder = null, ?int $ttk = null): int|false
    {
        $fileName = $file->getClientOriginalName();
        $savePath = env('USER_STORAGE_FOLDER') . '/' . $this->userId;

        if (!is_null($folder)) {
            $savePath .= '/' . $folder;
        }

        if (!$this->checkIfUserHasSpace($file->getSize())) {
            $this->errors[] = new UploadException('Not enough space on disk');
            return false;
        }

        if (Storage::exists($savePath . '/' . $fileName)) {
            $this->errors[] = new UploadException('File already exists');
            return false;
        }

        if (!$path = Storage::putFileAs($savePath, $file, $fileName)) {
            $this->errors[] = new UploadException('Could not save file');
            return false;
        }

        $storedFile = new StoredFile;
        $storedFile->hash = hash_file('sha1', storage_path() . '/app/' . $path);
        $storedFile->file_size = $file->getSize();
        $storedFile->owner = $this->userId;
        $storedFile->location = $path;
        $storedFile->folder = $folder;
        if (!is_null($ttk)) {
            $storedFile->delete_when = now()->addDays($ttk);
        }
        $storedFile->save();
        $storedFile->refresh();

        return $storedFile->id;

    }

    /**
     * @inheritdoc
     */
    function delete(int $id): bool
    {
        // TODO: Implement delete() method.
    }

    /**
     * @inheritdoc
     */
    function list(string $folder = null): array
    {
        // TODO: Implement list() method.
    }

    /**
     * @inheritdoc
     */
    function rename(int $id): bool
    {
        // TODO: Implement rename() method.
    }

    /**
     * @inheritdoc
     */
    function folderAdd(string $name): bool
    {
        // TODO: Implement folderAdd() method.
    }

    /**
     * @inheritdoc
     */
    function folderRemove(string $name): bool
    {
        // TODO: Implement folderRemove() method.
    }

    /**
     * @inheritdoc
     */
    function volumeFolder(): string
    {
        // TODO: Implement volumeFolder() method.
    }

    /**
     * @inheritdoc
     */
    function volumeAll(): string|false
    {
        // TODO: Implement volumeAll() method.
    }

    /**
     * @inheritdoc
     */
    function volumeUser(): string|false
    {
        // TODO: Implement volumeUser() method.
    }

    /**
     * @inheritdoc
     */
    function validate(UploadedFile $file): bool
    {
        $size = $file->getSize();
        $extension = $file->clientExtension();
        if ($size > 20000000 || $extension === 'php') return false;
        return true;
    }

    /**
     * @param int $userId
     * @return FileSystemStorage
     */
    public function setUserId(int $userId): FileSystemStorage
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    private function checkIfUserHasSpace(int $size): bool
    {
        $otherFiles = StoredFile::where('owner', $this->userId)->sum('file_size');
        return ($size + $otherFiles) < Env::get('MAX_USER_STORAGE', 100000000);
    }
}

<?php

namespace App\Models\CloudStorage;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use const DIRECTORY_SEPARATOR;

class FileSystemStorage implements CloudStorageInterface
{

    private int|string $userId;

    /**
     * @inheritdoc
     */
    function download(int $id): string
    {
        if (is_null($file = StoredFile::where('id', '=', $id)->where('owner', '=', $this->getUserId())->first())) {
            throw new ResourceNotFoundException('Файл не найден');
        }
        return $file->location;
    }

    /**
     * @inheritdoc
     */
    function upload(UploadedFile $file, string $folder = null, int $ttk = null): int
    {
        $fileName = $file->getClientOriginalName();
        $savePath = Config::get('storage.path') . '/' . $this->userId;

        if (!is_null($folder)) {
            $savePath .= '/' . $folder;
        }

        if (!$this->checkIfUserHasSpace($file->getSize())) {
            throw new UploadException('Not enough space on disk');
        }

        if (Storage::exists($savePath . '/' . $fileName)) {
            throw new UploadException('File already exists');
        }

        if (!$path = Storage::putFileAs($savePath, $file, $fileName)) {
            throw new UploadException('Could not save file');
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
        if (!$storedFile = StoredFile::where('id', '=', $id)->where('owner', '=', $this->getUserId())->first()) {
            throw new FileNotFoundException('File with that id does not exist');
        }

        if (!Storage::delete($storedFile->location)) {
            throw new RuntimeException('Could not delete file');
        }

        $storedFile->delete();

        if (!StoredFile::where('owner', '=', $this->getUserId())->where('folder', '=', $storedFile->folder)->first()) {
            Storage::deleteDirectory(dirname($storedFile->location));
        }


        return true;
    }

    /**
     * @inheritdoc
     */
    function list(string $folder = null): array
    {
        $storedFiles = StoredFile::where('owner', '=', $this->getUserId());
        if (!is_null($folder)) $storedFiles->where('folder', '=', $folder);


        $list = [];

        foreach ($storedFiles->get() as $storedFile) {
            if ($storedFile->folder) {
                $list[$storedFile->folder][] = [
                    'id' => $storedFile->id,
                    'name' => basename($storedFile->location),
                    'hash' => $storedFile->hash,
                ];
                continue;
            }
            $list['loose'][] = [
                'id' => $storedFile->id,
                'name' => basename($storedFile->location),
                'hash' => $storedFile->hash,
            ];
        }
        return $list;

    }

    /**
     * @inheritdoc
     */
    function rename(int $id, string $newName): bool
    {
        if (!$storedFile = StoredFile::where('id', '=', $id)->where('owner', '=', $this->getUserId())->first()) {
            throw new FileNotFoundException('File with that id does not exist');
        }
        $oldPath = pathinfo($storedFile->location);
        $newPath = $oldPath['dirname'] . DIRECTORY_SEPARATOR . $newName . '.' . $oldPath['extension'];
        if (!Storage::move($storedFile->location, $newPath)) {
            throw new RuntimeException('Could not rename file');
        }
        $storedFile->location = $newPath;
        $storedFile->save();
        return true;
    }

    /**
     * @inheritdoc
     */
    function volumeFolder(string $folder): string
    {
        $volume = (int)StoredFile::where('owner', $this->getUserId())->where('folder', '=', $folder)->sum('file_size');
        return $this->formatFileSize($volume);
    }

    /**
     * @inheritdoc
     */
    function volumeAll(): string
    {
        $volume = StoredFile::sum('file_size');
        return $this->formatFileSize($volume);
    }

    /**
     * @inheritdoc
     */
    function volumeUser(): string
    {
        $volume = (int)StoredFile::where('owner', '=', $this->getUserId())->sum('file_size');
        return $this->formatFileSize($volume);
    }

    /**
     * @inheritdoc
     */
    function validate(UploadedFile $file): bool
    {
        $size = $file->getSize();
        $extension = $file->clientExtension();
        if ($size > Config::get('storage.max_size', 20000000) || $extension === 'php') return false;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function setUserId(int|string $userId): CloudStorageInterface
    {
        $this->userId = $userId;
        return $this;
    }

    private function checkIfUserHasSpace(int $size): bool
    {
        $otherFiles = StoredFile::where('owner', '=', $this->getUserId())->sum('file_size');
        return ($size + $otherFiles) < Config::get('storage . volume', 100000000);
    }

    /**
     * @inheritdoc
     */
    function getUserId(): int|string
    {
        return $this->userId;
    }


    /**
     * Возвращает отформатированную строку размера файла
     * @param int $volume
     * @return string
     */
    protected function formatFileSize(int $volume): string
    {
        if ($volume === 0) return 0;
        $base = log($volume) / log(1024);
        $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');
        return round(pow(1024, $base - floor($base)), 2) . $suffixes[floor($base)];
    }

}

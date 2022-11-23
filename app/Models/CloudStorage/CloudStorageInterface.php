<?php

namespace App\Models\CloudStorage;

use Illuminate\Http\UploadedFile;

interface CloudStorageInterface
{

    /**
     * Скачать файл по id <br>
     * Возвращает путь к файлу если файл найден , иначе false
     * @param int $id id файла
     * @return string|false
     */
    function download(int $id): string|false;

    /**
     * Загрузить файл<br>
     * Возвращает id файла при успешной загрузке файла, иначе false
     * @param UploadedFile $file Загруженный файл из запроса
     * @param string|null $folder папка, в которую положить файл
     * @param int|null $ttk Сколько хранить файл, дней
     * @return int|false
     */
    function upload(UploadedFile $file, ?string $folder = null, ?int $ttk = null): int|false;

    /**
     * Удалить файл<br>
     * Возвращает true при успешном удалении, иначе false
     * @param int $id
     * @return bool
     */
    function delete(int $id): bool;

    /**
     * Вывести список файлов<br>
     * Возвращает массив с файлами, пустой массив если файлов нет
     * @param string|null $folder
     * @return array
     */
    function list(string $folder = null): array;

    /**
     * Переименовать файл<br>
     * Возвращает true при успешном переименовании, иначе false
     * @param int $id
     * @return bool
     */
    function rename(int $id): bool;

    /**
     * Создание папки
     * Возвращает true при успешном создании, иначе false
     * @param string $name
     * @return bool
     */
    function folderAdd(string $name): bool;

    /**
     * Удаление папки
     * Возвращает true при успешном удалении, иначе false
     * @param string $name
     * @return bool
     */
    function folderRemove(string $name): bool;

    /**
     * Возвращает форматированную строку с объёмом файлов в папке, false если файлов нет
     * @return string
     */
    function volumeFolder(): string;

    /**
     * Возвращает форматированную строку с объёмом файлов на диске, false если файлов нет
     * @return string|false
     */
    function volumeAll(): string|false;

    /**
     * Возвращает форматированную строку с объёмом файлов на диске пользователя, false если файлов нет
     * @return string|false
     */
    function volumeUser(): string|false;

    /**
     * Валидация при загрузке файлов
     * @param UploadedFile $file
     * @return bool
     */
    function validate(UploadedFile $file): bool;


}

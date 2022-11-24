<?php

namespace App\Models\CloudStorage;

use Illuminate\Http\UploadedFile;
use Throwable;

interface CloudStorageInterface
{
    /**
     * Возвращает уникальный идентификатор пользователя
     * @return int|string
     * @throws Throwable
     */
    function getUserId(): int|string;

    /**
     * Устанавливает уникальный идентификатор пользователя, возвращает текущий объект
     * @param int|string $userId
     * @return CloudStorageInterface
     */
    function setUserId(int|string $userId): CloudStorageInterface;

    /**
     * Скачать файл по id <br>
     * Возвращает путь к файлу если файл найден, иначе выбрасывает исключение
     * @param int $id id файла
     * @return string
     * @throws Throwable
     */
    function download(int $id): string;

    /**
     * Загрузить файл<br>
     * Возвращает id файла при успешной загрузке файла, иначе выбрасывает исключение
     * @param UploadedFile $file Загруженный файл из запроса
     * @param string|null $folder папка, в которую положить файл
     * @param int|null $ttk Сколько хранить файл, дней
     * @return int
     * @throws Throwable
     */
    function upload(UploadedFile $file, string $folder = null, int $ttk = null): int;

    /**
     * Удалить файл<br>
     * Возвращает true при успешном удалении, иначе выбрасывает исключение
     * @param int $id
     * @return bool
     * @throws Throwable
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
     * Возвращает true при успешном переименовании, иначе выбрасывает исключение
     * @param int $id
     * @param string $newName
     * @return bool
     * @throws Throwable
     */
    function rename(int $id, string $newName): bool;

    /**
     * Возвращает форматированную строку с объёмом файлов в папке
     * @return string
     * @throws Throwable
     */
    function volumeFolder(string $folder): string;

    /**
     * Возвращает форматированную строку с объёмом файлов на диске
     * @return string
     */
    function volumeAll(): string;

    /**
     * Возвращает форматированную строку с объёмом файлов на диске пользователя
     * @return string
     */
    function volumeUser(): string;

    /**
     * Валидация при загрузке файлов
     * @param UploadedFile $file
     * @return bool
     */
    function validate(UploadedFile $file): bool;


}

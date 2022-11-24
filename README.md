# Simple Cloud Storage

## Прототип сервиса облачного хранилища для файлов.

- регистрация и авторизация (каждый пользователь видит свои файлы);
- базовая информация о пользователе
- создание папок (один уровень вложенности, без подпапок);
- список файлов;
- загрузка файлов;
- переименование файлов;
- удаление файлов,
- скачивание файлов;
- объем всех файлов на диске одного пользователя - `100 мб`;
- валидация при загрузке файлов:
    1. максимальный размер одного файла `20мб`
    2. запрещено загружать `*.php` файлы
- получение размера всех файлов внутри директории
- получение размера всех файлов пользователя
- получение размера всех файлов на сервере (доступно только администратору)
- возможность, при загрузке, указывать срок хранения файла, после которого он сам удаляется

## Развертывание проекта

### Требования

```
docker
php8.1
composer
```

1. Клонировать проект
2. В корне проекта ```composer install```
3. Создать .env файл, заполнить согласно .env.example, специфичные для проекта:
    - ADMIN_DEFAULT_EMAIL - почта админа по умолчанию
    - ADMIN_DEFAULT_PASSWORD - пароль админа по умолчанию
    - USER_STORAGE_FOLDER - папка, в которой хранятся пользовательские файлы
    - MAX_USER_STORAGE=100000000 - максимальный объём файлов пользователя
    - MAX_FILE_SIZE=20000000 - максимальный размер файла
4. Выполнить ```php artisan key:generate```
5. Выполнить ```crontab -e```, туда
   добавить ```* * * * * cd /путь/к/проекту/ && ./vendor/bin/sail artisan schedule:run >> /dev/null 2>&1```
6. Выполнить ```sudo service cron restart```
7. В корне проекта ```./vendor/bin/sail up```
8. ./vendor/bin/sail artisan migrate:fresh --seed

## Особенности реализации

### Регистрация и авторизация (каждый пользователь видит свои файлы):

    - При сиде базы (artisan migrate:fresh --seed) на основе почты и пароля из .env создаётся администратор с правом '
      create-users', который может через 'api/register' создавать новых пользователей и просматривать общий объём файлов
      на диске через 'api/file/volume/all'
    - Все пользователи авторизуются через '/api/login', указывая почту и пароль, в ответ получают токен и базовую
      информацию о пользователе
    - Разлогинивание через '/api/logout', текущий токен при этом удаляется
    - Все операции с файлами доступны только авторизованным пользователям

### Базовая информация о пользователе

    - Имя, почта, любимый цвет, указанные при регистрации
    - Выдаётся пользователю при авторизации

### Создание папок (один уровень вложенности, без подпапок):

    - Папки создаются при загрузке файлов, удаляются когда в них не остаётся файлов

### Работа с файлами:

    - Все файлы при загрузке и изменениях регистрируются в таблице, из которой уже в дальнейшем берутся данные о папке, где они находятся, их размере, местоположении и дате, когда их нужно удалить
    - Данные о максимальных размерах файлов, папок пользователя и т.п. хранятся в .env, доступны через конфиг storage
    - Возможность, при загрузке, указывать срок хранения файла, после которого он сам удаляется реализована через метод pruning в модели сохраненного файла StoredFile

### API

К всем методам, кроме авторизации, нужно передавать Authorization: Bearer токен, получаемый при авторизации

#### /api/file/upload

* `POST` : Загрузить файл в хранилище
* `file` - файл, который надо загрузить
* `ttk` - Сколько дней хранить файл
* `folder` - Папка, в которую сохранить файл
  Запрос:

```
curl --location --request POST 'http://localhost/api/file/upload' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 2|17ebnN9USvYaEs1c7IMd2RXqUD2NTW6dPaivkdXN' \
--form 'file=@"/D:/Downloads/файл.jpg"' \
--form 'ttk="10"' \
--form 'folder="folder1"'
```

Ответ: `id` - id загруженного файла

```
{
    "id": 2
}
```

#### /api/file/{id}

* `GET` : Скачать файл по id
* `id` - id файла

Запрос:

```
curl --location --request GET 'http://localhost/api/file/2' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 2|17ebnN9USvYaEs1c7IMd2RXqUD2NTW6dPaivkdXN'
```

Ответ: файл

#### /api/file/delete/{id}

* `DELETE` : Удалить файл из хранилища
* `id` - id файла  
  Запрос:

```
curl --location --request DELETE 'http://localhost/api/file/delete/4' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 2|17ebnN9USvYaEs1c7IMd2RXqUD2NTW6dPaivkdXN'
```

Ответ:

```
{
    "message": "Успешно удалено"
}
```

#### /api/file/rename/{id}

* `POST` : Переименовать файл по id
* `id` - id файла

Запрос:

```
curl --location --request POST 'http://localhost/api/file/rename/5' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 6|GlF05xdQ67M9coVE10z0R87zdiol9t48k4ade7PI' \
--header 'Content-Type: application/json' \
--data-raw '{
    "newName": "test"
}'
```

Ответ: `title` - название объявления, `price` - цена, `photos` - массив с ссылкой на главное фото

```
{
    "message": "Успешно переименовано"
}
```

#### /api/file/list

* `GET` : Получить все файлы пользователя

Запрос:

```
curl --location --request GET 'http://localhost/api/file/list' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 2|17ebnN9USvYaEs1c7IMd2RXqUD2NTW6dPaivkdXN' \
--data-raw ''
```

Ответ: `id` - id файлв, `name` - название файлв, `hash` - хэш сумма файла, `size` - размер файла

Файлы без папок лежат в массиве loose

```
{
    "folder1": [
        {
            "id": 1,
            "name": "de.jpg",
            "hash": "4f40c879c935ec640cf91112d4bccf1d99e29144",
            "size": "597.12 KB"
        },
        {
            "id": 2,
            "name": "обложкавидео.jpg",
            "hash": "f2ac75921d126c931a6abe3d56a01085ff90434a",
            "size": "196.03 KB"
        },
        {
            "id": 3,
            "name": "test.jpg",
            "hash": "4ec742c574447642c900ce3e652ba6decafae817",
            "size": "154.17 KB"
        }
    ],
    "folder5": [
        {
            "id": 5,
            "name": "de.jpg",
            "hash": "4f40c879c935ec640cf91112d4bccf1d99e29144",
            "size": "597.12 KB"
        }
    ],
    "loose": [
        {
            "id": 6,
            "name": "de.jpg",
            "hash": "4f40c879c935ec640cf91112d4bccf1d99e29144",
            "size": "597.12 KB"
        }
    ]
}
```

#### /api/file/volume/folder?folder=folder1

* `GET` : Получить общий объём файлов в папке пользователя
* `folder` - название папки

Запрос:

```
curl --location --request GET 'http://localhost/api/file/volume/folder?folder=folder1' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 2|17ebnN9USvYaEs1c7IMd2RXqUD2NTW6dPaivkdXN'
```

Ответ:

```
947.31 KB
```

#### /api/volume/user

* `GET` : Получить общий объём файлов во всех папках пользователя

Запрос:

```
curl --location --request GET 'http://localhost/api/volume/user' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 2|17ebnN9USvYaEs1c7IMd2RXqUD2NTW6dPaivkdXN'
```

Ответ:

```
2.09 MB
```

#### /api/volume/all

* `GET` : Получить общий объём файлов. Доступно только администратору.

Запрос:

```
curl --location --request GET 'http://localhost/api/volume/all' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 2|17ebnN9USvYaEs1c7IMd2RXqUD2NTW6dPaivkdXN'
```

Ответ:

```
2.09 MB
```

#### /api/register

* `GET` : Регистрация нового пользователя. Доступно только администратору.
* `name` - Имя пользователя. Обязательное поле.
* `email` - Почта пользователя. Обязательное поле.
* `password` - Пароль пользователя. Обязательное поле.
* `password_confitmation` - Подтверждение пароля пользователя. Обязательное поле.
* `favorite_colour` - Любимый цвет пользователя. Небязательное поле.

Запрос:

```
curl --location --request POST 'http://localhost/api/register' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 2|17ebnN9USvYaEs1c7IMd2RXqUD2NTW6dPaivkdXN' \
--header 'Content-Type: application/json' \
--data-raw '{
    "name": "test",
    "email": "test@test.test",
    "password": "test",
    "password_confirmation": "test",
    "favorite_colour": "purple"
}'
```

Ответ:

```
{
    "token": "4|HqeBDohZbc50iCNLxgYh6SzRUcyjBxNt6k4A0toy",
    "user": {
        "name": "test",
        "email": "test@test.test",
        "favorite_colour": "purple",
        "updated_at": "2022-11-24T16:36:40.000000Z",
        "created_at": "2022-11-24T16:36:40.000000Z",
        "id": 3
    }
}
```

#### /api/login

* `GET` : Разлогинивание. Предыдущий токен удаляется.

Запрос:

```
curl --location --request POST 'http://localhost/api/logout' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer 5|cyO3bbaIde9KpXskluS89Ve0TuabqjXhsD0V65o9' \
--data-raw ''
```

Ответ:

```
{
    "message": "Вы были успешно разлогинены"
}
```

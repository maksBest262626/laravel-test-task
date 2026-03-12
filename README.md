# Laravel Test Task

## Установка

```bash
make install
```

Приложение будет доступно на `http://localhost:8000` (не будет, так как это чисто апи)

### Доступные команды

| Команда | Описание |
|---|---|
| `make install` | Полная установка проекта |
| `make up` / `make down` | Запуск / остановка контейнеров |
| `make bash` | Войти в PHP контейнер |
| `make migrate` | Запустить миграции |
| `make fresh` | Пересоздать БД с сидерами |
| `make test` | Запустить тесты |
| `make artisan c='...'` | Выполнить artisan команду |
| `make composer c='...'` | Выполнить composer команду |

---

## 1. Основы Laravel

### Разница между `@extends` и `@include` в Blade

`@extends` задаёт наследование layout-шаблона. Дочерний шаблон переопределяет секции (`@section`) родительского. Может быть только один `@extends` на шаблон, и он должен стоять первой строкой.

`@include` — это вставка (подключение) другого шаблона в текущий. Можно вызывать сколько угодно раз и передавать данные вторым аргументом

```blade
{{-- Наследование: страница расширяет layout --}}
@extends('layouts.app')

@section('content')
    {{-- Вставка переиспользуемого компонента --}}
    @include('partials.header', ['title' => 'Главная'])
@endsection
```

### Сервис-провайдеры

Сервис-провайдеры — центральное место для конфигурации и начальной загрузки приложения. Каждый провайдер имеет два метода:

- `register()` — регистрация привязок в сервис-контейнере (биндинг интерфейсов к реализациям, синглтоны)
- `boot()` — вызывается после регистрации всех провайдеров. Здесь можно использовать любые зависимости: регистрировать маршруты, слушатели событий, middleware, валидаторы и т.д.

```php
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Привязка интерфейса к реализации
        $this->app->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);
    }

    public function boot(): void
    {
        // Публикация конфигов, миграций, запуск наблюдателей и т.д.
    }
}
```

Все провайдеры перечислены в `bootstrap/providers.php`. Laravel инстанцирует каждый, вызывает `register()` у всех, затем `boot()` у всех.

### Система маршрутизации

Маршруты определяются в файлах `routes/web.php` и `routes/api.php`. Laravel сопоставляет входящий HTTP-запрос (метод + URI) с зарегистрированными маршрутами по порядку их объявления.

Основные возможности:

```php
// Базовый маршрут
Route::get('/users', [UserController::class, 'index']);

// Параметры маршрута
Route::get('/users/{id}', [UserController::class, 'show']);

// Группировка с middleware и префиксом
Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::apiResource('posts', PostController::class);
});

// Именованные маршруты
Route::get('/dashboard', DashboardController::class)->name('dashboard');
```

Под капотом: при загрузке приложения `RouteServiceProvider` регистрирует все маршруты. При входящем запросе `Router` перебирает коллекцию маршрутов, находит совпадение по URI и HTTP-методу, применяет middleware-цепочку и вызывает контроллер/замыкание.

### Разница между `get()` и `first()`

- `get()` возвращает коллекцию (`Collection`) всех найденных записей. Если ничего не найдено — пустая коллекция.
- `first()` возвращает одну модель (первую найденную запись) или `null`.

```php
// Коллекция всех активных пользователей
$users = User::where('status', 'active')->get(); // Collection

// Один конкретный пользователь
$user = User::where('email', 'admin@example.com')->first(); // User|null
```

На уровне SQL `first()` добавляет `LIMIT 1` к запросу.

---

## 2. База данных и Eloquent ORM

### Миграция для таблицы users

```bash
make artisan c="make:migration create_users_table"
```

```php
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('status')->default('active');
        $table->timestamps();
    });
}
```

### Запрос активных пользователей с сортировкой по дате регистрации

```php
$users = User::where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->get();
```

### Отношение "один ко многим" между пользователями и постами

Миграция для таблицы `posts`:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('body');
    $table->timestamps();
});
```

Модели:

```php
// app/Models/User.php
class User extends Authenticatable
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

// app/Models/Post.php
class Post extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Использование:

```php
// Все посты пользователя
$posts = $user->posts;

// Автор поста
$author = $post->user;

// Eager loading (решение проблемы N+1)
$users = User::with('posts')->get();
```

---

## 3. REST API для личного кабинета

**Задание:** REST API для личного кабинета мобильного приложения.

Реализовано:
- Аутентификация через Sanctum (register, login, logout)
- CRUD операции для профиля (show, update, delete)
- История действий пользователя (activity log)
- Загрузка и удаление файлов через API
- Rate limiting (10 req/min для гостей, 60 req/min для авторизованных)

### Структура проекта

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php        # Регистрация, логин, логаут
│   │   ├── ProfileController.php     # CRUD профиля
│   │   ├── ActivityLogController.php  # История действий
│   │   └── FileController.php        # Загрузка файлов
│   ├── Requests/
│   │   ├── RegisterRequest.php
│   │   ├── LoginRequest.php
│   │   ├── UpdateProfileRequest.php
│   │   └── UploadFileRequest.php
│   └── Resources/
│       ├── UserResource.php
│       ├── ActivityLogResource.php
│       └── FileResource.php
└── Models/
    ├── User.php
    ├── ActivityLog.php
    └── File.php
```

### API Endpoints

#### Аутентификация (без токена, rate limit: 10/мин)

| Метод | URL | Описание |
|---|---|---|
| POST | `/api/register` | Регистрация |
| POST | `/api/login` | Логин |

#### Защищённые маршруты (Bearer token, rate limit: 60/мин)

| Метод | URL | Описание |
|---|---|---|
| POST | `/api/logout` | Логаут |
| GET | `/api/profile` | Получить профиль |
| PUT | `/api/profile` | Обновить профиль |
| DELETE | `/api/profile` | Удалить аккаунт |
| GET | `/api/activity` | История действий (пагинация) |
| GET | `/api/files` | Список файлов (пагинация) |
| POST | `/api/files` | Загрузить файл (max 10MB) |
| DELETE | `/api/files/{id}` | Удалить файл |

### Примеры запросов

**Регистрация:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"password123","password_confirmation":"password123"}'
```

**Логин:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```

**Профиль (с токеном):**
```bash
curl http://localhost:8000/api/profile \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Загрузка файла** (тестовый PDF лежит в корне проекта):
```bash
curl -X POST http://localhost:8000/api/files \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@test-upload.pdf"
```

### Загрузка файлов

#### Как это работает

1. Файлы загружаются через `POST /api/files` с `multipart/form-data`
2. Сохраняются на диск `public` в директорию `storage/app/public/uploads/{user_id}/`
3. Symlink `public/storage` -> `storage/app/public` создаётся при `make install` (`php artisan storage:link`)
4. В БД (таблица `files`) сохраняются метаданные: оригинальное имя, путь, mime-тип, размер
5. В ответе API возвращается прямая ссылка на файл

#### Ограничения

- Максимальный размер: **10 MB**
- Допустимые форматы: `jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx, txt, csv, zip`
- Удалить файл может только его владелец (Policy)

#### Загрузка файла

В корне проекта лежит тестовый файл `test-upload.pdf` для проверки:

```bash
curl -X POST http://localhost:8000/api/files \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@test-upload.pdf"
```

Ответ:
```json
{
    "data": {
        "id": 1,
        "original_name": "test-upload.pdf",
        "url": "http://localhost:8000/storage/uploads/1/hashed_name.pdf",
        "mime_type": "application/pdf",
        "size": 583,
        "created_at": "2026-03-12T15:00:00.000000Z"
    }
}
```

#### Получение списка файлов

```bash
curl http://localhost:8000/api/files \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Возвращает пагинированный список файлов текущего пользователя (20 на страницу).

#### Скачивание файла

Файл доступен напрямую по URL из поля `url` в ответе:

```bash
curl -O http://localhost:8000/storage/uploads/1/hashed_name.pdf
```

#### Удаление файла

```bash
curl -X DELETE http://localhost:8000/api/files/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Файл удаляется с диска и из БД. Вернёт `204 No Content`.

#### Где хранятся файлы

```
storage/app/public/uploads/
└── {user_id}/
    ├── hashed_name1.pdf
    └── hashed_name2.jpg

public/storage -> ../storage/app/public  (symlink)
```

Nginx отдаёт файлы статически через `try_files $uri`, минуя PHP — это быстро.

### Rate Limiting

Настроен в `bootstrap/app.php`:

- **Гостевые маршруты** (`guest`): 10 запросов в минуту по IP
- **Авторизованные маршруты** (`authenticated`): 60 запросов в минуту по user ID

Заголовки ответа содержат информацию о лимитах:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

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

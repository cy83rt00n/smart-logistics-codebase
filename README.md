# Notification Service API

Микросервис уведомлений для Smart Logistics. Поддерживает отправку через **SMS** и **Email** с использованием мок-провайдеров.

## Содержание

- [Архитектура](#архитектура)
- [Эндпоинты](#эндпоинты)
  - [POST /api/notifications/send](#post-apinotificationssend)
  - [GET /api/notifications/{uuid}/status](#get-apinotificationsuuidstatus)
- [Категории уведомлений](#категории-уведомлений)
- [Каналы доставки](#каналы-доставки)
- [Структура проекта](#структура-проекта)
- [Установка и запуск](#установка-и-запуск)
- [Тестирование](#тестирование)
- [Swagger документация](#swagger-документация)

## Архитектура

![Notification Service Architecture](../docs/architecture.svg)

```
Client ──POST──► /api/notifications/send
                    │
          ┌─────────┴──────────┐
          ▼ transaction         ▼ marketing
     NotificationService    SendNotificationJob
          │                      │
     ┌────┴────┐           ┌────┴────┐
     ▼         ▼           ▼         ▼
  SmsChannel  Email      SmsChannel  Email
  Channel                 Channel
     │         │           │         │
     ▼         ▼           ▼         ▼
  Mock SMS  Mock Email  Mock SMS  Mock Email
  Provider  Provider    Provider  Provider
     │         │           │         │
     └────┬────┘           └────┬────┘
          ▼                      ▼
   NotificationDelivery    NotificationDelivery
   (status: sent)          (status: queued → processing → sent/failed)
```

## Эндпоинты

### POST /api/notifications/send

Отправка уведомления. Тип обработки зависит от категории:

- **transaction** — синхронная отправка (высокий приоритет)
- **marketing** — постановка в очередь RabbitMQ, асинхронная обработка

#### Request Body

```json
{
    "category": "transaction",
    "type": "sms",
    "recipients": ["+79001234567"],
    "subject": "Order confirmation",
    "body": "Your order #12345 has been confirmed.",
    "data": {}
}
```

| Поле        | Тип                    | Обязательное | Описание                         |
|-------------|------------------------|--------------|----------------------------------|
| `category`  | `string`               | ✅           | `transaction` или `marketing`    |
| `type`      | `string`               | ✅           | `sms` или `email`                |
| `recipients`| `array<string>`        | ✅           | Список получателей (минимум 1)   |
| `subject`   | `string` (max 255)     | ✅           | Тема сообщения                   |
| `body`      | `string`               | ✅           | Текст сообщения                  |
| `data`      | `object`               | ❌           | Дополнительные данные            |

#### Response (200 — успех)

**Transactional — синхронно:**
```json
{
    "success": true,
    "message": "Notification sent"
}
```

**Marketing — поставлено в очередь:**
```json
{
    "success": true,
    "message": "Notification queued for sending"
}
```

#### Response (422 — ошибка валидации)

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "category": ["The selected category is invalid."],
        "type": ["The selected type is invalid."],
        "recipients": ["The recipients field must have at least 1 items."]
    }
}
```

#### Response (500 — частичная ошибка отправки)

```json
{
    "success": false,
    "message": "Some notifications failed",
    "errors": {
        "+79001234567": ["Provider temporarily unavailable"],
        "+79007654321": ["Invalid phone number format"]
    }
}
```

---

### GET /api/notifications/{uuid}/status

Получение статуса доставки по UUID.

#### Path Parameters

| Параметр | Тип     | Обязательный | Описание                    |
|----------|---------|--------------|-----------------------------|
| `uuid`   | `string`| ✅           | UUID записи доставки        |

#### Response (200)

```json
{
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "sent",
    "recipient": "+79001234567",
    "type": "sms",
    "category": "transaction",
    "error_message": null,
    "created_at": "2026-06-13T06:00:00.000000Z",
    "updated_at": "2026-06-13T06:00:01.000000Z"
}
```

Возможные статусы: `pending`, `queued`, `sent`, `failed`, `rejected`.

#### Response (404)

```json
{
    "success": false,
    "message": "Notification delivery not found"
}
```

## Категории уведомлений

| Категория      | Приоритет | Обработка               | Очередь      |
|----------------|-----------|------------------------|--------------|
| `transaction`  | Высокий   | Синхронная (без задержки) | —            |
| `marketing`    | Низкий    | Асинхронная (через RabbitMQ) | `notifications` |

## Каналы доставки

### SMS (Mock)

- **Мок-провайдер:** `App\Services\Channels\SmsChannel`
- Симулирует задержку от 50 до 200 мс
- Валидирует формат номера (+7XXXXXXXXXX)
- Возвращает случайные ошибки с вероятностью 10%
- Конфигурация: `config/notifications.php` → `sms`

### Email (Mock)

- **Мок-провайдер:** `App\Services\Channels\EmailChannel`
- Симулирует задержку от 100 до 500 мс
- Валидирует формат email
- Возвращает случайные ошибки с вероятностью 10%
- Конфигурация: `config/notifications.php` → `email`

## Структура проекта

```
codebase/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── SendQueuedNotifications.php     # Команда artisan для обработки очереди
│   ├── DTO/
│   │   └── Notification.php                    # Data Transfer Object для уведомлений
│   ├── Exceptions/
│   │   └── NotificationException.php           # Кастомное исключение
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── NotificationController.php  # REST контроллер с Swagger аннотациями
│   ├── Jobs/
│   │   └── SendNotificationJob.php             # Job для асинхронной отправки
│   ├── Models/
│   │   └── NotificationDelivery.php            # Eloquent модель
│   ├── Providers/
│   │   └── AppServiceProvider.php              # Регистрация сервисов
│   └── Services/
│       ├── Channels/
│       │   ├── NotificationChannelInterface.php  # Интерфейс канала
│       │   ├── SmsChannel.php                    # SMS канал (mock)
│       │   └── EmailChannel.php                  # Email канал (mock)
│       └── NotificationService.php               # Сервис отправки
├── config/
│   ├── notifications.php                         # Конфигурация уведомлений
│   └── l5-swagger.php                            # Конфигурация Swagger
├── database/
│   ├── factories/
│   │   └── NotificationDeliveryFactory.php       # Factory для тестов
│   └── migrations/
│       └── 2026_06_12_224410_create_notification_deliveries_table.php
├── routes/
│   └── api.php                                   # Маршруты API
├── tests/
│   ├── Feature/
│   │   └── SendNotificationTest.php              # Feature-тесты API
│   └── Unit/
│       ├── NotificationServiceTest.php           # Unit-тесты сервиса
│       ├── SmsChannelTest.php                    # Unit-тесты SMS канала
│       └── EmailChannelTest.php                  # Unit-тесты Email канала
└── README.md                                     # Этот файл
```

## Установка и запуск

```bash
# 1. Войти в контейнер PHP
docker compose exec fpm bash

# 2. Установить зависимости (если ещё не сделано)
composer install

# 3. Выполнить миграции
php artisan migrate --force

# 4. Сгенерировать Swagger документацию
php artisan l5-swagger:generate

# 5. Запустить queue worker для маркетинговых уведомлений
php artisan queue:work --queue=notifications --tries=3 --delay=5
```

## Тестирование

### Настройка тестовой БД

```bash
docker compose exec db psql -U smartlogistics -c "CREATE DATABASE test_smartlogistics;"
```

### Запуск тестов

```bash
# Все тесты
docker compose exec fpm php artisan test

# Только Notification Service
docker compose exec fpm php artisan test --filter=NotificationServiceTest

# Только API
docker compose exec fpm php artisan test --filter=SendNotificationTest

# Только каналы
docker compose exec fpm php artisan test --filter=SmsChannelTest
docker compose exec fpm php artisan test --filter=EmailChannelTest
```

### Покрытие тестов

| Тест                   | Статус | Описание                              |
|------------------------|--------|---------------------------------------|
| NotificationServiceTest | ✅ 5/5 | send, createQueuedDelivery, валидация |
| SmsChannelTest          | ✅ 7/7 | send, валидация номера, ошибки        |
| EmailChannelTest        | ✅ 8/7 | send, валидация email, ошибки         |
| SendNotificationTest    | ✅ 7/7 | POST send, GET status, validation     |

## Swagger документация

Swagger UI доступен после генерации:

| Ресурс           | URL                                      |
|------------------|------------------------------------------|
| Swagger UI       | `http://localhost/api/documentation`     |
| OpenAPI JSON     | `http://localhost/docs/api-docs.json`    |
| OpenAPI YAML     | `http://localhost/docs/api-docs.yaml`    |

### Генерация

```bash
docker compose exec fpm php artisan l5-swagger:generate
```

После генерации документация будет доступна по указанным URL.
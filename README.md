# Notification Service

Микросервис для массовой рассылки SMS/Email уведомлений с поддержкой приоритетов и дедубликации.

## Технологии

- PHP 8.2 / Laravel 12
- PostgreSQL 15
- RabbitMQ 3.12 (брокер сообщений)
- Redis 7 (кэш и дедубликация)
- Docker / Docker Compose

## Быстрый старт

```bash
# Клонирование репозитория
git clone <your-repo-url>
cd notification-service-2

# Запуск всех сервисов
docker-compose up -d --build

# Выполнение миграций
docker exec notification-app php artisan migrate --force

# Генерация ключа приложения
docker exec notification-app php artisan key:generate --force
```

## API Документация

Swagger: http://localhost:8000/api/documentation

## API Endpoints
```bash
POST	/api/v1/notifications/send	Массовая рассылка уведомлений
GET	/api/v1/subscribers/{id}/history	История уведомлений подписчика
```
Пример запроса

```bash
curl -X POST http://localhost:8000/api/v1/notifications/send \
  -H "Content-Type: application/json" \
  -d '{
    "channel": "sms",
    "message": "Your code: 123456",
    "recipients": ["+79001234567", "+79007654321"],
    "priority": 10,
    "idempotency_key": "unique_request_id"
  }'
```

# Запуск тестов

## Все тесты
```bash
docker exec notification-app php artisan test
```

## Только интеграционные
```bash
docker exec notification-app php artisan test --testsuite=Integration
```

# Статусы доставки
queued - в очереди

sent - отправлено провайдеру

delivered - доставлено

dropped - ошибка доставки

# Мониторинг

## Логи worker
```bash
docker logs notification-worker -f
```

## Статус очереди RabbitMQ
```bash
docker exec notification-rabbitmq rabbitmqctl list_queues
```

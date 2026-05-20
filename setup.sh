#!/bin/bash

echo "🧹 Остановка и удаление старых контейнеров..."
docker-compose down -v

echo "🗑️ Удаление старых образов..."
docker rmi notification-service-2-app notification-service-2-worker 2>/dev/null || true

echo "📦 Очистка кеша Docker..."
docker builder prune -f

echo "🧹 Очистка старых контейнеров..."
docker compose down -v

echo "🗑️ Удаление старых образов..."
docker rmi notification-service-2-app notification-service-2-worker 2>/dev/null || true

echo "📦 Сборка образов..."
docker compose build --no-cache

echo "🚀 Запуск сервисов..."
docker compose up -d

echo "⏳ Ожидание готовности сервисов..."
sleep 15

echo "🗄️ Выполнение миграций..."
docker exec notification-app php artisan migrate --force

echo "🔑 Генерация ключа..."
docker exec notification-app php artisan key:generate

echo "🧹 Очистка кеша..."
docker exec notification-app php artisan config:clear
docker exec notification-app php artisan cache:clear
docker exec notification-app php artisan queue:restart

echo "✅ Статус контейнеров:"
docker ps

echo "📋 Логи worker:"
docker logs notification-worker --tail=20

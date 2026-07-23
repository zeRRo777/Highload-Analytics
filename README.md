# Highload Analytics API

Сервис асинхронного импорта и аналитики транзакций, спроектированный для работы с большими объемами данных (Highload) без утечек оперативной памяти.

## 🎯 Архитектурные решения и Оптимизация

Этот проект демонстрирует продвинутые навыки работы с памятью, базами данных и фоновыми процессами:

1. **Zero-Memory File Upload:** Файлы импорта (до 100+ МБ) не загружаются в оперативную память сервера. Они напрямую стримятся в S3-совместимое хранилище (Minio) с использованием встроенных механизмов Laravel Storage.
2. **Memory-Safe Processing (`LazyCollection`):** Фоновый воркер (Redis Queue) читает CSV-файлы из S3 построчно с помощью PHP Generators (`yield`) и `LazyCollection`. Потребление RAM остается на уровне 2-5 МБ независимо от размера файла.
3. **Bulk Inserts:** Данные накапливаются в чанки (по 1000 строк) и вставляются в PostgreSQL одним запросом, минимизируя нагрузку на сеть и базу данных.
4. **Advanced SQL (CTE & Window Functions):** Для аналитики используется сложный запрос с Common Table Expressions (`withExpression`) и оконными функциями (`RANK() OVER`), что позволяет получать аналитику одним запросом к БД.
5. **PostgreSQL Functional Indexes:** Добавлен функциональный B-Tree индекс `CREATE INDEX idx_transactions_month ON transactions (TO_CHAR(transaction_date, 'YYYY-MM'))`, который предотвращает Full Table Scan при агрегации по месяцам.
6. **Redis Caching:** Результаты тяжелых аналитических запросов кэшируются в Redis.

## 🛠 Технологический стек

- **PHP 8.4 / Laravel 13**
- **PostgreSQL 18**
- **Redis** (Queues & Cache)
- **Minio (S3)** (File Storage)
- **Docker & Docker Compose**
- **Pest PHP** (Testing)
- **Swagger / OpenAPI**

---

## Инструкция по запуску (Docker)

Проект полностью контейнеризирован. Инфраструктура включает авто-настройку Minio (создание бакетов и политик через контейнер `minio_setup`).

### 1. Подготовка

```bash
git clone https://github.com/zeRRo777/Highload-Analytics.git
cd Highload-Analytics
cp .env.example .env
```

### 2. Запуск

```bash
docker compose up -d
```

### 3. Установка зависимостей и БД

```bash
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
```

### 4. Документация API (Swagger)

```bash
docker compose exec php php artisan l5-swagger:generate v1
```

Документация доступна по адресу: **http://localhost:8080/api/v1/documentation**

---

## 🧪 Тестирование приложения

### 1. Запуск Unit & Feature тестов (Pest)

```bash
docker compose exec php php artisan test
```

### 2. Нагрузочное (Highload) тестирование вручную

В проекте предусмотрена команда для генерации больших CSV-файлов.

1. **Сгенерируйте файл на 100 000 строк:**

```bash
docker compose exec php php artisan app:generate-csv --count=100000
```

Файл появится в `storage/app/test_transactions.csv`.

2. **Загрузите файл через API (через Swagger или Postman):**
   Отправьте `POST /api/v1/imports` прикрепив сгенерированный файл. Вы получите ID импорта.
   Воркер начнет обработку чанками по 1000 строк. Потребление памяти воркером не превысит нескольких мегабайт.

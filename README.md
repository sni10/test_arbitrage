[![CI](https://img.shields.io/github/actions/workflow/status/sni10/test_arbitrage/tests.yml?style=for-the-badge&logo=github&label=CI)](https://github.com/sni10/test_arbitrage/actions/workflows/tests.yml)
[![Release](https://img.shields.io/github/actions/workflow/status/sni10/test_arbitrage/release.yml?style=for-the-badge&logo=github&label=Release)](https://github.com/sni10/test_arbitrage/actions/workflows/release.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge&logo=opensourceinitiative&logoColor=white)](https://github.com/sni10/test_arbitrage/blob/main/LICENSE)
[![Latest Release](https://img.shields.io/github/v/release/sni10/test_arbitrage?style=for-the-badge&logo=github)](https://github.com/sni10/test_arbitrage/releases/latest)
[![Tests](https://img.shields.io/github/actions/workflow/status/sni10/test_arbitrage/tests.yml?style=for-the-badge&logo=github&label=Tests)](https://github.com/sni10/test_arbitrage/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/badge/coverage-85.4%25-brightgreen?style=for-the-badge&logo=codecov&logoColor=white)](https://github.com/sni10/test_arbitrage/actions/workflows/tests.yml)

# Arbitrage Analyzer

Консольное приложение для анализа арбитражных возможностей на криптовалютных биржах.

## Описание

Система анализирует цены торговых пар на 5 биржах (Binance, Poloniex, Bybit, WhiteBIT, JBEX) и находит арбитражные возможности. Реализована на базе Laravel 11 с использованием Clean Architecture.

**Основные возможности:**
- Получение лучших цен по торговой паре
- Поиск арбитражных возможностей с фильтрацией по прибыли
- Кэширование списка общих пар (TTL: 1 час)
- Graceful degradation при недоступности бирж
- Retry-логика для сетевых запросов

## Требования

- Docker & Docker Compose
- PHP 8.4 (в контейнере)
- PostgreSQL 16 (в контейнере)
- Redis 7.0 (в контейнере)

## Установка

### 1. Запустить сервисы

```powershell
$env:APP_ENV = "test"
docker compose up -d --build
```
### 2. Настроить .env (опционально)

Скопируйте `.env.example` в `.env` и настройте параметры:

```env
# API таймауты и лимиты
API_TIMEOUT=5000
RATE_LIMIT_DELAY=200
PAIRS_CACHE_TTL=3600

# API ключи бирж (опционально)
JBEX_API_KEY=your_api_key
JBEX_API_SECRET=your_api_secret
```

## Использование

### Команда: arb:price

Получить лучшие цены по торговой паре на всех биржах.

```powershell
docker compose exec -T php-arb php artisan arb:price BTC/USDT
```

### Команда: arb:opportunities

Найти арбитражные возможности с фильтрацией.

```powershell
# Все возможности с прибылью >= 0.1%
docker compose exec -T php-arb php artisan arb:opportunities

# Возможности с прибылью >= 0.5%
docker compose exec -T php-arb php artisan arb:opportunities --min-profit=0.5

# Топ-10 возможностей
docker compose exec -T php-arb php artisan arb:opportunities --top=10

# Комбинация фильтров
docker compose exec -T php-arb php artisan arb:opportunities --min-profit=0.3 --top=5
```
-----------------------------------

<details>
  <summary> OUTPUT docker compose exec -T php-arb php artisan arb:price BTC/USDT</summary>

```terminaloutput
Fetching prices for BTC/USDT...

═══════════════════════════════════════════════════════
  Best Prices for BTC/USDT
═══════════════════════════════════════════════════════

Lowest Price:
  Exchange:  Poloniex
  Price:     87157.44
  Time:      57947-03-30 03:04:10

Highest Price:
  Exchange:  Binance
  Price:     87179.99
  Time:      57947-03-30 02:50:01

Price Difference:
  Absolute:  22.550000000003
  Percent:   0.025872719529168%

Statistics:
  Exchanges checked: 5
═══════════════════════════════════════════════════════
```

</details>

-----------------------------------

<details>
  <summary> OUTPUT docker compose exec -T php-arb php artisan arb:opportunities </summary>

```terminaloutput
Searching for arbitrage opportunities...

═══════════════════════════════════════════════════════
  Arbitrage Opportunities
═══════════════════════════════════════════════════════

Filters Applied:
  Min Profit:    0.1%
  Top Results:   All
  Pairs Checked: 75

Found 54 opportunities:

+-------------+----------+-----------------+----------+-----------------+-------------+
| Pair        | Buy From | Buy Price       | Sell To  | Sell Price      | Profit %    |
+-------------+----------+-----------------+----------+-----------------+-------------+
| KAIA/USDT   | Poloniex | 0.00000910      | Binance  | 0.05810000      | 638,361.54% |
| TRUMP/USDT  | Poloniex | 0.06000000      | Binance  | 4.91200000      | 8,086.67%   |
| RED/USDT    | Poloniex | 0.02100000      | WhiteBIT | 0.21090000      | 904.29%     |
| MORPHO/USDT | Poloniex | 0.40000000      | Binance  | 1.19000000      | 197.50%     |
| JTO/USDT    | Binance  | 0.35200000      | Poloniex | 0.77300000      | 119.60%     |
| TOWNS/USDT  | WhiteBIT | 0.00549000      | Poloniex | 0.00847000      | 54.28%      |
| RENDER/USDT | Binance  | 1.26100000      | Poloniex | 1.83060000      | 45.17%      |
| AIXBT/USDT  | Binance  | 0.02810000      | Poloniex | 0.04030000      | 43.42%      |
| GALA/USDT   | WhiteBIT | 0.00605900      | Poloniex | 0.00750000      | 23.78%      |
| TNSR/USDT   | WhiteBIT | 0.08230000      | Poloniex | 0.10130000      | 23.09%      |
| AVNT/USDT   | WhiteBIT | 0.28040000      | Poloniex | 0.33130000      | 18.15%      |
| WLFI/USDT   | Poloniex | 0.11320000      | JBEX     | 0.13230000      | 16.87%      |
| CGPT/USDT   | WhiteBIT | 0.02818000      | Poloniex | 0.03180000      | 12.85%      |
| S/USDT      | WhiteBIT | 0.07140000      | Poloniex | 0.08000000      | 12.04%      |
| PEOPLE/USDT | Poloniex | 0.00850000      | WhiteBIT | 0.00925600      | 8.89%       |
| ZK/USDT     | Poloniex | 0.02530000      | Binance  | 0.02751000      | 8.74%       |
| CRV/USDT    | Poloniex | 0.35000000      | Binance  | 0.37680000      | 7.66%       |
| CAKE/USDT   | Poloniex | 1.68900000      | JBEX     | 1.80500000      | 6.87%       |
| WIF/USDT    | WhiteBIT | 0.32440000      | Poloniex | 0.34380000      | 5.98%       |
| ONDO/USDT   | Poloniex | 0.36750000      | Binance  | 0.38700000      | 5.31%       |
| LINEA/USDT  | Poloniex | 0.00601000      | WhiteBIT | 0.00631000      | 4.99%       |
| ENS/USDT    | WhiteBIT | 9.41050000      | Poloniex | 9.82000000      | 4.35%       |
| EIGEN/USDT  | JBEX     | 0.38700000      | Poloniex | 0.40300000      | 4.13%       |
| OM/USDT     | JBEX     | 0.06930000      | Poloniex | 0.07197000      | 3.85%       |
| BONK/USDT   | Poloniex | 0.00000770      | Binance  | 0.00000799      | 3.79%       |
| SUSHI/USDT  | Poloniex | 0.28050000      | JBEX     | 0.29100000      | 3.74%       |
| SSV/USDT    | JBEX     | 3.75000000      | Poloniex | 3.88000000      | 3.47%       |
| XTZ/USDT    | Poloniex | 0.42410000      | JBEX     | 0.43860000      | 3.42%       |
| STRK/USDT   | Binance  | 0.07780000      | Poloniex | 0.08000000      | 2.83%       |
| ETH/USDC    | WhiteBIT | 2,955.72000000  | Poloniex | 3,032.90000000  | 2.61%       |
| XRP/USDC    | WhiteBIT | 1.87689000      | Poloniex | 1.92490000      | 2.56%       |
| JUP/USDT    | Poloniex | 0.18600000      | Binance  | 0.19010000      | 2.20%       |
| FET/USDT    | Binance  | 0.20390000      | Poloniex | 0.20800000      | 2.01%       |
| PENDLE/USDT | WhiteBIT | 1.76580000      | Poloniex | 1.80010000      | 1.94%       |
| WAL/USDT    | Poloniex | 0.12000000      | Binance  | 0.12150000      | 1.25%       |
| IMX/USDT    | Binance  | 0.22300000      | Poloniex | 0.22550000      | 1.12%       |
| ENA/USDT    | Binance  | 0.20020000      | Poloniex | 0.20220000      | 1.00%       |
| ASTER/USDT  | Binance  | 0.69200000      | Poloniex | 0.69760000      | 0.81%       |
| INJ/USDT    | WhiteBIT | 4.57100000      | Poloniex | 4.60010000      | 0.64%       |
| TON/USDT    | Poloniex | 1.44300000      | Binance  | 1.45100000      | 0.55%       |
| ICP/USDT    | WhiteBIT | 3.01900000      | Poloniex | 3.03200000      | 0.43%       |
| LTC/USDC    | Poloniex | 76.52000000     | Binance  | 76.77000000     | 0.33%       |
| BTC/USDC    | WhiteBIT | 87,218.52000000 | Poloniex | 87,501.01000000 | 0.32%       |
| SAND/USDT   | Poloniex | 0.11320000      | JBEX     | 0.11350000      | 0.27%       |
| BCH/USDT    | WhiteBIT | 579.47000000    | Poloniex | 580.70000000    | 0.21%       |
| PEPE/USDT   | Poloniex | 0.00000394      | Binance  | 0.00000395      | 0.21%       |
| ARB/USDT    | Binance  | 0.18470000      | Poloniex | 0.18500000      | 0.16%       |
| FIL/USDT    | WhiteBIT | 1.27010000      | Binance  | 1.27200000      | 0.15%       |
| UNI/USDT    | Poloniex | 6.02140000      | Binance  | 6.02900000      | 0.13%       |
| FLOKI/USDT  | WhiteBIT | 0.00003987      | Binance  | 0.00003992      | 0.13%       |
| ETH/BTC     | Poloniex | 0.03386000      | JBEX     | 0.03390000      | 0.12%       |
| SKY/USDT    | WhiteBIT | 0.06410000      | Poloniex | 0.06417000      | 0.11%       |
| ETC/USDT    | Poloniex | 12.07700000     | Binance  | 12.09000000     | 0.11%       |
| AAVE/USDT   | JBEX     | 153.02000000    | Poloniex | 153.18000000    | 0.10%       |
+-------------+----------+-----------------+----------+-----------------+-------------+
═══════════════════════════════════════════════════════
```

</details>

-----------------------------------

<details>
  <summary> OUTPUT docker compose exec -T php-arb php artisan arb:opportunities --min-profit=0.3 --top=5 </summary>

```terminaloutput
═══════════════════════════════════════════════════════
  Arbitrage Opportunities
═══════════════════════════════════════════════════════

Filters Applied:
  Min Profit:    0.3%
  Top Results:   5
  Pairs Checked: 127

Found 5 opportunities:

+-------------+----------+------------+----------+------------+-------------+
| Pair        | Buy From | Buy Price  | Sell To  | Sell Price | Profit %    |
+-------------+----------+------------+----------+------------+-------------+
| KAIA/USDT   | Poloniex | 0.00000910 | Binance  | 0.06020000 | 661,438.46% |
| TRUMP/USDT  | Poloniex | 0.06000000 | Binance  | 5.03200000 | 8,286.67%   |
| RED/USDT    | Poloniex | 0.02100000 | Binance  | 0.21630000 | 930.00%     |
| PORTAL/USDT | Binance  | 0.02260000 | Poloniex | 0.11100000 | 391.15%     |
| MORPHO/USDT | Poloniex | 0.40000000 | Binance  | 1.22900000 | 207.25%     |
+-------------+----------+------------+----------+------------+-------------+
═══════════════════════════════════════════════════════
```

</details>

-----------------------------------

## Тестирование

### Запустить все тесты

```powershell
docker compose exec -T php-arb php vendor/bin/phpunit
```

### Запустить unit-тесты

```powershell
docker compose exec -T php-arb php vendor/bin/phpunit tests/Unit
```

### Запустить feature-тесты

```powershell
docker compose exec -T php-arb php vendor/bin/phpunit tests/Feature
```

## Архитектура

Проект следует принципам Clean Architecture:

```
app/
├── Domain/              # Бизнес-логика (чистый PHP)
│   ├── Contracts/       # Интерфейсы
│   ├── Entities/        # Сущности (Ticker, ArbitrageOpportunity)
│   └── Services/        # Доменные сервисы
├── Application/         # Use-cases и оркестрация
│   ├── Services/        # Сервисы приложения
│   └── UseCases/        # Сценарии использования
├── Infrastructure/      # Реализация портов
│   ├── Cache/           # Адаптеры кэша
│   ├── Connectors/      # Коннекторы бирж
│   └── Factories/       # Фабрики клиентов
└── Console/Commands/    # Artisan команды
```

**Зависимости:** `Http -> Application -> Domain <- Infrastructure`

## Поддерживаемые биржи

- **Binance** (CCXT)
- **Poloniex** (CCXT)
- **Bybit** (CCXT)
- **WhiteBIT** (CCXT)
- **JBEX** (Custom REST API)

## Защита от Rate-Limit

Система оптимизирована для минимизации риска блокировки со стороны бирж:

### Текущая оптимизация

- **`arb:opportunities`**: использует `fetchTickers()` — **1 запрос на биржу** (всего 5 запросов), вместо N×5 запросов
- **`arb:price`**: использует `fetchTicker()` — 5 запросов для одной пары (приемлемо)
- **Retry-логика**: 3 попытки с задержкой 200 мс между попытками
- **CCXT rate-limiter**: встроенная защита от превышения лимитов
- **Graceful degradation**: продолжение работы при недоступности отдельных бирж

### Рекомендации

Для снижения риска бана:

1. **Используйте API ключи** (особенно для JBEX) — авторизованные запросы имеют более высокие лимиты
2. **Увеличьте задержки** при частом использовании:
   ```env
   RATE_LIMIT_DELAY=500  # увеличить с 200 до 500 мс
   API_TIMEOUT=10000     # увеличить таймаут до 10 сек
   ```
3. **Ограничьте частоту запуска** команд — не запускайте `arb:opportunities` чаще 1 раза в минуту
4. **Отключите ненужные биржи** в `.env`:
   ```env
   BINANCE_ENABLED=true
   JBEX_ENABLED=false    # отключить при отсутствии ключей
   ```
5. **Используйте кэш** — список общих пар кэшируется на 1 час (настраивается через `PAIRS_CACHE_TTL`)

### Лимиты бирж (публичные API)

- **Binance**: 1200 запросов/мин (weight-based)
- **Bybit**: 120 запросов/мин
- **Poloniex**: 6 запросов/сек
- **WhiteBIT**: 600 запросов/5 мин
- **JBEX**: зависит от наличия API ключа

## Лицензия

Proprietary

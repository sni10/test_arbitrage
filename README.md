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

### 1. Создать сеть Docker (однократно)

```powershell
docker network create internal_sync_network
```

### 2. Запустить сервисы

```powershell
$env:APP_ENV = "test"
docker compose up -d --build
```

### 3. Установить зависимости

```powershell
docker compose exec -T php-arb composer install
```

### 4. Настроить приложение

```powershell
# Сгенерировать ключ приложения
docker compose exec -T php-arb php artisan key:generate

# Выполнить миграции
docker compose exec -T php-arb php artisan migrate
```

### 5. Настроить .env (опционально)

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

**Вывод:**
```
Trading Pair: BTC/USDT
Min Price: 42150.50 (Binance)
Max Price: 42380.20 (Bybit)
Difference: 229.70 (0.545%)
Exchanges checked: 5
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

<details>
  <summary> OUTPUT </summary>

```terminaloutput
═══════════════════════════════════════════════════════
  Best Prices for BTC/USDT
═══════════════════════════════════════════════════════

Lowest Price:
  Exchange:  Poloniex
  Price:     89652.05
  Time:      57945-07-06 22:44:14

Highest Price:
  Exchange:  Binance
  Price:     89703.99
  Time:      57945-07-06 23:20:13

Price Difference:
  Absolute:  51.940000000002
  Percent:   0.057935094624163%

Statistics:
  Exchanges checked: 4
═══════════════════════════════════════════════════════
```

</details>


<details>
  <summary> OUTPUT docker compose exec -T php-arb php artisan arb:opportunities </summary>

```terminaloutput
═══════════════════════════════════════════════════════
Arbitrage Opportunities
═══════════════════════════════════════════════════════

Filters Applied:
Min Profit:    0.1%
Top Results:   All
Pairs Checked: 127

Found 115 opportunities:

+-------------+----------+-----------------+----------+-----------------+-------------+
| Pair        | Buy From | Buy Price       | Sell To  | Sell Price      | Profit %    |
+-------------+----------+-----------------+----------+-----------------+-------------+
| KAIA/USDT   | Poloniex | 0.00000910      | Binance  | 0.06020000      | 661,438.46% |
| TRUMP/USDT  | Poloniex | 0.06000000      | Binance  | 5.03200000      | 8,286.67%   |
| RED/USDT    | Poloniex | 0.02100000      | Binance  | 0.21600000      | 928.57%     |
| PORTAL/USDT | WhiteBIT | 0.02290000      | Poloniex | 0.11100000      | 384.72%     |
| MORPHO/USDT | Poloniex | 0.40000000      | Binance  | 1.23000000      | 207.50%     |
| JTO/USDT    | WhiteBIT | 0.35800000      | Poloniex | 0.77300000      | 115.92%     |
| ALT/USDT    | Poloniex | 0.00582000      | Binance  | 0.01166000      | 100.34%     |
| LUNA/USDT   | Poloniex | 0.06200000      | Binance  | 0.11610000      | 87.26%      |
| STX/USDT    | WhiteBIT | 0.25220000      | Poloniex | 0.42000000      | 66.53%      |
| HAEDAL/USDT | Poloniex | 0.02360000      | Binance  | 0.03840000      | 62.71%      |
| GALA/USDT   | Binance  | 0.00626000      | Poloniex | 0.00999000      | 59.58%      |
| TOWNS/USDT  | Binance  | 0.00556000      | Poloniex | 0.00847000      | 52.34%      |
| NEWT/USDT   | Poloniex | 0.06670000      | Binance  | 0.09980000      | 49.63%      |
| RENDER/USDT | WhiteBIT | 1.31400000      | Poloniex | 1.83060000      | 39.32%      |
| AIXBT/USDT  | WhiteBIT | 0.02910000      | Poloniex | 0.04030000      | 38.49%      |
| APE/USDT    | Poloniex | 0.15000000      | Binance  | 0.20040000      | 33.60%      |
| BLUR/USDT   | WhiteBIT | 0.02790000      | Poloniex | 0.03540000      | 26.88%      |
| AVNT/USDT   | WhiteBIT | 0.26350000      | Poloniex | 0.33130000      | 25.73%      |
| EGLD/USDT   | WhiteBIT | 6.41920000      | Poloniex | 7.91000000      | 23.22%      |
| LUNC/USDT   | Poloniex | 0.00003300      | Binance  | 0.00004006      | 21.39%      |
| WLFI/USDT   | Poloniex | 0.11320000      | Binance  | 0.13510000      | 19.35%      |
| TNSR/USDT   | Binance  | 0.08490000      | Poloniex | 0.10130000      | 19.32%      |
| GMX/USDT    | Poloniex | 7.18000000      | WhiteBIT | 8.40000000      | 16.99%      |
| SNX/USDT    | Binance  | 0.42500000      | Poloniex | 0.49200000      | 15.76%      |
| WOO/USDT    | Poloniex | 0.02340000      | Binance  | 0.02680000      | 14.53%      |
| ID/USDT     | WhiteBIT | 0.06147000      | Poloniex | 0.07000000      | 13.88%      |
| KSM/USDT    | WhiteBIT | 7.02900000      | Poloniex | 8.00000000      | 13.81%      |
| UMA/USDT    | WhiteBIT | 0.70990000      | Poloniex | 0.80700000      | 13.68%      |
| PEOPLE/USDT | Poloniex | 0.00850000      | WhiteBIT | 0.00950600      | 11.84%      |
| CAKE/USDT   | Poloniex | 1.68900000      | Binance  | 1.85800000      | 10.01%      |
| CGPT/USDT   | WhiteBIT | 0.02898000      | Poloniex | 0.03180000      | 9.73%       |
| BICO/USDT   | Poloniex | 0.03830000      | WhiteBIT | 0.04200000      | 9.66%       |
| ZK/USDT     | Poloniex | 0.02530000      | WhiteBIT | 0.02762000      | 9.17%       |
| LINEA/USDT  | Poloniex | 0.00601000      | Binance  | 0.00653000      | 8.65%       |
| BOME/USDT   | Poloniex | 0.00054550      | Binance  | 0.00059000      | 8.16%       |
| NXPC/USDT   | Poloniex | 0.35700000      | Binance  | 0.38530000      | 7.93%       |
| MET/USDT    | Poloniex | 0.22600000      | Binance  | 0.24300000      | 7.52%       |
| BAT/USDT    | WhiteBIT | 0.21280000      | Poloniex | 0.22860000      | 7.42%       |
| GMT/USDT    | Poloniex | 0.01370000      | WhiteBIT | 0.01467000      | 7.08%       |
| NEXO/USDT   | WhiteBIT | 0.93470000      | Poloniex | 1.00000000      | 6.99%       |
| RUNE/USDT   | Poloniex | 0.55450000      | Binance  | 0.59200000      | 6.76%       |
| S/USDT      | WhiteBIT | 0.07500000      | Poloniex | 0.08000000      | 6.67%       |
| BONK/USDT   | Poloniex | 0.00000770      | Binance  | 0.00000821      | 6.65%       |
| FXS/USDT    | Poloniex | 0.62000000      | WhiteBIT | 0.66100000      | 6.61%       |
| CRV/USDT    | Poloniex | 0.35000000      | WhiteBIT | 0.37310000      | 6.60%       |
| WAL/USDT    | Poloniex | 0.12000000      | Binance  | 0.12740000      | 6.17%       |
| ONDO/USDT   | Poloniex | 0.37670000      | Binance  | 0.39970000      | 6.11%       |
| AXS/USDT    | Poloniex | 0.81000000      | Binance  | 0.85900000      | 6.05%       |
| PENDLE/USDT | Poloniex | 1.80000000      | Binance  | 1.90800000      | 6.00%       |
| JUP/USDT    | Poloniex | 0.18600000      | Binance  | 0.19530000      | 5.00%       |
| RPL/USDT    | WhiteBIT | 1.86000000      | Poloniex | 1.95000000      | 4.84%       |
| QTUM/USDT   | WhiteBIT | 1.26960000      | Poloniex | 1.32800000      | 4.60%       |
| ENA/USDT    | Poloniex | 0.20220000      | Binance  | 0.21090000      | 4.30%       |
| AGLD/USDT   | WhiteBIT | 0.25000000      | Poloniex | 0.26000000      | 4.00%       |
| RDNT/USDT   | WhiteBIT | 0.00937000      | Poloniex | 0.00970000      | 3.52%       |
| LRC/USDT    | Poloniex | 0.05450000      | Binance  | 0.05630000      | 3.30%       |
| SSV/USDT    | WhiteBIT | 3.76530000      | Poloniex | 3.88000000      | 3.05%       |
| EIGEN/USDT  | Binance  | 0.39200000      | Poloniex | 0.40300000      | 2.81%       |
| XTZ/USDT    | Poloniex | 0.44000000      | Binance  | 0.45100000      | 2.50%       |
| ENJ/USDT    | Poloniex | 0.02680000      | Binance  | 0.02741000      | 2.28%       |
| C98/USDT    | WhiteBIT | 0.02200000      | Poloniex | 0.02250000      | 2.27%       |
| OM/USDT     | Binance  | 0.07070000      | Poloniex | 0.07197000      | 1.80%       |
| INJ/USDT    | Poloniex | 4.60010000      | Binance  | 4.68200000      | 1.78%       |
| LTC/BTC     | Poloniex | 0.00086400      | Binance  | 0.00087900      | 1.74%       |
| ENS/USDT    | WhiteBIT | 9.66270000      | Poloniex | 9.82000000      | 1.63%       |
| ZRO/USDT    | Binance  | 1.31300000      | Poloniex | 1.33300000      | 1.52%       |
| STRK/USDT   | Poloniex | 0.08000000      | Binance  | 0.08120000      | 1.50%       |
| WIF/USDT    | WhiteBIT | 0.33890000      | Poloniex | 0.34380000      | 1.45%       |
| YFI/USDT    | Poloniex | 3,380.14000000  | Binance  | 3,427.00000000  | 1.39%       |
| SUSHI/USDT  | WhiteBIT | 0.29730000      | Poloniex | 0.30130000      | 1.35%       |
| CHZ/USDT    | WhiteBIT | 0.03551400      | Poloniex | 0.03597000      | 1.28%       |
| GRT/USDT    | Poloniex | 0.03810000      | Binance  | 0.03853000      | 1.13%       |
| G/USDT      | WhiteBIT | 0.00445000      | Poloniex | 0.00450000      | 1.12%       |
| AEVO/USDT   | Poloniex | 0.03600000      | Binance  | 0.03640000      | 1.11%       |
| ETHFI/USDT  | WhiteBIT | 0.71900000      | Poloniex | 0.72660000      | 1.06%       |
| TRX/USDC    | WhiteBIT | 0.28325800      | Poloniex | 0.28608000      | 1.00%       |
| SKY/USDT    | Binance  | 0.06760000      | Poloniex | 0.06823000      | 0.93%       |
| FET/USDT    | Poloniex | 0.20800000      | Binance  | 0.20960000      | 0.77%       |
| COMP/USDT   | Poloniex | 24.00000000     | WhiteBIT | 24.18000000     | 0.75%       |
| ICP/USDT    | Binance  | 3.05400000      | Poloniex | 3.07200000      | 0.59%       |
| SOL/BTC     | Poloniex | 0.00141500      | WhiteBIT | 0.00142173      | 0.48%       |
| SAND/USDT   | Poloniex | 0.11700000      | WhiteBIT | 0.11753600      | 0.46%       |
| DOT/BTC     | Binance  | 0.00002041      | Poloniex | 0.00002050      | 0.44%       |
| IMX/USDT    | Binance  | 0.23100000      | WhiteBIT | 0.23200000      | 0.43%       |
| ASTER/USDT  | Poloniex | 0.70700000      | WhiteBIT | 0.71000000      | 0.42%       |
| JST/USDT    | Binance  | 0.03979000      | Poloniex | 0.03994000      | 0.38%       |
| XLM/BTC     | WhiteBIT | 0.00000250      | Binance  | 0.00000251      | 0.37%       |
| TON/USDT    | WhiteBIT | 1.46600000      | Poloniex | 1.47100000      | 0.34%       |
| AAVE/USDT   | Poloniex | 154.84000000    | Binance  | 155.35000000    | 0.33%       |
| SUI/USDT    | Poloniex | 1.47180000      | Binance  | 1.47660000      | 0.33%       |
| BTC/USDC    | Poloniex | 89,219.77000000 | WhiteBIT | 89,496.75000000 | 0.31%       |
| ARB/USDT    | WhiteBIT | 0.18970000      | Poloniex | 0.19020000      | 0.26%       |
| LDO/USDT    | WhiteBIT | 0.54860000      | Poloniex | 0.55000000      | 0.26%       |
| PEPE/USDT   | WhiteBIT | 0.00000402      | Binance  | 0.00000403      | 0.22%       |
| ETH/USDC    | Poloniex | 3,032.90000000  | Binance  | 3,039.27000000  | 0.21%       |
| ADA/USDT    | WhiteBIT | 0.37602700      | Binance  | 0.37680000      | 0.21%       |
| LTC/USDC    | WhiteBIT | 78.66000000     | Poloniex | 78.82000000     | 0.20%       |
| DOT/USDT    | WhiteBIT | 1.82650000      | Binance  | 1.83000000      | 0.19%       |
| POL/USDT    | WhiteBIT | 0.10890000      | Poloniex | 0.10910000      | 0.18%       |
| A/USDT      | Poloniex | 0.16420000      | Binance  | 0.16450000      | 0.18%       |
| XRP/USDC    | Poloniex | 1.92460000      | Binance  | 1.92810000      | 0.18%       |
| ZRX/USDT    | WhiteBIT | 0.12130000      | Poloniex | 0.12150000      | 0.16%       |
| MANA/USDT   | Poloniex | 0.12480000      | Binance  | 0.12500000      | 0.16%       |
| FIL/USDT    | Poloniex | 1.31400000      | Binance  | 1.31600000      | 0.15%       |
| FLOKI/USDT  | WhiteBIT | 0.00004083      | Poloniex | 0.00004089      | 0.15%       |
| SHIB/USDT   | Poloniex | 0.00000732      | Binance  | 0.00000733      | 0.14%       |
| DOGE/USDT   | WhiteBIT | 0.13354340      | Binance  | 0.13372000      | 0.13%       |
| NEAR/USDT   | Poloniex | 1.55400000      | Binance  | 1.55600000      | 0.13%       |
| LINK/USDT   | Poloniex | 12.73460000     | Binance  | 12.75000000     | 0.12%       |
| XRP/BTC     | Binance  | 0.00002154      | WhiteBIT | 0.00002156      | 0.11%       |
| ATOM/USDT   | WhiteBIT | 1.97880000      | Binance  | 1.98100000      | 0.11%       |
| OP/USDT     | WhiteBIT | 0.27590000      | Binance  | 0.27620000      | 0.11%       |
| BTC/USDT    | Poloniex | 89,376.77000000 | Binance  | 89,470.00000000 | 0.10%       |
| SUN/USDT    | Binance  | 0.02041000      | Poloniex | 0.02043110      | 0.10%       |
| SOL/USDT    | WhiteBIT | 127.03000000    | Binance  | 127.16000000    | 0.10%       |
+-------------+----------+-----------------+----------+-----------------+-------------+
═══════════════════════════════════════════════════════
```

</details>

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


**Вывод:**
```
+----------+----------+-------------+----------+-------------+
| Pair     | Buy      | Buy Price   | Sell     | Sell Price  | Profit % |
+----------+----------+-------------+----------+-------------+
| BTC/USDT | Binance  | 42150.50    | Bybit    | 42380.20    | 0.545%   |
| ETH/USDT | Binance  | 2234.10     | Bybit    | 2245.80     | 0.524%   |
+----------+----------+-------------+----------+-------------+

Total opportunities: 2
Pairs checked: 15
Min profit filter: 0.1%
```

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

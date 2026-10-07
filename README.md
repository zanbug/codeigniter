<p align="center"><img src="https://zanbug.com/logo.png" alt="Zanbug" width="120"></p>

# zanbug/codeigniter

CodeIgniter adapter for the [Zanbug](https://zanbug.com) error & monitoring
SDK. Works with **CodeIgniter 3 and CodeIgniter 4**, and even in
composer-free legacy projects. PHP **7.0+**.

## Install (Composer)

```bash
composer require zanbug/codeigniter
```

Set your credentials as environment variables (in `.env` or the server env):

```dotenv
ZANBUG_API_KEY=bb_xxxxxxxxxxxxxxxx
ZANBUG_HOST=https://zanbug.com
```

---

## CodeIgniter 3

### Option A — hooks (recommended)

1. Enable hooks in `application/config/config.php`:

   ```php
   $config['enable_hooks'] = TRUE;
   ```

2. Register the hook in `application/config/hooks.php` on `pre_system`:

   ```php
   $hook['pre_system'] = array(
       'class'    => 'Zanbug\\CodeIgniter\\Hooks\\ZanbugHook',
       'function' => '__construct',
       'filename' => '', // loaded via Composer autoloader
       'filepath' => '',
   );
   ```

   The hook reads `ZANBUG_API_KEY` and `ZANBUG_HOST` from the environment and
   boots the SDK (init + register global handlers) automatically.

### Option B — composer-free (legacy)

If you cannot run Composer, require the core SDK's manual autoloader directly
in `index.php` (before CodeIgniter bootstraps):

```php
require __DIR__ . '/path/to/zanbug-php-sdk/autoload.php';

\Zanbug\Sdk\Zanbug::init([
    'api_key' => getenv('ZANBUG_API_KEY') ?: 'bb_xxxxxxxxxxxxxxxx',
    'host'    => 'https://zanbug.com',
]);
\Zanbug\Sdk\Zanbug::registerHandlers();
```

> ⚠️ **CI3 caveat:** CodeIgniter 3 calls `set_exception_handler()` during its own
> bootstrap, which *replaces* the handler you register in `index.php`. So the
> `index.php` approach alone captures **manual** `Zanbug::capture($e)` calls but
> **not uncaught exceptions**. For automatic capture of uncaught exceptions in CI3,
> use **Option A (the `pre_system` hook)** — it runs *after* CI3 installs its handler
> and re-registers Zanbug's, so Zanbug wins.

---

## CodeIgniter 4

Register a `pre_system` event in `app/Config/Events.php`:

```php
Events::on('pre_system', static function () {
    \Zanbug\CodeIgniter\ZanbugCI::boot([
        'api_key' => getenv('ZANBUG_API_KEY'),
        'host'    => getenv('ZANBUG_HOST'),
    ]);
});
```

`ZanbugCI::boot()` initializes the SDK and registers global error, exception
and shutdown handlers.

---

## Slow query monitoring

Queries slower than `slow_query_ms` (default 1000 ms, pass it in the `boot()`
config array) are batched and sent non-blocking at shutdown. Disable with
`'capture_queries' => false`.

- **CodeIgniter 4** — automatic: `boot()` subscribes to the framework's
  `DBQuery` event. Works with any DB driver (MySQLi, Postgre, SQLite3, ...).
- **CodeIgniter 3** — no query event exists, so call this once late in the
  request (e.g. a `post_system` hook). It reads the driver's built-in query
  log (`save_queries` must stay enabled — it is by default):

  ```php
  \Zanbug\CodeIgniter\ZanbugCI::flushCi3Queries();
  ```

  Or record individual queries manually from anywhere:

  ```php
  \Zanbug\Sdk\Zanbug::recordQuery($sql, $durationMs, ['connection' => 'mysqli']);
  ```

---

## Manual capture

Anywhere in your app:

```php
use Zanbug\CodeIgniter\ZanbugCI;

try {
    // ...
} catch (\Throwable $e) {
    ZanbugCI::capture($e);
}
```

Or via the core facade:

```php
\Zanbug\Sdk\Zanbug::captureMessage('Payment gateway timeout', 'error');
```

## Updating the SDK
```bash
vendor/bin/zanbug check      # exit 10 when a newer version is published
vendor/bin/zanbug update     # composer require zanbug/php-sdk:^<latest> zanbug/codeigniter:^<latest>
```
Automatic: `ZANBUG_AUTO_UPDATE=true` (or `'auto_update' => true` in the SDK config) — once a day at the end of a CLI run the SDK updates itself in a detached process (log `<tmp>/zanbug-update.log`). Restart workers afterwards. Details: [zanbug/php-sdk README](https://github.com/zanbug/php-sdk#updating-the-sdk).

## Log capture (errors logged but not thrown)

Errors you catch and log without re-throwing only reach the log file. Enable
`capture_logs` and forward them to Zanbug with `recordLog()`:

```php
\Zanbug\Sdk\Zanbug::init([
    'api_key'      => 'bb_xxxxxxxx',
    'host'         => 'https://zanbug.com',
    'capture_logs' => true,
    'log_level'    => 'error', // minimum PSR level forwarded
]);

// Anywhere you'd log an error:
\Zanbug\Sdk\Zanbug::recordLog('error', 'Payment gateway timeout', ['order_id' => 123]);

// Caught-and-logged throwable (attach it for a full stacktrace):
try { risky(); } catch (\Throwable $e) {
    \Zanbug\Sdk\Zanbug::recordLog('error', $e->getMessage(), ['exception' => $e]);
}
```

Records below `log_level` are dropped; context is redacted; `recordLog()` never throws.

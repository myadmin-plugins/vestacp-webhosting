---
name: plugin-event-handler
description: Adds a new Symfony GenericEvent lifecycle handler to src/Plugin.php and registers it in getHooks(). Use when user says 'add event handler', 'new lifecycle hook', 'handle plugin event', or adds a method to src/Plugin.php. Key: checks $event['category'], calls get_module_settings(), instantiates VestaCP, logs with myadmin_log(), calls $event->stopPropagation(). Do NOT use for new VestaCP API methods (use vestacp-api-method skill instead).
---
# Plugin Event Handler

## Critical

- **Always** guard the entire handler body with `if ($event['category'] == get_service_define('WEB_VESTA'))` — without this check, the handler fires for every webhosting provider.
- **Always** call `$event->stopPropagation()` before closing the `if` block — omitting it allows other providers to double-handle the event.
- **Never** interpolate `$_GET`/`$_POST` directly — escape user data with `$db->real_escape()` before any DB query.
- `self::$module` is always `'webhosting'` — do not hardcode the string elsewhere.
- Register every new handler in `getHooks()` or it will never fire.

## Instructions

### Step 1 — Define the method signature in `src/Plugin.php`

Add a `public static` method with a `GenericEvent $event` parameter and a `@param` docblock. Place it alongside the existing lifecycle methods (`getActivate`, `getReactivate`, `getDeactivate`, `getTerminate`).

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getYourEvent(GenericEvent $event)
{
}
```

Verify the method is `public static` before proceeding.

### Step 2 — Add the category guard and get the service subject

The first two lines inside every handler are identical across all existing methods:

```php
public static function getYourEvent(GenericEvent $event)
{
    if ($event['category'] == get_service_define('WEB_VESTA')) {
        $serviceClass = $event->getSubject();
```

Verify `get_service_define('WEB_VESTA')` is the constant used (not a literal integer) before proceeding.

### Step 3 — Load settings and server credentials

Copy this block verbatim — all lifecycle handlers use this exact credential extraction pattern:

```php
        $settings = get_module_settings(self::$module);
        $serverdata = get_service_master($serviceClass->getServer(), self::$module);
        $hash = $serverdata[$settings['PREFIX'].'_key'];
        $ip = $serverdata[$settings['PREFIX'].'_ip'];
        [$user, $pass] = explode(':', $hash);
```

Verify `$settings['PREFIX']` is used (not a hardcoded prefix string) before proceeding.

### Step 4 — Log entry and instantiate VestaCP

Log before instantiation; mask the password in the log message:

```php
        myadmin_log(self::$module, 'info', 'VestaCP YourEventName', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $vesta = new VestaCP($ip, $user, $pass);
```

`VestaCP` is already imported via `use Detain\MyAdminVestaCP\VestaCP;` at the top of the file — do not use the fully-qualified class name unless inside a scope that loses the import.

### Step 5 — Call the VestaCP method and branch on result

Log the call with args before executing, then branch on the boolean return:

```php
        myadmin_log(self::$module, 'info', "Calling vesta->someMethod({$serviceClass->getUsername()})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
        if ($vesta->someMethod($serviceClass->getUsername())) {
            $event['success'] = true;
            myadmin_log(self::$module, 'info', 'Success, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        } else {
            $event['success'] = false;
            myadmin_log(self::$module, 'info', 'Failure, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        }
```

Use `json_encode($vesta->response)` for response logging (not `var_export`) unless the handler is `getActivate`, which uses `var_export` for the creation response.

### Step 6 — Stop propagation and close blocks

```php
        $event->stopPropagation();
    }
}
```

`stopPropagation()` must be inside the `if` block, not after it.

### Step 7 — Register in `getHooks()`

Add one line to the array returned by `getHooks()` in `src/Plugin.php`:

```php
public static function getHooks()
{
    return [
        self::$module.'.settings'    => [__CLASS__, 'getSettings'],
        self::$module.'.activate'    => [__CLASS__, 'getActivate'],
        self::$module.'.reactivate'  => [__CLASS__, 'getReactivate'],
        self::$module.'.deactivate'  => [__CLASS__, 'getDeactivate'],
        self::$module.'.terminate'   => [__CLASS__, 'getTerminate'],
        self::$module.'.your_event'  => [__CLASS__, 'getYourEvent'],  // add here
    ];
}
```

Verify the event name string matches exactly what `run_event()` uses in the caller before proceeding.

### Step 8 — Run tests

Run `vendor/bin/phpunit` (configured via `phpunit.xml.dist`) — all tests must pass before the handler is considered complete.

## Examples

**User says:** "Add a handler for the `webhosting.suspend_bandwidth` event that calls `$vesta->suspendAccount()`"

**Actions taken:**
1. Add `getSuspendBandwidth` method to `src/Plugin.php` following Steps 1–6.
2. Register `self::$module.'.suspend_bandwidth' => [__CLASS__, 'getSuspendBandwidth']` in `getHooks()`.

**Result:**

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getSuspendBandwidth(GenericEvent $event)
{
    if ($event['category'] == get_service_define('WEB_VESTA')) {
        $serviceClass = $event->getSubject();
        $settings = get_module_settings(self::$module);
        $serverdata = get_service_master($serviceClass->getServer(), self::$module);
        $hash = $serverdata[$settings['PREFIX'].'_key'];
        $ip = $serverdata[$settings['PREFIX'].'_ip'];
        [$user, $pass] = explode(':', $hash);
        myadmin_log(self::$module, 'info', 'VestaCP Bandwidth Suspension', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $vesta = new VestaCP($ip, $user, $pass);
        myadmin_log(self::$module, 'info', "Calling vesta->suspendAccount({$serviceClass->getUsername()})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
        if ($vesta->suspendAccount($serviceClass->getUsername())) {
            $event['success'] = true;
            myadmin_log(self::$module, 'info', 'Success, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        } else {
            $event['success'] = false;
            myadmin_log(self::$module, 'info', 'Failure, Response: '.json_encode($vesta->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        }
        $event->stopPropagation();
    }
}
```

## Common Issues

**Handler never fires:**
- Verify the event name in `getHooks()` exactly matches the string passed to `run_event()` in the caller. Even a trailing `s` difference (`terminate` vs `terminates`) silently skips the handler.

**`$event['category']` check always fails:**
- Run `var_dump(get_service_define('WEB_VESTA'))` to confirm the constant is defined in the current request context. If it returns `null`, `function_requirements('get_service_define')` has not been called.

**`explode(':', $hash)` produces only one element:**
- The server's `_key` field is not in `user:password` format. Check `$serverdata[$settings['PREFIX'].'_key']` directly — an empty or misconfigured master server record will produce a single-element array and `$pass` will be undefined.

**`VestaCP` class not found:**
- Confirm `use Detain\MyAdminVestaCP\VestaCP;` exists at the top of `src/Plugin.php`. If writing a handler outside that file, use the fully-qualified name `\Detain\MyAdminVestaCP\VestaCP`.

**PHPUnit test fails with "method does not exist on VestaCP":**
- The VestaCP method must exist in `src/VestaCP.php` before the handler can call it. If it doesn't exist, add it there first (that is a separate task from adding the event handler).

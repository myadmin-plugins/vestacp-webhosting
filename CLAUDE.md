# myadmin-vestacp-webhosting

MyAdmin plugin: VestaCP webhosting lifecycle management.

## Commands

```bash
composer install               # install deps
vendor/bin/phpunit             # run all tests (uses phpunit.xml.dist)
```

## Structure

- **API client**: `src/VestaCP.php` — cURL POST to `https://{host}:8083/api/` for every VestaCP command
- **Plugin**: `src/Plugin.php` — registers Symfony `GenericEvent` hooks for the `webhosting` module lifecycle
- **Tests**: `tests/VestaCPTest.php` · `tests/PluginTest.php` — PHPUnit 9, config `phpunit.xml.dist`
- **Autoload**: PSR-4 `Detain\MyAdminVestaCP\` → `src/` · dev `Detain\MyAdminVestaCP\Tests\` → `tests/`
- **CI/CD**: `.github/` contains workflows for automated testing and deployment; `.idea/` contains `inspectionProfiles/`, `deployment.xml`, and `encodings.xml`

## VestaCP API Pattern

Every method in `src/VestaCP.php` follows this exact cURL pattern — do not deviate:

```php
$postvars = [
    'user' => $this->username,
    'password' => $this->password,
    'returncode' => 'yes',
    'cmd' => 'v-command-name',
    'arg1' => $arg1,
];
$postdata = http_build_query($postvars);
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
$this->response = curl_exec($curl);
// success: $this->response == '0'
```

## Plugin Event Handler Pattern

All lifecycle methods in `src/Plugin.php` follow this pattern:

```php
public static function getActivate(GenericEvent $event) {
    if ($event['category'] == get_service_define('WEB_VESTA')) {
        $serviceClass = $event->getSubject();
        $settings = get_module_settings(self::$module);
        $serverdata = get_service_master($serviceClass->getServer(), self::$module);
        [$user, $pass] = explode(':', $serverdata[$settings['PREFIX'].'_key']);
        $ip = $serverdata[$settings['PREFIX'].'_ip'];
        myadmin_log(self::$module, 'info', 'message', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $vesta = new VestaCP($ip, $user, $pass);
        // ... call $vesta->method()
        $event['success'] = true;
        $event->stopPropagation();
    }
}
```

Register new hooks in `getHooks()`:
```php
public static function getHooks() {
    return [
        self::$module.'.activate'   => [__CLASS__, 'getActivate'],
        self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
        // add new hooks here
    ];
}
```

## Conventions

- `self::$module` is `'webhosting'` — used in all `myadmin_log()` and `get_module_settings()` calls
- Log every VestaCP call before and after: `myadmin_log(self::$module, 'info', 'Calling vesta->method()', __LINE__, __FILE__, self::$module, $id)`
- Check `$event['category'] == get_service_define('WEB_VESTA')` before acting in every handler
- DB updates use `get_module_db(self::$module)` and `$db->real_escape()` on all user data
- CI: `.scrutinizer.yml` runs `vendor/bin/phpunit tests/ -v --coverage-clover coverage.xml --whitelist src/`
- Code style: tabs for indentation (see `.scrutinizer.yml` coding_style section)

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->

---
name: phpunit-plugin-test
description: Adds PHPUnit 9 tests to tests/VestaCPTest.php or tests/PluginTest.php matching the existing test structure. Use when user says 'add test', 'write unit test', 'test this method', or creates files in tests/. Key: namespace Detain\MyAdminVestaCP\Tests\, bootstrap via vendor/autoload.php, run with vendor/bin/phpunit. Do NOT use for integration or end-to-end tests that require a live VestaCP server or database.
---
# PHPUnit Plugin Test

## Critical

- **Never mock cURL calls** — all VestaCP API method tests use `ReflectionMethod` + source inspection (`getMethodSource`), not live cURL calls. Do NOT attempt to mock `curl_exec` or `curl_init`.
- **Source inspection pattern** — to assert what a method does internally, use the private `getMethodSource(ReflectionMethod $method): string` helper (already present in both test files). Call `$this->assertStringContainsString(...)` on the returned string.
- **No live server calls** — tests must pass with no VestaCP server available. Only instantiation, reflection, property access, and source-code assertions are allowed.
- **Test method names** must start with `test` and be `void`-typed with `declare(strict_types=1)` at the top of the file.

## Instructions

1. **Identify the correct test file.**
   - New tests for `src/VestaCP.php` → `tests/VestaCPTest.php`
   - New tests for `src/Plugin.php` → `tests/PluginTest.php`
   - Verify the file exists before editing: `ls tests/`

2. **Match the file header exactly.** Both files open with:
   ```php
   <?php

   declare(strict_types=1);

   namespace Detain\MyAdminVestaCP\Tests;

   use Detain\MyAdminVestaCP\VestaCP; // or Plugin
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;
   use ReflectionMethod;
   ```
   Do not add extra `use` imports unless the new test strictly requires them.

3. **Write the test method.** Choose one of three patterns based on what you are testing:

   **Pattern A — Property/constructor assertion** (no reflection needed for simple cases):
   ```php
   /**
    * Test that [describe what is verified].
    *
    * @return void
    */
   public function testMyNewBehavior(): void
   {
       $vesta = new VestaCP('host.example.com', 'admin', 's3cret');
       $this->assertSame('expected', $vesta->someProperty);
   }
   ```

   **Pattern B — Method signature via Reflection** (for new methods added to `VestaCP`):
   ```php
   public function testMyMethodSignature(): void
   {
       $method = $this->reflection->getMethod('myMethod');
       $params = $method->getParameters();
       $this->assertCount(2, $params);
       $names = array_map(fn($p) => $p->getName(), $params);
       $this->assertSame(['username', 'domain'], $names);
       $this->assertTrue($params[0]->isOptional() === false);
   }
   ```

   **Pattern C — Source inspection** (to assert cURL command strings, credentials, flags):
   ```php
   public function testMyMethodUsesCorrectVestaCommand(): void
   {
       $source = $this->getMethodSource($this->reflection->getMethod('myMethod'));
       $this->assertStringContainsString("'v-my-command'", $source);
       $this->assertStringContainsString("'user' => \$this->username", $source);
       $this->assertStringContainsString('$this->response = curl_exec($curl)', $source);
   }
   ```

4. **For data-provider tests**, name the provider `<testName>Provider` and annotate with `@dataProvider`:
   ```php
   /**
    * @return array<string, array{string}>
    */
   public function myMethodProvider(): array
   {
       return [
           'myMethod' => ['myMethod'],
       ];
   }
   ```

5. **Update count assertions** if your new test adds a method or property to the class under test. Specifically:
   - `VestaCPTest::testPublicMethodCount()` asserts `assertCount(14, $ownMethods)` — increment if a new public method is added to `VestaCP`.
   - `PluginTest::testGetHooksCount()` asserts `assertCount(5, $hooks)` — increment if a hook is added to `Plugin::getHooks()`.
   - `PluginTest::testAllOwnMethodsAreKnown()` lists every method by name — add new method names to `$expectedMethods`.
   - Verify: `grep -n 'assertCount' tests/VestaCPTest.php tests/PluginTest.php`

6. **Run the tests** and confirm they pass:
   ```bash
   vendor/bin/phpunit
   ```
   All tests must be green before committing.

## Examples

**User says:** "Add a test for the new `renameAccount` method that takes `$oldUsername` and `$newUsername` and calls `v-rename-user`."

**Actions taken:**
1. Open `tests/VestaCPTest.php`.
2. Add to `publicMethodProvider()`: `'renameAccount' => ['renameAccount'],`
3. Increment `assertCount(14, ...)` → `assertCount(15, ...)`
4. Add two test methods:

```php
/**
 * Test renameAccount method parameter signature.
 *
 * @return void
 */
public function testRenameAccountSignature(): void
{
    $method = $this->reflection->getMethod('renameAccount');
    $params = $method->getParameters();
    $this->assertCount(2, $params);
    $names = array_map(fn($p) => $p->getName(), $params);
    $this->assertSame(['oldUsername', 'newUsername'], $names);
    $this->assertFalse($params[0]->isOptional());
    $this->assertFalse($params[1]->isOptional());
}

/**
 * Test that renameAccount uses the v-rename-user command.
 *
 * @return void
 */
public function testRenameAccountUsesCorrectCommand(): void
{
    $source = $this->getMethodSource($this->reflection->getMethod('renameAccount'));
    $this->assertStringContainsString("'v-rename-user'", $source);
    $this->assertStringContainsString("'user' => \$this->username", $source);
    $this->assertStringContainsString('$this->response = curl_exec($curl)', $source);
    $this->assertStringContainsString(':8083/api/', $source);
}
```

5. Run `vendor/bin/phpunit` → all green.

**Result:** Two new passing tests, counts updated, no live server required.

## Common Issues

- **`Error: Call to undefined method Detain\MyAdminVestaCP\VestaCP::myMethod()`** in reflection tests:
  The method doesn't exist yet in `src/VestaCP.php`. Write the source method first, then the test.

- **`Failed asserting that 14 matches expected 15`** in `testPublicMethodCount`:
  You added a method but forgot to update the `assertCount` in `testPublicMethodCount()`. Check line ~332 in `tests/VestaCPTest.php`.

- **`Class 'Detain\MyAdminVestaCP\Tests\...' not found`** when running PHPUnit:
  The test namespace must be exactly `Detain\MyAdminVestaCP\Tests` (no trailing backslash). Verify `declare(strict_types=1)` and the `namespace` line are present.

- **`No tests executed`** or wrong test file run:
  Always run from the package root: `cd /path/to/myadmin-vestacp-webhosting && vendor/bin/phpunit`. The `phpunit.xml.dist` at the package root points to `tests/`.

- **`ReflectionException: Method X does not exist`** in source-inspection tests:
  The method name is case-sensitive. Confirm the exact name in `src/VestaCP.php` or `src/Plugin.php` with `grep 'public function' src/VestaCP.php`.

- **`assertStringContainsString` fails on source inspection**:
  The helper `getMethodSource` reads lines `$startLine - 1` to `$endLine`. If the assertion string spans the opening `{` line (line before `$startLine`), it will be missed. Move the assertion string to a line inside the method body, not the signature line.
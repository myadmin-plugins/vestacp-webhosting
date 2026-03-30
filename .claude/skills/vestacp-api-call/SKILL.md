---
name: vestacp-api-call
description: Adds a new method to `src/VestaCP.php` following the cURL POST pattern to `https://{hostname}:8083/api/`. Use when user says 'add VestaCP command', 'new API method', 'call VestaCP API', or needs to add a method to `src/VestaCP.php`. Key capabilities: builds `$postvars` with auth + cmd + argN, calls `http_build_query`, sets `$this->response = curl_exec($curl)`, checks response `== '0'` for success. Do NOT use for modifying `src/Plugin.php` event handlers or for reading from the VestaCP API with JSON output (use the list-method variant instead).
---
# vestacp-api-call

## Critical

- **Never** use GET requests, guzzle, or file_get_contents — every call MUST use the exact cURL block below.
- **Never** omit `CURLOPT_SSL_VERIFYPEER` or `CURLOPT_SSL_VERIFYHOST` — both must be `false`.
- **Never** build the URL inline — always construct the endpoint URL from `$this->hostname` as shown in `src/VestaCP.php`.
- Command-type methods (add/delete/suspend/unsuspend) MUST include `'returncode' => 'yes'` in `$postvars` and check `$this->response == '0'` for success.
- List/query methods (returning data) omit `returncode`, do NOT return bool — they `json_decode($this->response, true)` instead.
- The method must be non-static and public. `$this->response` is always set, even on failure.
- After adding the method, update `testPublicMethodCount()` in `tests/VestaCPTest.php` (the count assertion) and add the method name to `publicMethodProvider()` and `apiMethodProvider()`.

## Instructions

1. **Identify the VestaCP command name.** VestaCP CLI commands follow the pattern `v-verb-noun` (e.g., `v-add-user`, `v-delete-user`, `v-suspend-user`). Confirm the exact command string before writing any code.

2. **Determine method type.** 
   - *Mutation* (add/delete/suspend/unsuspend/change): includes `'returncode' => 'yes'`, returns `bool|mixed|string`.
   - *Query* (list/check/get): omits `returncode`, parses JSON response, prints/returns data.

3. **Add the method to `src/VestaCP.php`** inside `class VestaCP`, before the setter methods at the bottom. Use this exact skeleton for **mutation** methods:

```php
/**
 * @param string $argName1
 * @param string $argName2
 */
public function methodName($argName1, $argName2)
{
    $vstReturncode = 'yes';
    $vstCommand = 'v-command-name';

    // Prepare POST query
    $postvars = [
        'user'       => $this->username,
        'password'   => $this->password,
        'returncode' => $vstReturncode,
        'cmd'        => $vstCommand,
        'arg1'       => $argName1,
        'arg2'       => $argName2,
    ];
    $postdata = http_build_query($postvars);

    // Send POST query via cURL
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $this->response = curl_exec($curl);

    // Check result
    if ($this->response == '0' || $this->response == 0) {
        echo "Operation completed successfully\n";
    } else {
        echo 'Query returned error code: '.$this->response.PHP_EOL;
    }
}
```

For **query** methods, omit `$vstReturncode` and `'returncode'` from `$postvars`, and replace the result-check block with:

```php
    // Parse JSON output
    $data = json_decode($this->response, true);

    // Print result
    print_r($data);
```

4. **Map arguments to `arg1`, `arg2`, … argN** in the order the VestaCP CLI command expects them. There is no other mapping layer.

5. **Verify the cURL block is identical** to existing methods — no extra options, no curl_close(), no timeout setting.

6. **Update `tests/VestaCPTest.php`:**
   - Add `'methodName' => ['methodName']` to `publicMethodProvider()` (line ~305).
   - For mutation methods also add to `apiMethodProvider()` (line ~676).
   - Increment the count in `testPublicMethodCount()` (e.g., change `assertCount(14, ...)` to `assertCount(15, ...)`).
   - Add a signature test following the pattern of `testAddDatabaseSignature()`.

7. **Run tests** to verify nothing broke — run `vendor/bin/phpunit` (configured via `phpunit.xml.dist`). All tests must pass before finishing.

## Examples

**User says:** "Add a method to change a user's password via VestaCP (`v-change-user-password`)"

**Actions taken:**
1. Identified command: `v-change-user-password`, args: `$username`, `$password` — mutation type.
2. Added method `changeUserPassword($username, $password)` to `src/VestaCP.php` before `setHostname()`.
3. Added `'changeUserPassword' => ['changeUserPassword']` to both `publicMethodProvider()` and `apiMethodProvider()` in `tests/VestaCPTest.php`.
4. Incremented `assertCount` from 14 to 15 in `testPublicMethodCount()`.
5. Added `testChangeUserPasswordSignature()` verifying 2 params named `['username', 'password']`.
6. Ran `vendor/bin/phpunit` — all tests passed.

**Result — method body in `src/VestaCP.php`:**
```php
/**
 * @param string $username
 * @param string $password
 */
public function changeUserPassword($username, $password)
{
    $vstReturncode = 'yes';
    $vstCommand = 'v-change-user-password';

    // Prepare POST query
    $postvars = [
        'user'       => $this->username,
        'password'   => $this->password,
        'returncode' => $vstReturncode,
        'cmd'        => $vstCommand,
        'arg1'       => $username,
        'arg2'       => $password,
    ];
    $postdata = http_build_query($postvars);

    // Send POST query via cURL
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://'.$this->hostname.':8083/api/');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $this->response = curl_exec($curl);

    // Check result
    if ($this->response == '0' || $this->response == 0) {
        echo "Password has been successfully changed\n";
    } else {
        echo 'Query returned error code: '.$this->response.PHP_EOL;
    }
}
```

## Common Issues

- **`testPublicMethodCount` fails with "expected 14 got 15"**: You added the method but forgot to update the `assertCount` in `testPublicMethodCount()`. Increment the count by 1 for each method added.

- **`testApiMethodsIncludeCredentials` fails for your new method**: Your `$postvars` array is missing `'user' => $this->username` or `'password' => $this->password`. Both keys are required in every API method.

- **`testApiMethodsDisableSslVerification` fails**: You added `CURLOPT_SSL_VERIFYPEER` or `CURLOPT_SSL_VERIFYHOST` with value `true`, or omitted one of them. Both must be `curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false)` and `curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false)`.

- **`$this->response` is `null` after call (cURL connection failure)**: The VestaCP host is unreachable or the port 8083 is firewalled. `curl_exec` returns `false` (not `null`) on failure — check `curl_error($curl)` for the actual message. The existing code treats `null` as failure (`createAccount` has the null check); other methods do not — they will echo the error code.

- **Response is a non-zero number string (e.g., `"3"`, `"9"`)**: VestaCP returned an error code. Error codes are documented in the VestaCP source (`/usr/local/vesta/func/main.sh`). Code `3` = object already exists; code `9` = object not found.

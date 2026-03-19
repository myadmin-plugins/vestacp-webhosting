<?php

declare(strict_types=1);

namespace Detain\MyAdminVestaCP\Tests;

use Detain\MyAdminVestaCP\VestaCP;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test suite for the VestaCP API client class.
 *
 * Tests cover class structure, constructor behavior, getters/setters,
 * property visibility, and static analysis of methods that interact
 * with external services (cURL calls).
 *
 * @covers \Detain\MyAdminVestaCP\VestaCP
 */
class VestaCPTest extends TestCase
{
    /**
     * @var ReflectionClass<VestaCP>
     */
    private ReflectionClass $reflection;

    /**
     * Set up reflection instance for structural tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reflection = new ReflectionClass(VestaCP::class);
    }

    /**
     * Test that the VestaCP class exists and can be loaded.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(VestaCP::class));
    }

    /**
     * Test that VestaCP resides in the correct namespace.
     *
     * @return void
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\MyAdminVestaCP', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the class is not abstract and not an interface.
     *
     * @return void
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
        $this->assertFalse($this->reflection->isAbstract());
        $this->assertFalse($this->reflection->isInterface());
    }

    /**
     * Test that the constructor accepts three optional string parameters.
     *
     * @return void
     */
    public function testConstructorParameterCount(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(3, $constructor->getParameters());
    }

    /**
     * Test that all constructor parameters are optional with empty string defaults.
     *
     * @return void
     */
    public function testConstructorParametersAreOptional(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        foreach ($constructor->getParameters() as $param) {
            $this->assertTrue($param->isOptional(), "Parameter \${$param->getName()} should be optional");
            $this->assertSame('', $param->getDefaultValue(), "Parameter \${$param->getName()} should default to empty string");
        }
    }

    /**
     * Test that constructor parameter names match expected values.
     *
     * @return void
     */
    public function testConstructorParameterNames(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $names = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertSame(['hostname', 'username', 'password'], $names);
    }

    /**
     * Test default construction with no arguments sets empty properties.
     *
     * @return void
     */
    public function testDefaultConstruction(): void
    {
        $vesta = new VestaCP();
        $this->assertSame('', $vesta->hostname);
        $this->assertSame('', $vesta->username);
        $this->assertSame('', $vesta->password);
        $this->assertSame('', $vesta->response);
    }

    /**
     * Test construction with all three arguments populates properties.
     *
     * @return void
     */
    public function testConstructionWithArguments(): void
    {
        $vesta = new VestaCP('host.example.com', 'admin', 's3cret');
        $this->assertSame('host.example.com', $vesta->hostname);
        $this->assertSame('admin', $vesta->username);
        $this->assertSame('s3cret', $vesta->password);
    }

    /**
     * Test construction with partial arguments.
     *
     * @return void
     */
    public function testConstructionWithPartialArguments(): void
    {
        $vesta = new VestaCP('192.168.1.1');
        $this->assertSame('192.168.1.1', $vesta->hostname);
        $this->assertSame('', $vesta->username);
        $this->assertSame('', $vesta->password);
    }

    /**
     * Test that all four expected public properties exist.
     *
     * @return void
     */
    public function testPublicPropertiesExist(): void
    {
        $expected = ['hostname', 'username', 'password', 'response'];
        foreach ($expected as $prop) {
            $this->assertTrue($this->reflection->hasProperty($prop), "Property \${$prop} should exist");
            $this->assertTrue($this->reflection->getProperty($prop)->isPublic(), "Property \${$prop} should be public");
        }
    }

    /**
     * Test that the property count matches expectations (no hidden properties).
     *
     * @return void
     */
    public function testPropertyCount(): void
    {
        $properties = $this->reflection->getProperties();
        $this->assertCount(4, $properties);
    }

    /**
     * Test that all properties default to string type values.
     *
     * @return void
     */
    public function testPropertyDefaultValues(): void
    {
        $vesta = new VestaCP();
        $this->assertIsString($vesta->hostname);
        $this->assertIsString($vesta->username);
        $this->assertIsString($vesta->password);
        $this->assertIsString($vesta->response);
    }

    /**
     * Test that setHostname correctly updates the hostname property.
     *
     * @return void
     */
    public function testSetHostname(): void
    {
        $vesta = new VestaCP();
        $vesta->setHostname('newhost.example.com');
        $this->assertSame('newhost.example.com', $vesta->hostname);
    }

    /**
     * Test that setUsername correctly updates the username property.
     *
     * @return void
     */
    public function testSetUsername(): void
    {
        $vesta = new VestaCP();
        $vesta->setUsername('newadmin');
        $this->assertSame('newadmin', $vesta->username);
    }

    /**
     * Test that setPassword correctly updates the password property.
     *
     * @return void
     */
    public function testSetPassword(): void
    {
        $vesta = new VestaCP();
        $vesta->setPassword('newpass');
        $this->assertSame('newpass', $vesta->password);
    }

    /**
     * Test that getResponse returns the response property value.
     *
     * @return void
     */
    public function testGetResponse(): void
    {
        $vesta = new VestaCP();
        $this->assertSame('', $vesta->getResponse());
    }

    /**
     * Test that getResponse reflects direct property modifications.
     *
     * @return void
     */
    public function testGetResponseAfterDirectAssignment(): void
    {
        $vesta = new VestaCP();
        $vesta->response = 'test-response-data';
        $this->assertSame('test-response-data', $vesta->getResponse());
    }

    /**
     * Test setter chaining: setters should modify properties in sequence.
     *
     * @return void
     */
    public function testSettersModifyPropertiesSequentially(): void
    {
        $vesta = new VestaCP();
        $vesta->setHostname('host1.com');
        $vesta->setUsername('user1');
        $vesta->setPassword('pass1');
        $this->assertSame('host1.com', $vesta->hostname);
        $this->assertSame('user1', $vesta->username);
        $this->assertSame('pass1', $vesta->password);
    }

    /**
     * Test that setters overwrite constructor values.
     *
     * @return void
     */
    public function testSettersOverwriteConstructorValues(): void
    {
        $vesta = new VestaCP('original.host', 'origuser', 'origpass');
        $vesta->setHostname('new.host');
        $vesta->setUsername('newuser');
        $vesta->setPassword('newpass');
        $this->assertSame('new.host', $vesta->hostname);
        $this->assertSame('newuser', $vesta->username);
        $this->assertSame('newpass', $vesta->password);
    }

    /**
     * Test that all expected public methods exist on the class.
     *
     * @return void
     * @dataProvider publicMethodProvider
     */
    public function testPublicMethodExists(string $methodName): void
    {
        $this->assertTrue(
            $this->reflection->hasMethod($methodName),
            "Method {$methodName} should exist"
        );
        $this->assertTrue(
            $this->reflection->getMethod($methodName)->isPublic(),
            "Method {$methodName} should be public"
        );
    }

    /**
     * Provides the expected public method names.
     *
     * @return array<string, array{string}>
     */
    public function publicMethodProvider(): array
    {
        return [
            'createAccount' => ['createAccount'],
            'addWebDnsMailDomain' => ['addWebDnsMailDomain'],
            'addDatabase' => ['addDatabase'],
            'listAccount' => ['listAccount'],
            'listWebDomains' => ['listWebDomains'],
            'deleteAccount' => ['deleteAccount'],
            'suspendAccount' => ['suspendAccount'],
            'unsuspendAccount' => ['unsuspendAccount'],
            'checkUserPass' => ['checkUserPass'],
            'setHostname' => ['setHostname'],
            'setPassword' => ['setPassword'],
            'setUsername' => ['setUsername'],
            'getResponse' => ['getResponse'],
        ];
    }

    /**
     * Test that the class has exactly the expected number of own public methods.
     *
     * @return void
     */
    public function testPublicMethodCount(): void
    {
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $ownMethods = array_filter($methods, fn($m) => $m->getDeclaringClass()->getName() === VestaCP::class);
        $this->assertCount(14, $ownMethods); // 13 public + __construct
    }

    /**
     * Test createAccount method parameter signature.
     *
     * @return void
     */
    public function testCreateAccountSignature(): void
    {
        $method = $this->reflection->getMethod('createAccount');
        $params = $method->getParameters();
        $this->assertCount(5, $params);
        $names = array_map(fn($p) => $p->getName(), $params);
        $this->assertSame(['username', 'password', 'email', 'name', 'package'], $names);
        $this->assertFalse($params[0]->isOptional());
        $this->assertFalse($params[1]->isOptional());
        $this->assertFalse($params[2]->isOptional());
        $this->assertFalse($params[3]->isOptional());
        $this->assertTrue($params[4]->isOptional());
        $this->assertSame('default', $params[4]->getDefaultValue());
    }

    /**
     * Test addWebDnsMailDomain method parameter signature.
     *
     * @return void
     */
    public function testAddWebDnsMailDomainSignature(): void
    {
        $method = $this->reflection->getMethod('addWebDnsMailDomain');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $names = array_map(fn($p) => $p->getName(), $params);
        $this->assertSame(['username', 'domain'], $names);
    }

    /**
     * Test addDatabase method parameter signature.
     *
     * @return void
     */
    public function testAddDatabaseSignature(): void
    {
        $method = $this->reflection->getMethod('addDatabase');
        $params = $method->getParameters();
        $this->assertCount(4, $params);
        $names = array_map(fn($p) => $p->getName(), $params);
        $this->assertSame(['username', 'dbName', 'dbUser', 'dbPass'], $names);
    }

    /**
     * Test listAccount method parameter signature.
     *
     * @return void
     */
    public function testListAccountSignature(): void
    {
        $method = $this->reflection->getMethod('listAccount');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('username', $params[0]->getName());
        $this->assertSame('format', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertSame('json', $params[1]->getDefaultValue());
    }

    /**
     * Test listWebDomains method parameter signature.
     *
     * @return void
     */
    public function testListWebDomainsSignature(): void
    {
        $method = $this->reflection->getMethod('listWebDomains');
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $names = array_map(fn($p) => $p->getName(), $params);
        $this->assertSame(['username', 'domain', 'format'], $names);
        $this->assertTrue($params[2]->isOptional());
        $this->assertSame('json', $params[2]->getDefaultValue());
    }

    /**
     * Test deleteAccount method parameter signature.
     *
     * @return void
     */
    public function testDeleteAccountSignature(): void
    {
        $method = $this->reflection->getMethod('deleteAccount');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('username', $params[0]->getName());
    }

    /**
     * Test suspendAccount method parameter signature.
     *
     * @return void
     */
    public function testSuspendAccountSignature(): void
    {
        $method = $this->reflection->getMethod('suspendAccount');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('username', $params[0]->getName());
    }

    /**
     * Test unsuspendAccount method parameter signature.
     *
     * @return void
     */
    public function testUnsuspendAccountSignature(): void
    {
        $method = $this->reflection->getMethod('unsuspendAccount');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('username', $params[0]->getName());
    }

    /**
     * Test checkUserPass method parameter signature.
     *
     * @return void
     */
    public function testCheckUserPassSignature(): void
    {
        $method = $this->reflection->getMethod('checkUserPass');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $names = array_map(fn($p) => $p->getName(), $params);
        $this->assertSame(['username', 'password'], $names);
    }

    /**
     * Test that setter methods each accept exactly one parameter.
     *
     * @return void
     * @dataProvider setterMethodProvider
     */
    public function testSetterMethodSignatures(string $method, string $paramName): void
    {
        $ref = $this->reflection->getMethod($method);
        $params = $ref->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame($paramName, $params[0]->getName());
    }

    /**
     * Provides setter method names and their expected parameter names.
     *
     * @return array<string, array{string, string}>
     */
    public function setterMethodProvider(): array
    {
        return [
            'setHostname' => ['setHostname', 'hostname'],
            'setUsername' => ['setUsername', 'username'],
            'setPassword' => ['setPassword', 'password'],
        ];
    }

    /**
     * Test that getResponse has no parameters.
     *
     * @return void
     */
    public function testGetResponseSignature(): void
    {
        $method = $this->reflection->getMethod('getResponse');
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Test that none of the methods are static.
     *
     * @return void
     */
    public function testNoMethodsAreStatic(): void
    {
        $ownMethods = array_filter(
            $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn($m) => $m->getDeclaringClass()->getName() === VestaCP::class
        );
        foreach ($ownMethods as $method) {
            $this->assertFalse(
                $method->isStatic(),
                "Method {$method->getName()} should not be static"
            );
        }
    }

    /**
     * Test that the class does not extend any parent class.
     *
     * @return void
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Test that the class does not implement any interfaces.
     *
     * @return void
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->reflection->getInterfaceNames());
    }

    /**
     * Test that API methods build the correct API URL using hostname and port 8083.
     *
     * @return void
     */
    public function testApiUrlPattern(): void
    {
        $method = $this->reflection->getMethod('createAccount');
        $source = $this->getMethodSource($method);
        $this->assertStringContainsString("'https://'.\$this->hostname.':8083/api/'", $source);
    }

    /**
     * Test that createAccount uses the v-add-user command.
     *
     * @return void
     */
    public function testCreateAccountUsesCorrectVestaCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('createAccount'));
        $this->assertStringContainsString("'v-add-user'", $source);
    }

    /**
     * Test that addWebDnsMailDomain uses the v-add-domain command.
     *
     * @return void
     */
    public function testAddWebDnsMailDomainUsesCorrectCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('addWebDnsMailDomain'));
        $this->assertStringContainsString("'v-add-domain'", $source);
    }

    /**
     * Test that addDatabase uses the v-add-database command.
     *
     * @return void
     */
    public function testAddDatabaseUsesCorrectCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('addDatabase'));
        $this->assertStringContainsString("'v-add-database'", $source);
    }

    /**
     * Test that deleteAccount uses the v-delete-user command.
     *
     * @return void
     */
    public function testDeleteAccountUsesCorrectCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('deleteAccount'));
        $this->assertStringContainsString("'v-delete-user'", $source);
    }

    /**
     * Test that suspendAccount uses the v-suspend-user command.
     *
     * @return void
     */
    public function testSuspendAccountUsesCorrectCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('suspendAccount'));
        $this->assertStringContainsString("'v-suspend-user'", $source);
    }

    /**
     * Test that unsuspendAccount uses the v-unsuspend-user command.
     *
     * @return void
     */
    public function testUnsuspendAccountUsesCorrectCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('unsuspendAccount'));
        $this->assertStringContainsString("'v-unsuspend-user'", $source);
    }

    /**
     * Test that checkUserPass uses the v-check-user-password command.
     *
     * @return void
     */
    public function testCheckUserPassUsesCorrectCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('checkUserPass'));
        $this->assertStringContainsString("'v-check-user-password'", $source);
    }

    /**
     * Test that listAccount uses the v-list_user command.
     *
     * @return void
     */
    public function testListAccountUsesCorrectCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('listAccount'));
        $this->assertStringContainsString("'v-list_user'", $source);
    }

    /**
     * Test that listWebDomains uses the v-list-web-domain command.
     *
     * @return void
     */
    public function testListWebDomainsUsesCorrectCommand(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('listWebDomains'));
        $this->assertStringContainsString("'v-list-web-domain'", $source);
    }

    /**
     * Test that all API methods include authentication credentials in POST data.
     *
     * @return void
     * @dataProvider apiMethodProvider
     */
    public function testApiMethodsIncludeCredentials(string $methodName): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod($methodName));
        $this->assertStringContainsString("'user' => \$this->username", $source);
        $this->assertStringContainsString("'password' => \$this->password", $source);
    }

    /**
     * Provides method names that make API calls.
     *
     * @return array<string, array{string}>
     */
    public function apiMethodProvider(): array
    {
        return [
            'createAccount' => ['createAccount'],
            'addWebDnsMailDomain' => ['addWebDnsMailDomain'],
            'addDatabase' => ['addDatabase'],
            'listAccount' => ['listAccount'],
            'listWebDomains' => ['listWebDomains'],
            'deleteAccount' => ['deleteAccount'],
            'suspendAccount' => ['suspendAccount'],
            'unsuspendAccount' => ['unsuspendAccount'],
            'checkUserPass' => ['checkUserPass'],
        ];
    }

    /**
     * Test that all API methods use cURL with SSL verification disabled.
     *
     * @return void
     * @dataProvider apiMethodProvider
     */
    public function testApiMethodsDisableSslVerification(string $methodName): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod($methodName));
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYPEER', $source);
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYHOST', $source);
    }

    /**
     * Test that all API methods store the response in $this->response.
     *
     * @return void
     * @dataProvider apiMethodProvider
     */
    public function testApiMethodsStoreResponse(string $methodName): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod($methodName));
        $this->assertStringContainsString('$this->response = curl_exec($curl)', $source);
    }

    /**
     * Test that all API methods use POST requests.
     *
     * @return void
     * @dataProvider apiMethodProvider
     */
    public function testApiMethodsUsePost(string $methodName): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod($methodName));
        $this->assertStringContainsString('CURLOPT_POST', $source);
        $this->assertStringContainsString('CURLOPT_POSTFIELDS', $source);
    }

    /**
     * Test that all API methods connect to port 8083.
     *
     * @return void
     * @dataProvider apiMethodProvider
     */
    public function testApiMethodsUsePort8083(string $methodName): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod($methodName));
        $this->assertStringContainsString(':8083/api/', $source);
    }

    /**
     * Test that all API methods use http_build_query for POST data.
     *
     * @return void
     * @dataProvider apiMethodProvider
     */
    public function testApiMethodsUseHttpBuildQuery(string $methodName): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod($methodName));
        $this->assertStringContainsString('http_build_query', $source);
    }

    /**
     * Test that createAccount splits name into first and last name using mb_* functions.
     *
     * @return void
     */
    public function testCreateAccountUsesMultibyteNameSplitting(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('createAccount'));
        $this->assertStringContainsString('mb_substr', $source);
        $this->assertStringContainsString('mb_strpos', $source);
    }

    /**
     * Test that createAccount includes returncode in its POST data.
     *
     * @return void
     */
    public function testCreateAccountIncludesReturncode(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('createAccount'));
        $this->assertStringContainsString("'returncode'", $source);
    }

    /**
     * Test that multiple VestaCP instances are independent.
     *
     * @return void
     */
    public function testMultipleInstancesAreIndependent(): void
    {
        $vesta1 = new VestaCP('host1.com', 'user1', 'pass1');
        $vesta2 = new VestaCP('host2.com', 'user2', 'pass2');

        $this->assertSame('host1.com', $vesta1->hostname);
        $this->assertSame('host2.com', $vesta2->hostname);

        $vesta1->setHostname('changed.com');
        $this->assertSame('changed.com', $vesta1->hostname);
        $this->assertSame('host2.com', $vesta2->hostname);
    }

    /**
     * Test that setting empty string values works correctly.
     *
     * @return void
     */
    public function testEmptyStringValues(): void
    {
        $vesta = new VestaCP('host', 'user', 'pass');
        $vesta->setHostname('');
        $vesta->setUsername('');
        $vesta->setPassword('');
        $this->assertSame('', $vesta->hostname);
        $this->assertSame('', $vesta->username);
        $this->assertSame('', $vesta->password);
    }

    /**
     * Test that properties handle special characters.
     *
     * @return void
     */
    public function testSpecialCharactersInProperties(): void
    {
        $vesta = new VestaCP('host.example.com', 'admin@host', 'p@$$w0rd!');
        $this->assertSame('host.example.com', $vesta->hostname);
        $this->assertSame('admin@host', $vesta->username);
        $this->assertSame('p@$$w0rd!', $vesta->password);
    }

    /**
     * Test that properties handle unicode characters.
     *
     * @return void
     */
    public function testUnicodeInProperties(): void
    {
        $vesta = new VestaCP();
        $vesta->setUsername("\xC3\xA9\xC3\xA0\xC3\xBC");
        $this->assertSame("\xC3\xA9\xC3\xA0\xC3\xBC", $vesta->username);
    }

    /**
     * Test that response property is publicly writable and readable.
     *
     * @return void
     */
    public function testResponsePropertyIsReadWrite(): void
    {
        $vesta = new VestaCP();
        $vesta->response = '0';
        $this->assertSame('0', $vesta->response);
        $this->assertSame('0', $vesta->getResponse());
    }

    /**
     * Test that the class has a proper docblock.
     *
     * @return void
     */
    public function testClassHasDocblock(): void
    {
        $docComment = $this->reflection->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('VestaCP', $docComment);
    }

    /**
     * Helper to get method source code as a string.
     *
     * @param ReflectionMethod $method
     * @return string
     */
    private function getMethodSource(ReflectionMethod $method): string
    {
        $filename = $method->getFileName();
        if ($filename === false) {
            return '';
        }
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $lines = file($filename);
        if ($lines === false) {
            return '';
        }
        return implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
    }
}

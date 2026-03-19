<?php

declare(strict_types=1);

namespace Detain\MyAdminVestaCP\Tests;

use Detain\MyAdminVestaCP\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test suite for the Plugin class.
 *
 * Tests cover class structure, static properties, hook registration,
 * and event handler method signatures. Event handlers that depend on
 * external functions and database calls are tested via static analysis.
 *
 * @covers \Detain\MyAdminVestaCP\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
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
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that the Plugin class exists and can be loaded.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Test that Plugin resides in the correct namespace.
     *
     * @return void
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\MyAdminVestaCP', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the class is instantiable.
     *
     * @return void
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
    }

    /**
     * Test that Plugin does not extend any parent class.
     *
     * @return void
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Test that Plugin does not implement any interfaces.
     *
     * @return void
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->reflection->getInterfaceNames());
    }

    /**
     * Test that the constructor exists and takes no parameters.
     *
     * @return void
     */
    public function testConstructorTakesNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that the Plugin can be instantiated without errors.
     *
     * @return void
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the $name static property exists and has the correct value.
     *
     * @return void
     */
    public function testNameProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('name'));
        $prop = $this->reflection->getProperty('name');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('VestaCP Webhosting', Plugin::$name);
    }

    /**
     * Test that the $description static property exists and contains expected content.
     *
     * @return void
     */
    public function testDescriptionProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('description'));
        $prop = $this->reflection->getProperty('description');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertStringContainsString('vestacp.com', Plugin::$description);
    }

    /**
     * Test that the $help static property exists and is an empty string.
     *
     * @return void
     */
    public function testHelpProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('help'));
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Test that the $module static property is set to 'webhosting'.
     *
     * @return void
     */
    public function testModuleProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('module'));
        $this->assertSame('webhosting', Plugin::$module);
    }

    /**
     * Test that the $type static property is set to 'service'.
     *
     * @return void
     */
    public function testTypeProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('type'));
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Test that all static properties are public.
     *
     * @return void
     */
    public function testAllStaticPropertiesArePublic(): void
    {
        $staticProps = array_filter(
            $this->reflection->getProperties(),
            fn($p) => $p->isStatic()
        );
        foreach ($staticProps as $prop) {
            $this->assertTrue(
                $prop->isPublic(),
                "Static property \${$prop->getName()} should be public"
            );
        }
    }

    /**
     * Test that the expected five static properties exist.
     *
     * @return void
     */
    public function testStaticPropertyCount(): void
    {
        $staticProps = array_filter(
            $this->reflection->getProperties(),
            fn($p) => $p->isStatic()
        );
        $this->assertCount(5, $staticProps);
    }

    /**
     * Test that getHooks returns an array.
     *
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks returns the expected event names as keys.
     *
     * @return void
     */
    public function testGetHooksEventNames(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKeys = [
            'webhosting.settings',
            'webhosting.activate',
            'webhosting.reactivate',
            'webhosting.deactivate',
            'webhosting.terminate',
        ];
        $this->assertSame($expectedKeys, array_keys($hooks));
    }

    /**
     * Test that getHooks returns exactly five hooks.
     *
     * @return void
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(5, $hooks);
    }

    /**
     * Test that each hook value is a callable array with the class and method name.
     *
     * @return void
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $this->assertIsArray($handler, "Handler for '{$event}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$event}' should have two elements");
            $this->assertSame(Plugin::class, $handler[0], "Handler class for '{$event}' should be Plugin");
            $this->assertIsString($handler[1], "Handler method for '{$event}' should be a string");
        }
    }

    /**
     * Test that hook callbacks reference methods that exist on the Plugin class.
     *
     * @return void
     */
    public function testGetHooksReferenceExistingMethods(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $this->assertTrue(
                $this->reflection->hasMethod($handler[1]),
                "Method {$handler[1]} referenced by hook '{$event}' should exist on Plugin"
            );
        }
    }

    /**
     * Test that the settings hook maps to getSettings.
     *
     * @return void
     */
    public function testSettingsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getSettings', $hooks['webhosting.settings'][1]);
    }

    /**
     * Test that the activate hook maps to getActivate.
     *
     * @return void
     */
    public function testActivateHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getActivate', $hooks['webhosting.activate'][1]);
    }

    /**
     * Test that the reactivate hook maps to getReactivate.
     *
     * @return void
     */
    public function testReactivateHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getReactivate', $hooks['webhosting.reactivate'][1]);
    }

    /**
     * Test that the deactivate hook maps to getDeactivate.
     *
     * @return void
     */
    public function testDeactivateHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getDeactivate', $hooks['webhosting.deactivate'][1]);
    }

    /**
     * Test that the terminate hook maps to getTerminate.
     *
     * @return void
     */
    public function testTerminateHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getTerminate', $hooks['webhosting.terminate'][1]);
    }

    /**
     * Test that hook event keys all start with the module name.
     *
     * @return void
     */
    public function testHookKeysStartWithModuleName(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $key) {
            $this->assertStringStartsWith(
                Plugin::$module . '.',
                $key,
                "Hook key '{$key}' should start with module name"
            );
        }
    }

    /**
     * Test that getHooks is a static method.
     *
     * @return void
     */
    public function testGetHooksIsStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that all event handler methods are static.
     *
     * @return void
     * @dataProvider eventHandlerMethodProvider
     */
    public function testEventHandlerMethodsAreStatic(string $methodName): void
    {
        $method = $this->reflection->getMethod($methodName);
        $this->assertTrue($method->isStatic(), "Method {$methodName} should be static");
    }

    /**
     * Test that all event handler methods are public.
     *
     * @return void
     * @dataProvider eventHandlerMethodProvider
     */
    public function testEventHandlerMethodsArePublic(string $methodName): void
    {
        $method = $this->reflection->getMethod($methodName);
        $this->assertTrue($method->isPublic(), "Method {$methodName} should be public");
    }

    /**
     * Test that event handler methods accept a GenericEvent parameter.
     *
     * @return void
     * @dataProvider eventHandlerMethodProvider
     */
    public function testEventHandlerMethodsAcceptGenericEvent(string $methodName): void
    {
        $method = $this->reflection->getMethod($methodName);
        $params = $method->getParameters();
        $this->assertCount(1, $params, "Method {$methodName} should accept exactly one parameter");
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type, "Parameter of {$methodName} should be type-hinted");
        $this->assertSame(
            'Symfony\Component\EventDispatcher\GenericEvent',
            $type->getName()
        );
    }

    /**
     * Provides the event handler method names.
     *
     * @return array<string, array{string}>
     */
    public function eventHandlerMethodProvider(): array
    {
        return [
            'getActivate' => ['getActivate'],
            'getReactivate' => ['getReactivate'],
            'getDeactivate' => ['getDeactivate'],
            'getTerminate' => ['getTerminate'],
            'getSettings' => ['getSettings'],
            'getChangeIp' => ['getChangeIp'],
            'getMenu' => ['getMenu'],
            'getRequirements' => ['getRequirements'],
        ];
    }

    /**
     * Test that getActivate references 'WEB_VESTA' service define check.
     *
     * @return void
     */
    public function testGetActivateChecksWebVestaCategory(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getActivate'));
        $this->assertStringContainsString("get_service_define('WEB_VESTA')", $source);
    }

    /**
     * Test that getReactivate references 'WEB_VESTA' service define check.
     *
     * @return void
     */
    public function testGetReactivateChecksWebVestaCategory(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getReactivate'));
        $this->assertStringContainsString("get_service_define('WEB_VESTA')", $source);
    }

    /**
     * Test that getDeactivate references 'WEB_VESTA' service define check.
     *
     * @return void
     */
    public function testGetDeactivateChecksWebVestaCategory(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getDeactivate'));
        $this->assertStringContainsString("get_service_define('WEB_VESTA')", $source);
    }

    /**
     * Test that getTerminate references 'WEB_VESTA' service define check.
     *
     * @return void
     */
    public function testGetTerminateChecksWebVestaCategory(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getTerminate'));
        $this->assertStringContainsString("get_service_define('WEB_VESTA')", $source);
    }

    /**
     * Test that getActivate calls stopPropagation.
     *
     * @return void
     */
    public function testGetActivateStopsPropagation(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getActivate'));
        $this->assertStringContainsString('stopPropagation()', $source);
    }

    /**
     * Test that getReactivate calls stopPropagation.
     *
     * @return void
     */
    public function testGetReactivateStopsPropagation(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getReactivate'));
        $this->assertStringContainsString('stopPropagation()', $source);
    }

    /**
     * Test that getDeactivate calls stopPropagation.
     *
     * @return void
     */
    public function testGetDeactivateStopsPropagation(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getDeactivate'));
        $this->assertStringContainsString('stopPropagation()', $source);
    }

    /**
     * Test that getTerminate calls stopPropagation.
     *
     * @return void
     */
    public function testGetTerminateStopsPropagation(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getTerminate'));
        $this->assertStringContainsString('stopPropagation()', $source);
    }

    /**
     * Test that getActivate creates a VestaCP instance.
     *
     * @return void
     */
    public function testGetActivateCreatesVestaCPInstance(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getActivate'));
        $this->assertStringContainsString('new VestaCP(', $source);
    }

    /**
     * Test that getActivate calls createAccount on VestaCP.
     *
     * @return void
     */
    public function testGetActivateCallsCreateAccount(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getActivate'));
        $this->assertStringContainsString('$vesta->createAccount(', $source);
    }

    /**
     * Test that getReactivate calls unsuspendAccount on VestaCP.
     *
     * @return void
     */
    public function testGetReactivateCallsUnsuspendAccount(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getReactivate'));
        $this->assertStringContainsString('$vesta->unsuspendAccount(', $source);
    }

    /**
     * Test that getDeactivate calls suspendAccount on VestaCP.
     *
     * @return void
     */
    public function testGetDeactivateCallsSuspendAccount(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getDeactivate'));
        $this->assertStringContainsString('$vesta->suspendAccount(', $source);
    }

    /**
     * Test that getTerminate calls deleteAccount on VestaCP.
     *
     * @return void
     */
    public function testGetTerminateCallsDeleteAccount(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getTerminate'));
        $this->assertStringContainsString('$vesta->deleteAccount(', $source);
    }

    /**
     * Test that getActivate uses myadmin_log for logging.
     *
     * @return void
     */
    public function testGetActivateUsesLogging(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getActivate'));
        $this->assertStringContainsString('myadmin_log(', $source);
    }

    /**
     * Test that getActivate handles the success and failure cases.
     *
     * @return void
     */
    public function testGetActivateHandlesSuccessAndFailure(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getActivate'));
        $this->assertStringContainsString("'success'] = true", $source);
        $this->assertStringContainsString("'success'] = false", $source);
    }

    /**
     * Test that getActivate calls website_welcome_email on success.
     *
     * @return void
     */
    public function testGetActivateCallsWelcomeEmail(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getActivate'));
        $this->assertStringContainsString('website_welcome_email(', $source);
    }

    /**
     * Test that getActivate performs a database update on success.
     *
     * @return void
     */
    public function testGetActivatePerformsDatabaseUpdate(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getActivate'));
        $this->assertStringContainsString('$db->query(', $source);
        $this->assertStringContainsString('update ', $source);
    }

    /**
     * Test that getTerminate handles empty username by returning true.
     *
     * @return void
     */
    public function testGetTerminateHandlesEmptyUsername(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getTerminate'));
        $this->assertStringContainsString("trim(\$serviceClass->getUsername()) == ''", $source);
        $this->assertStringContainsString('return true', $source);
    }

    /**
     * Test that getSettings uses setTarget for module and global settings.
     *
     * @return void
     */
    public function testGetSettingsUsesSetTarget(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getSettings'));
        $this->assertStringContainsString("setTarget('module')", $source);
        $this->assertStringContainsString("setTarget('global')", $source);
    }

    /**
     * Test that getSettings configures VestaCP-specific settings.
     *
     * @return void
     */
    public function testGetSettingsConfiguresVestaSettings(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getSettings'));
        $this->assertStringContainsString('NEW_WEBSITE_VESTA_SERVER', $source);
        $this->assertStringContainsString('OUTOFSTOCK_WEBHOSTING_VESTACP', $source);
    }

    /**
     * Test that getRequirements adds page and function requirements.
     *
     * @return void
     */
    public function testGetRequirementsAddsRequirements(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getRequirements'));
        $this->assertStringContainsString('add_page_requirement(', $source);
        $this->assertStringContainsString('add_requirement(', $source);
    }

    /**
     * Test that getMenu adds links for admin users.
     *
     * @return void
     */
    public function testGetMenuAddsAdminLinks(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getMenu'));
        $this->assertStringContainsString('add_link(', $source);
        $this->assertStringContainsString("'admin'", $source);
    }

    /**
     * Test that getChangeIp references editIp method.
     *
     * @return void
     */
    public function testGetChangeIpCallsEditIp(): void
    {
        $source = $this->getMethodSource($this->reflection->getMethod('getChangeIp'));
        $this->assertStringContainsString('editIp(', $source);
    }

    /**
     * Test that all methods that exist on the class are accounted for.
     *
     * @return void
     */
    public function testAllOwnMethodsAreKnown(): void
    {
        $expectedMethods = [
            '__construct',
            'getHooks',
            'getActivate',
            'getReactivate',
            'getDeactivate',
            'getTerminate',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        $ownMethods = array_filter(
            $this->reflection->getMethods(),
            fn($m) => $m->getDeclaringClass()->getName() === Plugin::class
        );
        $ownMethodNames = array_map(fn($m) => $m->getName(), $ownMethods);
        sort($ownMethodNames);
        sort($expectedMethods);

        $this->assertSame($expectedMethods, $ownMethodNames);
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
        $this->assertStringContainsString('Plugin', $docComment);
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

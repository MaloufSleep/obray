<?php

namespace tests\Object;

use App\controllers\cPermissionController;
use App\controllers\RouteNotFoundHandler;
use OUsers;
use tests\TestCase;

/**
 * @covers OObject::checkPermissions
 * @covers OObject::checkPermissionOrRole
 */
class PermissionsTest extends TestCase
{
    protected array $defaultForbiddenErrors = [
        'Forbidden' => [
            'You cannot access this resource.',
        ],
    ];

    public function testFetchObjectNoAuth()
    {
        $this->unauthenticate();
        $response = $this->route('PermissionController');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testFetchObjectAuth()
    {
        $this->authenticate();
        $response = $this->route('PermissionController');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testPublicMethodNoAuth()
    {
        $this->unauthenticate();
        $response = $this->route('PermissionController/public');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testPublicMethodWithAuth()
    {
        $this->authenticate();
        $response = $this->route('PermissionController/public');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testUnlistedMethodNoAuth()
    {
        $this->unauthenticate();
        $response = $this->route('PermissionController/noPermissionsListed');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testUnlistedMethodAuth()
    {
        $this->authenticate();
        $response = $this->route('PermissionController/noPermissionsListed');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testUserPermissionNoAuth()
    {
        $this->unauthenticate();
        $response = $this->route('PermissionController/user');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testUserPermissionAuth()
    {
        $this->authenticate();
        $response = $this->route('PermissionController/user');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    /**
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUserPermissionBasicAuth()
    {
        require_once __DIR__ . '/../../test_files/models/OUsers.php';
        $_SERVER['PHP_AUTH_USER'] = '';
        $_SERVER['PHP_AUTH_PW'] = '';

        $response = $this->route('PermissionController/user');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    /**
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUserPermissionBasicAuthThatFails()
    {
        require_once __DIR__ . '/../../test_files/models/OUsers.php';
        OUsers::$shouldAuthenticate = false;
        $_SERVER['PHP_AUTH_USER'] = '';
        $_SERVER['PHP_AUTH_PW'] = '';

        $response = $this->route('PermissionController/user');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testNonGraduatedPermissionsNoAuth()
    {
        $this->unauthenticate();
        $response = $this->route('PermissionController/nonGraduated');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testNonGraduatedPermissionsAuth()
    {
        $this->authenticate();
        $response = $this->route('PermissionController/nonGraduated');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNonGraduatedPermissionsWrongAuth()
    {
        $this->authenticate();
        $_SESSION['ouser']->ouser_permission_level = 2;
        $response = $this->route('PermissionController/nonGraduated');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testNullPermissionLevel()
    {
        $this->authenticate();
        $_SESSION['ouser']->ouser_permission_level = null;
        $response = $this->route('PermissionController/nonGraduated');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testPermissionsNoAuth()
    {
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testPermissionsAuthNoPermissions()
    {
        $this->authenticate();
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testPermissionsAuthPermissions()
    {
        $this->authenticate();
        $_SESSION['ouser']->permissions = ['permission_access'];
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testPermissionsAuthWrongPermissions()
    {
        $this->authenticate();
        $_SESSION['ouser']->permissions = ['different_access'];
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testRolesNoAuth()
    {
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testRolesAuthNoPermissions()
    {
        $this->authenticate();
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testRolesAuthPermissions()
    {
        $this->authenticate();
        $_SESSION['ouser']->roles = ['role_access'];
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testRolesAuthWrongPermissions()
    {
        $this->authenticate();
        $_SESSION['ouser']->roles = ['different_access'];
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testPermissionAndRoleOfSameNameDeniesAccess()
    {
        $this->authenticate();
        $_SESSION['ouser']->permissions = ['role_access'];
        $_SESSION['ouser']->roles = ['permission_access'];
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testSuperRole()
    {
        $this->authenticate();
        $_SESSION['ouser']->roles = ['SUPER'];
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);

        // Test case-sensitivity
        $_SESSION['ouser']->roles = ['super'];
        $response = $this->route('PermissionController/permissionsAndRoles');

        $this->assertError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testMissingRouteNoAuth()
    {
        $this->router->setMissingPathHandler(
            RouteNotFoundHandler::class,
            __DIR__ . '/../../test_files/app/controllers/RouteNotFoundHandler.php'
        );
        $response = $this->route('ControllerNotFound');

        $this->assertNotError($response);
        $this->assertInstanceOf(RouteNotFoundHandler::class, $response);

        $this->assertObjectHasAttribute('data', $response);
        $this->assertSame('Success', $response->data);
    }
}

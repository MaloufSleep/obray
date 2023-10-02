<?php

namespace tests\Object;

use App\controllers\cPermissionController;
use tests\TestCase;

/**
 * @covers OObject::checkPermissions
 * @covers OObject::checkPermissionOrRole
 */
class GraduatedPermissionsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        define('__OBRAY_GRADUATED_PERMISSIONS__', true);
    }

    protected array $defaultForbiddenErrors = [
        'Forbidden' => [
            'You cannot access this resource.',
        ],
    ];

    public function testGraduatedPermissionNoAuth()
    {
        $this->unauthenticate();
        $response = $this->route('PermissionController/graduated');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testGraduatedPermissionAuth()
    {
        $this->authenticate();
        $response = $this->route('PermissionController/graduated');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testGraduatedPermissionGreaterAuth()
    {
        $this->authenticate();
        $_SESSION['ouser']->ouser_permission_level = 2;
        $response = $this->route('PermissionController/graduated');

        $this->assertNotError($response);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }

    public function testGraduatedPermissionWrongAuth()
    {
        $this->authenticate();
        $_SESSION['ouser']->ouser_permission_level = 0;
        $response = $this->route('PermissionController/graduated');

        $this->assertError($response, $this->defaultForbiddenErrors);
        $this->assertInstanceOf(cPermissionController::class, $response);
    }
}

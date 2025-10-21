<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\GroupController;
use App\Controller\PermissionController;
use App\Controller\ProjectController;
use App\Controller\RoleController;
use App\Controller\TaskController;
use App\Controller\UserController;
use App\Middleware\AuthMiddleware;
use App\Routing\Router;

return static function (
    Router $router,
    AuthController $authController,
    UserController $userController,
    RoleController $roleController,
    PermissionController $permissionController,
    GroupController $groupController,
    ProjectController $projectController,
    TaskController $taskController,
    AuthMiddleware $authMiddleware
): void {
    $router->post('/api/v1/auth/register', [$authController, 'register']);
    $router->post('/api/v1/auth/login', [$authController, 'login']);
    $router->post('/api/v1/auth/logout', [$authController, 'logout'], [$authMiddleware]);

    $router->get('/api/v1/users', [$userController, 'index'], [$authMiddleware]);
    $router->post('/api/v1/users', [$userController, 'store'], [$authMiddleware]);
    $router->get('/api/v1/users/{id}', [$userController, 'show'], [$authMiddleware]);
    $router->put('/api/v1/users/{id}', [$userController, 'update'], [$authMiddleware]);
    $router->delete('/api/v1/users/{id}', [$userController, 'destroy'], [$authMiddleware]);
    $router->get('/api/v1/users/by-email/{email}', [$userController, 'findByEmail'], [$authMiddleware]);
    $router->get('/api/v1/users/by-username/{username}', [$userController, 'findByUsername'], [$authMiddleware]);

    $router->get('/api/v1/roles', [$roleController, 'index'], [$authMiddleware]);
    $router->post('/api/v1/roles', [$roleController, 'store'], [$authMiddleware]);
    $router->get('/api/v1/roles/{id}', [$roleController, 'show'], [$authMiddleware]);
    $router->put('/api/v1/roles/{id}', [$roleController, 'update'], [$authMiddleware]);
    $router->delete('/api/v1/roles/{id}', [$roleController, 'destroy'], [$authMiddleware]);
    $router->post('/api/v1/roles/{id}/permissions', [$roleController, 'syncPermissions'], [$authMiddleware]);
    $router->post('/api/v1/roles/{id}/assign', [$roleController, 'assign'], [$authMiddleware]);
    $router->post('/api/v1/roles/{id}/revoke', [$roleController, 'revoke'], [$authMiddleware]);

    $router->get('/api/v1/permissions', [$permissionController, 'index'], [$authMiddleware]);
    $router->post('/api/v1/permissions', [$permissionController, 'store'], [$authMiddleware]);
    $router->get('/api/v1/permissions/{id}', [$permissionController, 'show'], [$authMiddleware]);
    $router->put('/api/v1/permissions/{id}', [$permissionController, 'update'], [$authMiddleware]);
    $router->delete('/api/v1/permissions/{id}', [$permissionController, 'destroy'], [$authMiddleware]);

    $router->get('/api/v1/groups', [$groupController, 'index'], [$authMiddleware]);
    $router->post('/api/v1/groups', [$groupController, 'store'], [$authMiddleware]);
    $router->get('/api/v1/groups/{id}', [$groupController, 'show'], [$authMiddleware]);
    $router->put('/api/v1/groups/{id}', [$groupController, 'update'], [$authMiddleware]);
    $router->delete('/api/v1/groups/{id}', [$groupController, 'destroy'], [$authMiddleware]);
    $router->get('/api/v1/groups/{id}/members', [$groupController, 'members'], [$authMiddleware]);
    $router->post('/api/v1/groups/{id}/members', [$groupController, 'addMember'], [$authMiddleware]);
    $router->delete('/api/v1/groups/{id}/members/{userId}', [$groupController, 'removeMember'], [$authMiddleware]);

    $router->get('/api/v1/projects', [$projectController, 'index'], [$authMiddleware]);
    $router->post('/api/v1/projects', [$projectController, 'store'], [$authMiddleware]);
    $router->get('/api/v1/projects/{id}', [$projectController, 'show'], [$authMiddleware]);
    $router->put('/api/v1/projects/{id}', [$projectController, 'update'], [$authMiddleware]);
    $router->delete('/api/v1/projects/{id}', [$projectController, 'destroy'], [$authMiddleware]);
    $router->post('/api/v1/projects/{id}/members', [$projectController, 'addMember'], [$authMiddleware]);
    $router->delete('/api/v1/projects/{id}/members/{userId}', [$projectController, 'removeMember'], [$authMiddleware]);
    $router->get('/api/v1/projects/{id}/timeline', [$projectController, 'timeline'], [$authMiddleware]);
    $router->post('/api/v1/projects/{id}/timeline', [$projectController, 'addTimelineEvent'], [$authMiddleware]);

    $router->get('/api/v1/projects/{projectId}/tasks', [$taskController, 'list'], [$authMiddleware]);
    $router->post('/api/v1/projects/{projectId}/tasks', [$taskController, 'store'], [$authMiddleware]);
    $router->get('/api/v1/tasks/{id}', [$taskController, 'show'], [$authMiddleware]);
    $router->put('/api/v1/tasks/{id}', [$taskController, 'update'], [$authMiddleware]);
    $router->delete('/api/v1/tasks/{id}', [$taskController, 'destroy'], [$authMiddleware]);
};

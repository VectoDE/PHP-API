<?php

declare(strict_types=1);

namespace App;

use App\Bootstrap\EnvironmentBootstrap;
use App\Config\Config;
use App\Controller\AuthController;
use App\Controller\GroupController;
use App\Controller\PermissionController;
use App\Controller\ProjectController;
use App\Controller\RoleController;
use App\Controller\TaskController;
use App\Controller\UserController;
use App\Database\ConnectionFactory;
use App\Database\Migrations\GroupTablesMigration;
use App\Database\Migrations\ProjectManagementMigration;
use App\Database\Migrations\RbacTablesMigration;
use App\Database\Migrations\UserTableMigration;
use App\Http\Exceptions\HttpException;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Logging\Logger;
use App\Logging\LoggerFactory;
use App\Middleware\AuthMiddleware;
use App\Repository\GroupRepository;
use App\Repository\PermissionRepository;
use App\Repository\ProjectRepository;
use App\Repository\RoleRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Routing\Router;
use App\Security\Jwt\JwtManager;
use App\Security\PasswordHasher;
use App\Service\AuthService;
use App\Service\GroupService;
use App\Service\PermissionService;
use App\Service\ProjectService;
use App\Service\RoleService;
use App\Service\TaskService;
use App\Service\UserService;
use PDO;
use Throwable;

final class Application
{
    private Config $config;
    private Router $router;
    private Logger $logger;

    public function __construct(private readonly string $basePath)
    {
        EnvironmentBootstrap::init($this->basePath);
        $this->config = new Config($this->basePath);
        $this->logger = LoggerFactory::create($this->basePath);
        $pdo = $this->createDatabaseConnection();
        $this->bootstrapRouter($pdo);
    }

    public function run(): void
    {
        try {
            $request = Request::fromGlobals();
            $response = $this->router->dispatch($request);
        } catch (HttpException $exception) {
            $response = JsonResponse::fromArray([
                'error' => $exception->getMessage(),
                'details' => $exception->getPayload(),
            ], $exception->getStatusCode());
        } catch (Throwable $exception) {
            $this->logger->error('Unhandled exception', [
                'exception' => $exception,
            ]);

            $response = JsonResponse::fromArray([
                'error' => 'Internal server error.',
            ], 500);
        }

        $response->send();
    }

    private function createDatabaseConnection(): PDO
    {
        $pdo = ConnectionFactory::make($this->config);
        UserTableMigration::migrate($pdo);
        RbacTablesMigration::migrate($pdo);
        GroupTablesMigration::migrate($pdo);
        ProjectManagementMigration::migrate($pdo);

        return $pdo;
    }

    private function bootstrapRouter(PDO $pdo): void
    {
        $passwordHasher = new PasswordHasher();
        $userRepository = new UserRepository($pdo);
        $roleRepository = new RoleRepository($pdo);
        $permissionRepository = new PermissionRepository($pdo);
        $groupRepository = new GroupRepository($pdo);
        $projectRepository = new ProjectRepository($pdo);
        $taskRepository = new TaskRepository($pdo);
        $jwtManager = new JwtManager(
            $this->config->get('jwt.secret'),
            $this->config->get('jwt.ttl'),
            $this->config->get('jwt.issuer')
        );

        $authService = new AuthService($userRepository, $roleRepository, $passwordHasher, $jwtManager);
        $userService = new UserService($userRepository, $passwordHasher);
        $permissionService = new PermissionService($permissionRepository);
        $roleService = new RoleService($roleRepository, $permissionRepository, $userRepository);
        $groupService = new GroupService($groupRepository, $userRepository);
        $projectService = new ProjectService($projectRepository, $groupRepository, $userRepository, $taskRepository);
        $taskService = new TaskService($taskRepository, $projectRepository, $userRepository);

        $authController = new AuthController($authService);
        $userController = new UserController($userService);
        $permissionController = new PermissionController($permissionService);
        $roleController = new RoleController($roleService);
        $groupController = new GroupController($groupService);
        $projectController = new ProjectController($projectService);
        $taskController = new TaskController($taskService);
        $authMiddleware = new AuthMiddleware($jwtManager);

        $router = new Router();

        $this->registerRoutes(
            $router,
            $authController,
            $userController,
            $roleController,
            $permissionController,
            $groupController,
            $projectController,
            $taskController,
            $authMiddleware
        );

        $this->router = $router;
    }

    private function registerRoutes(
        Router $router,
        AuthController $authController,
        UserController $userController,
        RoleController $roleController,
        PermissionController $permissionController,
        GroupController $groupController,
        ProjectController $projectController,
        TaskController $taskController,
        AuthMiddleware $authMiddleware
    ): void
    {
        $routes = require $this->basePath . '/routes/api.php';
        $routes(
            $router,
            $authController,
            $userController,
            $roleController,
            $permissionController,
            $groupController,
            $projectController,
            $taskController,
            $authMiddleware
        );
    }
}

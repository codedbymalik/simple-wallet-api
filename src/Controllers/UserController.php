<?php

namespace Controllers;

use Core\BaseController;
use Services\UserService;
use Exception;

class UserController extends BaseController
{
    private UserService $userService;

    public function __construct(?UserService $userService = null)
    {
        $this->userService = $userService ?? new UserService();
    }

    public function create(): void
    {
        try {
            $payload = $this->getJsonPayload();

            if (empty($payload['name']) || empty($payload['email'])) {
                $this->sendError('Name and email are required', 400);
            }

            $user = $this->userService->createUser($payload);
            $this->sendSuccess($user, 'User created successfully', 201);
        } catch (Exception $e) {
            error_log("UserController create error: " . $e->getMessage());
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getById(int $id): void
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                $this->sendError('User not found', 404);
            }

            $this->sendSuccess($user, 'User retrieved successfully');
        } catch (Exception $e) {
            error_log("UserController getById error: " . $e->getMessage());
            $this->sendError($e->getMessage(), 500);
        }
    }

    public function getAll(): void
    {
        try {
            $users = $this->userService->getAllUsers();
            $this->sendSuccess($users, 'Users retrieved successfully');
        } catch (Exception $e) {
            error_log("UserController getAll error: " . $e->getMessage());
            $this->sendError($e->getMessage(), 500);
        }
    }

    public function update(int $id): void
    {
        try {
            $payload = $this->getJsonPayload();

            $user = $this->userService->updateUser($id, $payload);
            $this->sendSuccess($user, 'User updated successfully');
        } catch (Exception $e) {
            error_log("UserController update error: " . $e->getMessage());
            $this->sendError($e->getMessage(), $e->getMessage() === 'User not found' ? 404 : 400);
        }
    }

    public function delete(int $id): void
    {
        try {
            $deleted = $this->userService->deleteUser($id);

            if (!$deleted) {
                $this->sendError('User not found', 404);
            }

            $this->sendSuccess([], 'User deleted successfully');
        } catch (Exception $e) {
            error_log("UserController delete error: " . $e->getMessage());
            $this->sendError($e->getMessage(), $e->getMessage() === 'User not found' ? 404 : 500);
        }
    }
}

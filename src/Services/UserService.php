<?php

namespace Services;

use Interfaces\UserServiceInterface;
use Models\UserModel;
use Exception;

class UserService implements UserServiceInterface
{
    private UserModel $userModel;

    public function __construct(?UserModel $userModel = null)
    {
        $this->userModel = $userModel ?? new UserModel();
    }

    public function createUser(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['email'])) {
                throw new Exception('Name and email are required');
            }

            // Check if email already exists
            if ($this->userModel->getUserByEmail($data['email'])) {
                throw new Exception('Email already exists');
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }

            $userId = $this->userModel->createUser($data);
            $user = $this->userModel->getUserById($userId);

            return $user ?? [];
        } catch (Exception $e) {
            error_log("UserService createUser error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getUserById(int $id): ?array
    {
        try {
            return $this->userModel->getUserById($id);
        } catch (Exception $e) {
            error_log("UserService getUserById error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllUsers(): array
    {
        try {
            return $this->userModel->getAllUsers();
        } catch (Exception $e) {
            error_log("UserService getAllUsers error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateUser(int $id, array $data): array
    {
        try {
            // Check if user exists
            $user = $this->userModel->getUserById($id);
            if (!$user) {
                throw new Exception('User not found');
            }

            $this->userModel->updateUser($id, $data);
            return $this->userModel->getUserById($id) ?? [];
        } catch (Exception $e) {
            error_log("UserService updateUser error: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteUser(int $id): bool
    {
        try {
            // Check if user exists
            $user = $this->userModel->getUserById($id);
            if (!$user) {
                throw new Exception('User not found');
            }

            return $this->userModel->deleteUser($id) > 0;
        } catch (Exception $e) {
            error_log("UserService deleteUser error: " . $e->getMessage());
            throw $e;
        }
    }
}

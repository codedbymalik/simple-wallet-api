<?php

namespace Services;

use Interfaces\AccountServiceInterface;
use Models\AccountModel;
use Models\UserModel;
use Exception;

class AccountService implements AccountServiceInterface
{
    private AccountModel $accountModel;
    private UserModel $userModel;

    public function __construct(?AccountModel $accountModel = null, ?UserModel $userModel = null)
    {
        $this->accountModel = $accountModel ?? new AccountModel();
        $this->userModel = $userModel ?? new UserModel();
    }

    public function createAccount(int $userId, array $data): array
    {
        try {
            // Validate user exists
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Validate balance
            if (!isset($data['balance']) || $data['balance'] < 0) {
                throw new Exception('Balance cannot be negative');
            }

            // Validate account number
            if (empty($data['account_number'])) {
                throw new Exception('Account number is required');
            }

            // Check if account number already exists
            if ($this->accountModel->getAccountByNumber($data['account_number'])) {
                throw new Exception('Account number already exists');
            }

            $accountId = $this->accountModel->createAccount([
                'user_id' => $userId,
                'account_number' => $data['account_number'],
                'balance' => $data['balance'],
                'currency' => $data['currency'] ?? 'USD',
            ]);

            $account = $this->accountModel->getAccountById($accountId);
            return $account ?? [];
        } catch (Exception $e) {
            error_log("AccountService createAccount error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAccountById(int $id): ?array
    {
        try {
            return $this->accountModel->getAccountById($id);
        } catch (Exception $e) {
            error_log("AccountService getAccountById error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getUserAccounts(int $userId): array
    {
        try {
            // Validate user exists
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new Exception('User not found');
            }

            return $this->accountModel->getUserAccounts($userId);
        } catch (Exception $e) {
            error_log("AccountService getUserAccounts error: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateAccount(int $id, array $data): array
    {
        try {
            // Check if account exists
            $account = $this->accountModel->getAccountById($id);
            if (!$account) {
                throw new Exception('Account not found');
            }

            // If balance is being updated, validate it's not negative
            if (isset($data['balance']) && $data['balance'] < 0) {
                throw new Exception('Balance cannot be negative');
            }

            $this->accountModel->updateAccount($id, $data);
            return $this->accountModel->getAccountById($id) ?? [];
        } catch (Exception $e) {
            error_log("AccountService updateAccount error: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteAccount(int $id): bool
    {
        try {
            // Check if account exists
            $account = $this->accountModel->getAccountById($id);
            if (!$account) {
                throw new Exception('Account not found');
            }

            return $this->accountModel->deleteAccount($id) > 0;
        } catch (Exception $e) {
            error_log("AccountService deleteAccount error: " . $e->getMessage());
            throw $e;
        }
    }
}

<?php

namespace Controllers;

use Core\BaseController;
use Services\AccountService;
use Exception;

class AccountController extends BaseController
{
    private AccountService $accountService;

    public function __construct(?AccountService $accountService = null)
    {
        $this->accountService = $accountService ?? new AccountService();
    }

    public function create(): void
    {
        try {
            $payload = $this->getJsonPayload();

            if (empty($payload['user_id']) || empty($payload['account_number']) || !isset($payload['balance'])) {
                $this->sendError('User ID, account number, and balance are required', 400);
            }

            $account = $this->accountService->createAccount($payload['user_id'], $payload);
            $this->sendSuccess($account, 'Account created successfully', 201);
        } catch (Exception $e) {
            error_log("AccountController create error: " . $e->getMessage());
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            $this->sendError($e->getMessage(), $statusCode);
        }
    }

    public function getById(int $id): void
    {
        try {
            $account = $this->accountService->getAccountById($id);

            if (!$account) {
                $this->sendError('Account not found', 404);
            }

            $this->sendSuccess($account, 'Account retrieved successfully');
        } catch (Exception $e) {
            error_log("AccountController getById error: " . $e->getMessage());
            $this->sendError($e->getMessage(), 500);
        }
    }

    public function getUserAccounts(int $userId): void
    {
        try {
            $accounts = $this->accountService->getUserAccounts($userId);
            $this->sendSuccess($accounts, 'Accounts retrieved successfully');
        } catch (Exception $e) {
            error_log("AccountController getUserAccounts error: " . $e->getMessage());
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 500;
            $this->sendError($e->getMessage(), $statusCode);
        }
    }

    public function update(int $id): void
    {
        try {
            $payload = $this->getJsonPayload();

            $account = $this->accountService->updateAccount($id, $payload);
            $this->sendSuccess($account, 'Account updated successfully');
        } catch (Exception $e) {
            error_log("AccountController update error: " . $e->getMessage());
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 400;
            $this->sendError($e->getMessage(), $statusCode);
        }
    }

    public function delete(int $id): void
    {
        try {
            $deleted = $this->accountService->deleteAccount($id);

            if (!$deleted) {
                $this->sendError('Account not found', 404);
            }

            $this->sendSuccess([], 'Account deleted successfully');
        } catch (Exception $e) {
            error_log("AccountController delete error: " . $e->getMessage());
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 500;
            $this->sendError($e->getMessage(), $statusCode);
        }
    }
}

<?php

namespace Controllers;

use Core\BaseController;
use Services\TransactionService;
use Exception;

class TransactionController extends BaseController
{
    private TransactionService $transactionService;

    public function __construct(?TransactionService $transactionService = null)
    {
        $this->transactionService = $transactionService ?? new TransactionService();
    }

    public function transfer(): void
    {
        try {
            $payload = $this->getJsonPayload();

            if (empty($payload['from_account_id']) || empty($payload['to_account_id']) || !isset($payload['amount'])) {
                $this->sendError('From account ID, to account ID, and amount are required', 400);
            }

            $transaction = $this->transactionService->transferFunds(
                $payload['from_account_id'],
                $payload['to_account_id'],
                $payload['amount'],
                $payload['description'] ?? ''
            );

            $this->sendSuccess($transaction, 'Transfer completed successfully', 201);
        } catch (Exception $e) {
            error_log("TransactionController transfer error: " . $e->getMessage());
            $statusCode = 400;
            if (str_contains($e->getMessage(), 'not found')) {
                $statusCode = 404;
            } elseif (str_contains($e->getMessage(), 'Insufficient funds')) {
                $statusCode = 422;
            }
            $this->sendError($e->getMessage(), $statusCode);
        }
    }

    public function getAccountTransactions(int $accountId): void
    {
        try {
            $transactions = $this->transactionService->getAccountTransactions($accountId);
            $this->sendSuccess($transactions, 'Transactions retrieved successfully');
        } catch (Exception $e) {
            error_log("TransactionController getAccountTransactions error: " . $e->getMessage());
            $statusCode = str_contains($e->getMessage(), 'not found') ? 404 : 500;
            $this->sendError($e->getMessage(), $statusCode);
        }
    }

    public function getTransactionById(int $id): void
    {
        try {
            $transaction = $this->transactionService->getTransactionById($id);

            if (!$transaction) {
                $this->sendError('Transaction not found', 404);
            }

            $this->sendSuccess($transaction, 'Transaction retrieved successfully');
        } catch (Exception $e) {
            error_log("TransactionController getTransactionById error: " . $e->getMessage());
            $this->sendError($e->getMessage(), 500);
        }
    }
}

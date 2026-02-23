<?php

namespace Services;

use Interfaces\TransactionServiceInterface;
use Models\TransactionModel;
use Models\AccountModel;
use Core\Database;
use Exception;
use PDO;

class TransactionService implements TransactionServiceInterface
{
    private TransactionModel $transactionModel;
    private AccountModel $accountModel;
    private PDO $pdo;

    public function __construct(
        ?TransactionModel $transactionModel = null,
        ?AccountModel $accountModel = null,
        ?PDO $pdo = null
    ) {
        $this->transactionModel = $transactionModel ?? new TransactionModel();
        $this->accountModel = $accountModel ?? new AccountModel();
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    public function transferFunds(int $fromAccountId, int $toAccountId, float $amount, string $description): array
    {
        try {
            // Validate accounts exist
            $fromAccount = $this->accountModel->getAccountById($fromAccountId);
            $toAccount = $this->accountModel->getAccountById($toAccountId);

            if (!$fromAccount) {
                throw new Exception('Source account not found');
            }
            if (!$toAccount) {
                throw new Exception('Destination account not found');
            }

            // Validate amount
            if ($amount <= 0) {
                throw new Exception('Amount must be greater than zero');
            }

            // Check if source account has sufficient balance
            if ((float) $fromAccount['balance'] < $amount) {
                throw new Exception('Insufficient funds. Current balance: ' . $fromAccount['balance']);
            }

            // Validate account status
            if ($fromAccount['status'] !== 'active') {
                throw new Exception('Source account is not active');
            }
            if ($toAccount['status'] !== 'active') {
                throw new Exception('Destination account is not active');
            }

            // Begin transaction
            $this->pdo->beginTransaction();

            try {
                // Deduct from source account
                $this->accountModel->decreaseBalance($fromAccountId, $amount);

                // Add to destination account
                $this->accountModel->increaseBalance($toAccountId, $amount);

                // Record transaction
                $transactionId = $this->transactionModel->createTransaction([
                    'from_account_id' => $fromAccountId,
                    'to_account_id' => $toAccountId,
                    'amount' => $amount,
                    'type' => 'transfer',
                    'status' => 'completed',
                    'description' => $description,
                ]);

                // Commit transaction
                $this->pdo->commit();

                return $this->transactionModel->getTransactionById($transactionId) ?? [];
            } catch (Exception $e) {
                // Rollback on error
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("TransactionService transferFunds error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAccountTransactions(int $accountId): array
    {
        try {
            // Validate account exists
            $account = $this->accountModel->getAccountById($accountId);
            if (!$account) {
                throw new Exception('Account not found');
            }

            return $this->transactionModel->getAccountTransactions($accountId);
        } catch (Exception $e) {
            error_log("TransactionService getAccountTransactions error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getTransactionById(int $id): ?array
    {
        try {
            return $this->transactionModel->getTransactionById($id);
        } catch (Exception $e) {
            error_log("TransactionService getTransactionById error: " . $e->getMessage());
            throw $e;
        }
    }
}

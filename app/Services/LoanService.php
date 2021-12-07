<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        //
        $loan = new Loan([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
        ]);
        $loan->save();
        for ($i = 1; $i <= $terms; ++$i) {
            $termAmount = intdiv($amount, $terms);
            $currentAmount = $i == $terms ? $termAmount + $amount % $terms : $termAmount;
            $scheduledRepayment = new ScheduledRepayment([
                'loan_id' => $loan->id,
                'amount' => $currentAmount,
                'currency_code' => $currencyCode,
                'due_date' => date('Y-m-d', strtotime("+{$i} month", strtotime($processedAt))),
            ]);
            $scheduledRepayment->save();
        }
        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        $received = new ReceivedRepayment([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);
        $received->save();
        $repayCandidates = $loan->scheduledRepayments()
            ->where('status', '<>', Loan::STATUS_REPAID)
            ->orderBy('due_date')->get();
        $remainingAmount = $amount;
        foreach ($repayCandidates as $repayCandidate) {
            $repayCandidate->refresh();
            if ($remainingAmount >= $repayCandidate->outstanding_amount) {
                $remainingAmount -= $repayCandidate->outstanding_amount;
                $repayCandidate->outstanding_amount = 0;
                $repayCandidate->save();
            } else {
                $repayCandidate->outstanding_amount -= $remainingAmount;
                $repayCandidate->save();
                break;
            }
        }
        return $received;
    }
}

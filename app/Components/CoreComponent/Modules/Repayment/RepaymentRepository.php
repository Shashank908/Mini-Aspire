<?php
namespace App\Components\CoreComponent\Modules\Repayment;

use App\Components\CoreComponent\Modules\Loan\Loan;
use App\Helpers\LoanCalculator;
use Validator;

/*
 *  
 */
class RepaymentRepository
{
    /**
     * Create Repayment
     *
     * @param array $data
     * @return \App\Components\CoreComponent\Modules\Repayment\Repayment|null
     */
    public function createRepayment(&$bag, $data = [])
    {
        $validator = Validator::make($data, RepaymentRequest::staticRules(),
            RepaymentRequest::staticMessages());
        if ($validator->fails()) {
            $bag = [
                "message" => trans("default.validation_error"),
                "errors" => $validator->errors(),
            ];
            return null;
        }

        // ensure no duplicate repayment
        if (Repayment::where('loan_id', $data['loan_id'])
            ->where('due_date', $data['due_date'])->exists()) {
            $bag = [
                "message" => trans("default.repayment_exist"),
            ];
            return null;
        }

        $repayment = new Repayment();
        $repayment->fill([
            'loan_id' => $data['loan_id'],
            'amount' => $data['amount'],
            'payment_status' => $data['payment_status'],
            'due_date' => $data['due_date'],
            'date_of_payment' => $data['date_of_payment'],
            'remarks' => $data['remarks'],
        ]);
        if ($repayment->save()) {
            return $repayment;
        }
        $bag = [
            "message" => trans("default.saving_fail"),
        ];
        return null;
    }

    /**
     * Generate Repayments
     *
     * @param array $bag
     * @param \App\Components\CoreComponent\Modules\Loan\Loan $loan
     * @return boolean
     */
    public function generateRepayments(&$bag, Loan $loan)
    {
        $frequencyType = $loan->repayment_frequency;
        $monthDuration = $loan->duration;
        $calData = [
            $loan->amount,
            $loan->interest_rate,
            $loan->duration,
        ];
        if (RepaymentFrequency::isMonthly($frequencyType)) {
            $amount = LoanCalculator::calculateMonthlyRepayment(...$calData);
        } else if (RepaymentFrequency::isFortnightly($frequencyType)) {
            $amount = LoanCalculator::calculateFortnightlyRepayment(...$calData);
        } else {
            $amount = LoanCalculator::calculateWeeklyRepayment(...$calData);
        }
        $startDate = $loan->date_contract_start;
        $endDate = $loan->date_contract_end;
        $dueDate = $startDate->copy();
        while (true) {
            if (RepaymentFrequency::isMonthly($frequencyType)) {
                $dueDate = $dueDate->copy()->addMonth(1);
                $dueDate->day = $endDate->day;
            } else if (RepaymentFrequency::isFortnightly($frequencyType)) {
                $dueDate = $dueDate->copy()->addDay(14);
            } else {
                $dueDate = $dueDate->copy()->addDay(7);
            }
            if ($dueDate->greaterThan($endDate)) {
                break;
            }
            if (!$this->createRepayment($bag, [
                'loan_id' => $loan->id,
                'amount' => $amount,
                'payment_status' => RepaymentStatus::UNPAID["id"],
                'due_date' => $dueDate . '',
                'date_of_payment' => null,
                'remarks' => null,
            ])) {
                return false;
            }
        }
        return true;
    }
}

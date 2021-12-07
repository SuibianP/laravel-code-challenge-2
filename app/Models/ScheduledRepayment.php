<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class ScheduledRepayment extends Model
{
    use HasFactory, Notifiable;

    public function repaid() {
        $this->outstanding_amount = 0;
        $this->status = ScheduledRepayment::STATUS_REPAID;
    }

    public function updateRepaid() {
        if ($this->outstanding_amount == 0 || $this->status == ScheduledRepayment::STATUS_REPAID) {
            $this->repaid();
        }
    }

    protected static function booted()
    {
        static::creating(function (ScheduledRepayment $scheduledRepayment) {
            $scheduledRepayment->outstanding_amount =
                $scheduledRepayment->outstanding_amount ?? $scheduledRepayment->amount;
            $scheduledRepayment->updateRepaid();
            if ($scheduledRepayment->status != ScheduledRepayment::STATUS_DUE) {
                $scheduledRepayment->load('loan');
                $scheduledRepayment->loan->refresh();
                $scheduledRepayment->loan->outstanding_amount -=
                    $scheduledRepayment->amount - $scheduledRepayment->outstanding_amount;
                $scheduledRepayment->loan->save();
            }
        });
        static::updating(function (ScheduledRepayment $scheduledRepayment) {
            $scheduledRepayment->updateRepaid();
            if ($scheduledRepayment->outstanding_amount < $scheduledRepayment->amount &&
                $scheduledRepayment->outstanding_amount > 0) {
                $scheduledRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
            }
        });
    }

    public const STATUS_DUE = 'due';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_REPAID = 'repaid';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scheduled_repayments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        //
        'outstanding_amount',
        'amount',
        'loan_id',
        'currency_code',
        'due_date',
        'status',
    ];

    /**
     * A Scheduled Repayment belongs to a Loan
     *
     * @return BelongsTo
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class ReceivedRepayment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'received_repayments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        //
        'loan_id',
        'amount',
        'currency_code',
        'received_at',
        'status',
    ];

    /**
     * A Received Repayment belongs to a Loan
     *
     * @return BelongsTo
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    protected static function booted()
    {
        static::creating(function (ReceivedRepayment $receivedRepayment) {
            $receivedRepayment->loan->refresh();
            $receivedRepayment->loan->outstanding_amount -= $receivedRepayment->amount;
            $receivedRepayment->loan->save();
        });
    }
}

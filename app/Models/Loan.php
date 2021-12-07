<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Loan extends Model
{
    public const STATUS_DUE = 'due';
    public const STATUS_REPAID = 'repaid';

    public const CURRENCY_SGD = 'SGD';
    public const CURRENCY_VND = 'VND';

    use HasFactory, Notifiable;

    public function repaid() {
        $this->outstanding_amount = 0;
        $this->status = Loan::STATUS_REPAID;
    }

    public function updateRepaid() {
        if ($this->outstanding_amount == 0 || $this->status == Loan::STATUS_REPAID) {
            $this->repaid();
        }
    }

    protected static function booted()
    {
        static::creating(function (Loan $loan) {
            $loan->outstanding_amount = $loan->outstanding_amount ?? $loan->amount;
        });
        static::updating(function (Loan $loan) {
            $loan->updateRepaid();
        });
    }

    public function setStatusAttribute($value) {
        if ($value == Loan::STATUS_REPAID) {
            $this->attributes['outstanding_amount'] = 0;
        }
        $this->attributes['status'] = $value;
    }

    public function setOutstandingAmountAttribute($value) {
        assert($value >= 0);
        if ($value == 0) {
            $this->status = Loan::STATUS_REPAID;
        }
        $this->attributes['outstanding_amount'] = $value;
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'amount',
        'terms',
        'outstanding_amount',
        'currency_code',
        'processed_at',
        'status',
    ];

    /**
     * A Loan belongs to a User
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * A Loan has many Scheduled Repayments
     *
     * @return HasMany
     */
    public function scheduledRepayments()
    {
        return $this->hasMany(ScheduledRepayment::class, 'loan_id');
    }
}

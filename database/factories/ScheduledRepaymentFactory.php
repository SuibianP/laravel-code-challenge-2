<?php

namespace Database\Factories;

use App\Models\DebitCardTransaction;
use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'amount' => $this->faker->randomNumber(),
            'loan_id' => Loan::factory()->create(),
            'currency_code' => $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
            'due_date' => $this->faker->dateTimeBetween('+1 month', '+3 year'),
        ];
    }
}

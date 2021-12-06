<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        $TRANSACTION_COUNT = 5;
        DebitCardTransaction::factory()->for($this->debitCard)->count($TRANSACTION_COUNT)->create();
        $response = $this->get("/api/debit-card-transactions?debit_card_id={$this->debitCard->id}");
        $response->assertOk();
        $response->assertJsonCount($TRANSACTION_COUNT);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $transaction = DebitCardTransaction::factory()->create();
        $response = $this->get("/api/debit-card-transactions?debit_card_id={$transaction->debit_card_id}");
        $response->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $AMOUNT = 10000;
        // post /debit-card-transactions
        $response = $this->post('/api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $AMOUNT,
            'currency_code' => 'SGD',
        ]);
        $response->assertSuccessful();
        $this->assertDatabaseHas(DebitCardTransaction::class, [
            'amount' => $AMOUNT,
            'currency_code' => 'SGD',
            'debit_card_id' => $this->debitCard->id,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otherCard = DebitCard::factory()->create();
        $response = $this->post('/api/debit-card-transactions', [
            'debit_card_id' => $otherCard->id,
            'amount' => 3000,
            'currency_code' => 'VND',
        ]);
        $response->assertForbidden();
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $transaction = DebitCardTransaction::factory()->for($this->debitCard)->create();
        $response = $this->get("/api/debit-card-transactions/{$transaction->id}");
        $response->assertSuccessful();
        $response->assertExactJson([
            'amount' => (string)$transaction->amount, # FIXME
            'currency_code' => $transaction->currency_code,
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $transaction = DebitCardTransaction::factory()->create();
        $response = $this->get("/api/debit-card-transactions/{$transaction->id}");
        $response->assertForbidden();
    }

    // Extra bonus for extra tests :)
}

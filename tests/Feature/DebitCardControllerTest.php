<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $CARD_COUNT = 5;
        DebitCard::factory()->count($CARD_COUNT)->for($this->user)->create();
        $response = $this->get('/api/debit-cards');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['*' => [
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]]);
        $this->assertCount($this->user->debitCards()->active()->count(), $response->json());
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $CARD_COUNT = 5;
        $response = $this->get('/api/debit-cards');
        DebitCard::factory()->count($CARD_COUNT)->create();
        $this->assertEmpty($response->json());
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $response = $this->post('/api/debit-cards', ['type' => 'Fantasy Bank']);
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $card = DebitCard::factory()->for($this->user)->create();
        $response = $this->get("/api/debit-cards/$card->id");
        $response->assertExactJson([
           'id' => $card->id,
           'number' => $card->number,
           'type' => $card->type,
           'expiration_date' => $card->expiration_date->format('Y-m-d H:i:s'),
           # how does the main logic work w/o format?
           'is_active' => $card->is_active,
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $card = DebitCard::factory()->create();
        $response = $this->withoutExceptionHandling([AuthorizationException::class])
            ->get("/api/debit-cards/{$card->id}");
        $response->assertForbidden();
        $response->assertDontSee(collect($card)->values());
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $card = DebitCard::factory()->for($this->user)->expired()->create();
        $response = $this->put("api/debit-cards/{$card->id}", ['is_active' => true,]);
        $response->assertOk();
        $card = $card->fresh();
        $this->assertEquals(true, $card->getIsActiveAttribute());
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $card = DebitCard::factory()->for($this->user)->active()->create();
        $response = $this->put("api/debit-cards/{$card->id}", ['is_active' => false,]);
        $response->assertOk();
        $card = $card->fresh();
        $this->assertEquals(false, $card->getIsActiveAttribute());
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $card = DebitCard::factory()->for($this->user)->create();
        $response = $this->put("api/debit-cards/{$card->id}", ['is_active' => '1919',]);
        $response->assertRedirect();
        # $response->assertUnprocessable();
        $this->assertEquals($card->disabled_at, $card->fresh()->disabled_at); # unchanged
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $card = DebitCard::factory()->for($this->user)->create();
        $response = $this->delete("api/debit-cards/{$card->id}");
        $response->assertSuccessful();
        $this->assertSoftDeleted($card);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $card = DebitCard::factory()->for($this->user)->create();
        $transaction = DebitCardTransaction::factory()->for($card)->create();
        $response = $this->delete("api/debit-cards/{$card->id}");
        $response->assertForbidden();
        $this->assertNotSoftDeleted($card);
    }

    // Extra bonus for extra tests :)
}

<?php

namespace Tests\Feature;

use App\Components\CoreComponent\Modules\Client\Client;
use App\Components\CoreComponent\Modules\Loan\Loan;
use App\Components\CoreComponent\Modules\Repayment\Repayment;
use App\Components\CoreComponent\Modules\Repayment\RepaymentFrequency;
use App\Components\CoreComponent\Modules\Repayment\RepaymentRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HttpTest extends TestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */

    public static $access_token;
    public function __construct()
    {
        parent::__construct();
    }

    protected function setUp(): void 
    {
        parent::setUp();
        // set your headers here
        $response = $this->post('/api/v1/signin', [
            'email' => 'eng.raksa@gmail.com',
            'password' => '123456',
        ]);
        $this->withHeaders([
            'Authorization' => $response['access_token']
        ]);

    }

    private function getResponseData()
    {
        $data = [
            'first_name' => 'test_firstname',
            'last_name' => 'test_lastname',
            'phone_number' => rand(1111111111,9999999999),
        ];
        $response = $this->post('/api/v1/clients/create', $data);
        return $response;
    }

    public function testClientPostTest()
    {
        $response = $this->getResponseData();
        $response->assertStatus(200);
        $responseData = $response->baseResponse->getData(true);
        $this->assertTrue(\is_array($responseData));
    }

    public function testGetClientByIdTest()
    {
        // Test post to get client by client id
        $response = $this->post('/api/v1/clients/get/' . 9999999);
        $response->assertStatus(405);
    }

    public function testClientExistsTest()
    {
        // Assert client exists
        $data = $this->getResponseData();
        $responseData = $data->baseResponse->getData(true);
        $clientId = $responseData['client']['id'];
        $client = Client::active()->find($clientId);
        $this->client = $client;
        $this->assertNotNull($client);
    }

    public function testLoansFrequencyTypeTest()
    {
        // Test post to get loans frequency repayment type
        $response = $this->get('/api/v1/loans/get_freq_type');
        $response->assertStatus(200);
        $types = RepaymentFrequency::toArrayForApi();
        $response->assertJson([
            "types" => $types,
        ]);
    }

    public function testGetLoansOfClientTest()
    {
        // Test post to get loans of client
        $responseData = $this->getResponseData();
        $clientId = $responseData['client']['id'];
        $client = Client::active()->find($clientId);
        $response = $this->get('/api/v1/loans/get', [
            'client_id' => $client->id,
        ]);
        $response->assertStatus(200);
        $responseData = $response->baseResponse->getData(true);
        $loanCount = \count($responseData['data']);
        $this->assertTrue($loanCount == 0);
    }

    public function testCreateLoanTest()
    {
                // Test post to create loan of client
        $responseData = $this->getResponseData();
        $clientId = $responseData['client']['id'];
        $client = Client::active()->find($clientId);
        self::$access_token = $client;
        $date = Carbon::now();
        for ($i = 0; $i < 6; $i++) {
            $date->addMonth($i);
            $duration = 12;
            $loanData = [
                'client_id' => $client->id,
                'amount' => 1000,
                'duration' => $duration,
                'repayment_frequency' => RepaymentFrequency::MONTHLY['id'],
                'interest_rate' => 0.1,
                'arrangement_fee' => 100,
                'remarks' => null,
                'date_contract_start' => $date . '',
            ];
            $response = $this->post('/api/v1/loans/create', \array_replace($loanData, ['repayment_frequency' => -1]));
            $response->assertStatus(400);
            $response = $this->post('/api/v1/loans/create', $loanData);
            $response->assertStatus(200);
            $this->assertTrue($client->refresh()->loans->count() == $i + 1);
            $responseData = $response->baseResponse->getData(true);
            $loanId = $responseData['loan']['id'];
            $loan = Loan::active()->find($loanId);
            $this->assertTrue($loan->date_contract_end->diffInMonths($loan->date_contract_start) == $duration);
            $this->assertNotNull($loan);

            // Test duplicate generated repayment
            $repaymentRepository = new RepaymentRepository();
            $success = $repaymentRepository->generateRepayments($bag, $loan);
            $this->assertFalse($success);
            $this->testLoanRepaymentTest($loan);
        }
    }

    private function testLoanRepaymentTest($loan)
    {
        // Assert repayments
        $repayments = $loan->repayments;
        $this->assertTrue($loan->duration == $repayments->count());
        $repayment = $repayments->get(0);
        // TODO: find good assertion for raise exception
        $exceptionRaised = false;
        try {
            $repayment->payment_status = -1;
        } catch (\Exception $e) {
            $exceptionRaised = true;
        }
        $this->assertTrue($exceptionRaised);

        // Assert repay
        foreach ($loan->repayments as $repayment) {
            $response = $this->post('/api/v1/repayments/pay/' . $repayment->id);
            $response->assertStatus(200);

            // Assert not allow repay for paid repayment
            $response = $this->post('/api/v1/repayments/pay/' . $repayment->id);
            $response->assertStatus(400);
        } 
    }

    public function testClientActivateTest()
    {
        // Assert client activate
        $client->activate(false);
        $this->assertFalse($client->refresh()->active);
        foreach ($client->loans as $loan) {
            $this->assertFalse($loan->active);
            foreach ($loan->repayments as $repayment) {
                $this->assertFalse($repayment->active);
            }
        }
        $client->activate(true);
        $this->assertTrue($client->refresh()->active);
        foreach ($client->loans as $loan) {
            $this->assertTrue($loan->active);
            foreach ($loan->repayments as $repayment) {
                $this->assertTrue($repayment->active);
            }
        }
    }

    public function testClientTrashedTest()
    {
        // Asset client trashed
        $loansCount = $client->loans->count();
        $this->assertTrue($client->delete());
        $this->assertTrue($client->refresh()->trashed());
        $this->assertTrue($client->loans->count() == 0);
        foreach ($client->loans as $loan) {
            $this->assertTrue($loan->trashed());
            foreach ($loan->repayments as $repayment) {
                $this->assertTrue($repayment->trashed());
            }
        }
        $this->assertTrue($client->forceRestoreThis());
        $this->assertFalse($client->refresh()->trashed());
        $this->assertTrue($client->loans->count() == $loansCount);
        foreach ($client->loans as $loan) {
            $this->assertFalse($loan->trashed());
            foreach ($loan->repayments as $repayment) {
                $this->assertFalse($repayment->trashed());
            }
        }
        $loans = $client->loans;
        $this->assertTrue($client->forceDeleteThis());
        $this->assertNull(Client::withTrashed()->find($client->id));
        foreach ($loans as $loan) {
            $this->assertNull(Loan::withTrashed()->find($loan->id));
            foreach ($loan->repayments as $repayment) {
                $this->assertNull(Repayment::withTrashed()->find($repayment->id));
            }
        }
    }
}
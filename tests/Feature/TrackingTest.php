<?php

namespace Tests\Feature;

use App\Models\Conversion;
use App\Models\Customer;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TrackingTest extends TestCase
{

  /** @test */
  public function distribute_revenue_valid_request()
  {
    $request = [
      'revenue' => 6,
      'customerId' => 123,
      'bookingNumber' => Str::random()
    ];
    create(Customer::class);
    $cookie = [
      'mhs_tracking' => '{
        "placements": [
            {"platform": "trivago", "customer_id": 123, "date_of_contact": "2018-01-01 14:00:00"}, 
            {"platform": "tripadvisor", "customer_id": 123, "date_of_contact": "2018-01-03 14:00:00"}, 
            {"platform": "kayak", "customer_id": 123, "date_of_contact": "2018-01-05 14:00:00"}
        ]
      }'
    ];
    $response = $this->call('GET', '/api/distribute-revenue', $request, $cookie);
    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  }

  /** @test */
  public function distribute_revenue_has_no_cookie()
  {
    $request = [
      'revenue' => 6,
      'customerId' => 123,
      'bookingNumber' => Str::random()
    ];
    create(Customer::class);
    $response = $this->call('GET', '/api/distribute-revenue', $request);
    $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
  }

  /** @test */
  public function distribute_revenue_requires_a_customer_id_that_exists()
  {
    $this->json('GET', '/api/distribute-revenue', [
      'revenue' => 6,
      'bookingNumber' => Str::random()
    ])
      ->assertJsonValidationErrors(['customerId']);
  }

  /** @test */
  public function distribute_revenue_requires_a_revenue_that_exists()
  {
    create(Customer::class);
    $this->json('GET', '/api/distribute-revenue', [
      'customerId' => 123,
      'bookingNumber' => Str::random()
    ])
      ->assertJsonValidationErrors(['revenue']);
  }
  /** @test */
  public function distribute_revenue_requires_a_booking_number_that_exists()
  {
    create(Customer::class);
    $this->json('GET', '/api/distribute-revenue', [
      'customerId' => 123,
      'bookingNumber' => Str::random()
    ])
      ->assertJsonValidationErrors(['revenue']);
  }

  /** @test */
  public function most_attracted_platform()
  {
    create(Customer::class);
    $conversion = create(Conversion::class, ['platform' => 'trivago']);
    $this->json('GET', '/api/most-attracted-platform')
      ->assertJsonFragment(['platform' => $conversion->platform]);

  }

  /** @test */
  public function most_attracted_platform_is_false_when_database_is_empty()
  {
    $this->json('GET', '/api/most-attracted-platform')
      ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
  }

  /** @test */
  public function get_platform_revenue_for_non_existed_platform()
  {
    $this->json('GET', '/api/platform-revenue?platform=trivago')
      ->assertJsonFragment(['trivago' => 0]);
  }

  /** @test */
  public function get_platform_revenue()
  {
    create(Customer::class);
    create(Conversion::class, ['platform' => 'trivago', 'revenue' => 10]);
    $this->json('GET', '/api/platform-revenue?platform=trivago')
      ->assertJsonFragment(['trivago' => 10]);
  }
}

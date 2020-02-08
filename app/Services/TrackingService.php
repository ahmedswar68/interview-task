<?php


namespace App\Services;


use App\Models\Conversion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class TrackingService
 * @package App\Services
 */
class TrackingService
{
  /**
   * @var $model
   */
  private $model;

  /**
   * TrackingService constructor.
   * @param Conversion|null $model
   */
  public function __construct(Conversion $model = null)
  {
    $this->model = $model ?? new Conversion();
  }

  /**
   * this function for distribute revenue among all platforms that exist in the static Cookie
   * this insert this distribute revenue for each platform with customer ID and booking number
   * @param int $customerId
   * @param string $bookingReference
   * @param int $revenue
   * @param $cookie
   * @return bool
   */
  public function distributeRevenue(int $customerId, string $bookingReference, int $revenue, $cookie): bool
  {
    // I assumed that the cookie is not sent then decode cookie to assoc array'
    if (is_null($cookie)) {
      $cookie = json_decode('{"placements": [
    {"platform": "trivago", "customer_id": 123, "date_of_contact": "2018-01-01 14:00:00"}, 
    {"platform": "tripadvisor", "customer_id": 123, "date_of_contact": "2018-01-03 14:00:00"}, 
    {"platform": "kayak", "customer_id": 123, "date_of_contact": "2018-01-05 14:00:00"}
    ]}', true);
    } else {
      $cookie = json_decode($cookie, true);
    }
    $placements = $cookie['placements'];


    // get avg for every platform
    $revenueShare = floor(($revenue / count($placements)));
    // loop through placements and insert them into DB
    $insertedData = [];
    foreach ($placements as $placement) {
      if ($customerId !== 123) {
        return false;
      }
      $insertedData[] = [
        'customer_id' => $customerId,
        'booking_reference' => $bookingReference,
        'revenue' => $revenueShare,
        'platform' => $placement['platform'],
        'date_of_contact' => $placement['date_of_contact'],
      ];

    }
    if (!empty($insertedData)) {
      try {
        $this->createConversion($insertedData);
        return true;
      } catch (\Exception $exception) {
        Log::error('cannot create revenue' . $exception->getMessage());
      }
    }
    return false;
  }

  /**
   * return most attracted platform
   * @return mixed
   */
  public function getMostAttractedPlatform()
  {
    return Conversion::select('platform')
      ->groupBy('platform')
      ->orderBy(DB::raw('count(platform)', 'DESC'))
      ->take(1)->first();

  }

  /**
   * create multiple records
   * @param array $data
   */
  private function createConversion(array $data): void
  {
    $this->model->insert($data);
  }
}
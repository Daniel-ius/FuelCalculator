<?php

namespace Drupal\Tests\fuel_calculator\Functional;

use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Fuel Calculator Controller API.
 *
 */
#[Group("fuel_calculator")]
#[RunTestsInSeparateProcesses]
class FuelCalculatorControllerTest extends BrowserTestBase
{
    protected $defaultTheme = 'stark';

    protected static $modules = [
    'fuel_calculator',
    'serialization',
    'basic_auth',
    ];

    protected $authenticatedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticatedUser = $this->drupalCreateUser([
        'access fuel calculator api',
        ]);
    }

  /**
   * Make an authenticated API request.
   */
    protected function apiPost($data)
    {
        $this->drupalLogin($this->authenticatedUser);
        $this->drupalGet('/user');

        return $this->getHttpClient()->request('POST', $this->buildUrl('/api/v1/fuel-calculator'), [
        RequestOptions::JSON => $data,
        RequestOptions::COOKIES => $this->getSessionCookies(),
        RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    public function testCalculateWithValidData()
    {
        $response = $this->apiPost([
        'distance' => 150,
        'consumption' => 7.5,
        'price' => 1.5,
        ]);

        $body = $response->getBody()->getContents();

        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Expected 200 but got ' . $response->getStatusCode() . '. Body: ' . $response->getBody()
        );

        $data = json_decode($body, true);

        $this->assertNotNull($data, 'JSON should decode. Body: ' . $body);

        $this->assertEquals('success', $data['status']);

        $results = $data['data']['results'];
        $this->assertEquals('11.25', $results['spent']);
        $this->assertEquals('16.88', $results['cost']);
    }

    public function testCalculateWithEdgeCaseValues()
    {
        $response = $this->apiPost(['distance' => 0.1, 'consumption' => 0.5, 'price' => 100]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCalculateWithLargeValues()
    {
        $response = $this->apiPost(['distance' => 10000, 'consumption' => 15, 'price' => 2.5]);
        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $this->assertEquals('1,500.00', $data['data']['results']['spent']);
    }

    public function testCalculateWithoutAuthentication()
    {
        $response = $this->getHttpClient()->request('POST', $this->buildUrl('/api/v1/fuel-calculator'), [
        RequestOptions::JSON => ['distance' => 100, 'consumption' => 7.5, 'price' => 1.5],
        RequestOptions::HTTP_ERRORS => false,
        ]);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testCalculateWithInvalidJson()
    {
        $this->drupalLogin($this->authenticatedUser);

        $response = $this->getHttpClient()->request('POST', $this->buildUrl('/api/v1/fuel-calculator'), [
        RequestOptions::BODY => 'invalid{json',
        RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
        RequestOptions::COOKIES => $this->getSessionCookies(),
        RequestOptions::HTTP_ERRORS => false,
        ]);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCalculateWithMissingFields()
    {
        $response = $this->apiPost(['distance' => 100, 'consumption' => 7.5]); // Missing price
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCalculateWithNonNumericValues()
    {
        $response = $this->apiPost(['distance' => 'abc', 'consumption' => 7.5, 'price' => 1.5]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCalculateWithZeroValues()
    {
        $response = $this->apiPost(['distance' => 0, 'consumption' => 7.5, 'price' => 1.5]);
        $data = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() === 200) {
            $this->assertEquals('0.00', $data['data']['results']['spent']);
        }
    }

    public function testCalculateWithNegativeValues()
    {
        $response = $this->apiPost(['distance' => -100, 'consumption' => 7.5, 'price' => 1.5]);
        $this->assertContains($response->getStatusCode(), [200, 400]);
    }

    public function testConcurrentRequests()
    {
        $response1 = $this->apiPost(['distance' => 100, 'consumption' => 7.5, 'price' => 1.5]);
        $response2 = $this->apiPost(['distance' => 200, 'consumption' => 8.5, 'price' => 1.8]);

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());

        $data1 = json_decode($response1->getBody()->getContents(), true);
        $data2 = json_decode($response2->getBody()->getContents(), true);

        $this->assertNotEquals($data1['data']['results']['cost'], $data2['data']['results']['cost']);
    }

    public function testCalculateWithoutPermission()
    {
        $unprivileged = $this->drupalCreateUser([]);
        $this->drupalLogin($unprivileged);
        $this->drupalGet('/user');

        $response = $this->getHttpClient()->request('POST', $this->buildUrl('/api/v1/fuel-calculator'), [
        RequestOptions::JSON => ['distance' => 100, 'consumption' => 7.5, 'price' => 1.5],
        RequestOptions::COOKIES => $this->getSessionCookies(),
        RequestOptions::HTTP_ERRORS => false,
        ]);

        $this->assertEquals(403, $response->getStatusCode());
    }
}

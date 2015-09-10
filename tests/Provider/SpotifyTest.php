<?php

namespace Ipalaus\OAuth2\Client\Test\Provider;

use Mockery as m;

class SpotifyTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected function setUp()
    {
        $this->provider = new \Ipalaus\OAuth2\Client\Provider\Spotify([
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $options = ['scope' => [uniqid(), uniqid()]];

        $url = $this->provider->getAuthorizationUrl($options);

        $this->assertContains(urlencode(implode(' ', $options['scope'])), $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/api/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $userId = rand(1000, 9999);
        $birthdate = uniqid();
        $country = uniqid();
        $displayName = uniqid();
        $email = uniqid();
        $url = uniqid();
        $followers = rand(1000, 9999);
        $image = uniqid();
        $product = uniqid();
        $type = uniqid();
        $uri = uniqid();

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn($this->mockGetMe($birthdate, $country, $displayName, $email, $url, $followers, $userId, $image, $product, $type, $uri));
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->toArray()['id']);
        $this->assertEquals($birthdate, $user->getBirthdate());
        $this->assertEquals($birthdate, $user->toArray()['birthdate']);
        $this->assertEquals($country, $user->getCountry());
        $this->assertEquals($country, $user->toArray()['country']);
        $this->assertEquals($displayName, $user->getDisplayName());
        $this->assertEquals($displayName, $user->toArray()['display_name']);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->toArray()['email']);
        $this->assertEquals($url, $user->getSpotifyUrl());
        $this->assertEquals($url, $user->toArray()['external_urls']['spotify']);
        $this->assertEquals($followers, $user->getFollowers());
        $this->assertEquals($followers, $user->toArray()['followers']['total']);
        $this->assertEquals($image, $user->getImage());
        $this->assertEquals($image, $user->toArray()['images'][0]['url']);
        $this->assertEquals($product, $user->getProduct());
        $this->assertEquals($product, $user->toArray()['product']);
        $this->assertEquals($type, $user->getType());
        $this->assertEquals($type, $user->toArray()['type']);
        $this->assertEquals($uri, $user->getUri());
        $this->assertEquals($uri, $user->toArray()['uri']);
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $message = uniqid();
        $status = rand(400, 600);
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(' {"error_description":"' . $message . '"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    protected function mockGetMe($birthdate, $country, $displayName, $email, $url, $followers, $userId, $image, $product, $type, $uri)
    {
        $body = '{
          "birthdate": "' . $birthdate . '",
          "country": "' . $country . '",
          "display_name": "' . $displayName . '",
          "email": "' . $email . '",
          "external_urls": {
            "spotify": "' . $url . '"
          },
          "followers" : {
            "href" : null,
            "total" : ' . $followers . '
          },
          "href": "https://api.spotify.com/v1/users/wizzler",
          "id": "' . $userId . '",
          "images": [
            {
              "height": null,
              "url": "' . $image . '",
              "width": null
            }
          ],
          "product": "' . $product . '",
          "type": "' . $type . '",
          "uri": "' . $uri . '"
        }';

        return $body;
    }
}

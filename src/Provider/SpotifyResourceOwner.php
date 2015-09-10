<?php

namespace Ipalaus\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SpotifyResourceOwner implements ResourceOwnerInterface
{
    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->response['id'] ?: null;
    }

    /**
     * Get resource owner birth date.
     *
     * @return string|null
     */
    public function getBirthdate()
    {
        return $this->response['birthdate'] ?: null;
    }

    /**
     * Get resource owner country.
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->response['country'] ?: null;
    }

    /**
     * Ger resource owner display name.
     *
     * @return string|null
     */
    public function getDisplayName()
    {
        return $this->response['display_name'] ?: null;
    }

    /**
     * Get resource owner email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['email'] ?: null;
    }

    /**
     * Get resource owner spotify url.
     *
     * @return string|null
     */
    public function getSpotifyUrl()
    {
        if (isset($this->response['external_urls']['spotify'])) {
            return $this->response['external_urls']['spotify'];
        }

        return null;
    }

    /**
     * Get resource owner followers count.
     *
     * @return int|null
     */
    public function getFollowers()
    {
        if(isset($this->response['followers']['total'])) {
            return (int)$this->response['followers']['total'];
        }

        return null;
    }
    /**
     * Get resource owner image.
     *
     * @return string|null
     */
    public function getImage()
    {
        if (isset($this->response['images'][0]['url'])) {
            return $this->response['images'][0]['url'];
        }

        return null;
    }

    /**
     * Get resource owner product.
     *
     * @return string|null
     */
    public function getProduct()
    {
        return $this->response['product'] ?: null;
    }

    /**
     * Get resource owner type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->response['type'] ?: null;
    }

    /**
     * Get resource owner uri.
     *
     * @return string|null
     */
    public function getUri()
    {
        return $this->response['uri'] ?: null;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}

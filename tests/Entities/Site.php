<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Entities;

use InvalidArgumentException;

class Site
{

    /**
     * @var string
     */
    private $slug;

    /**
     * @var string|null
     */
    private $domain;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @param string $slug
     * @param array $values
     * @return Site
     */
    public static function create($slug, array $values)
    {
        $site = new self($slug);
        $site->exchangeArray($values);

        return $site;
    }

    /**
     * Site constructor.
     *
     * @param string $slug
     */
    public function __construct($slug)
    {
        if (empty($slug)) {
            throw new InvalidArgumentException('Expecting a non-empty slug');
        }

        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $domain
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return (string) $this->domain;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return (string) $this->name;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function exchangeArray(array $values)
    {
        if ($domain = array_get($values, 'domain')) {
            $this->setDomain($domain);
        }

        if ($name = array_get($values, 'name')) {
            $this->setName($name);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'domain' => $this->getDomain(),
            'name' => $this->getName(),
        ];
    }

    /**
     * @return void
     */
    public function save()
    {
        app(SiteRepository::class)->store($this);
    }

    /**
     * @return void
     */
    public function delete()
    {
        app(SiteRepository::class)->remove($this);
    }

}

<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Entities;

use Generator;
use IteratorAggregate;

class SiteRepository implements IteratorAggregate
{

    /**
     * @var array
     */
    private $sites = [];

    /**
     * @param $slug
     * @return Site|null
     */
    public function find($slug)
    {
        if (!isset($this->sites[$slug])) {
            return null;
        }

        return Site::create($slug, $this->sites[$slug]);
    }

    /**
     * @return array
     */
    public function findAll()
    {
        return iterator_to_array($this->all());
    }

    /**
     * @param Site $site
     * @return void
     */
    public function store(Site $site)
    {
        $this->sites[$site->getSlug()] = $site->toArray();
    }

    /**
     * @param Site|string $site
     * @return void
     */
    public function remove($site)
    {
        $slug = ($site instanceof Site) ? $site->getSlug() : $site;

        unset($this->sites[$slug]);
    }

    /**
     * @return array
     */
    public function all()
    {
        return iterator_to_array($this);
    }

    /**
     * @return Generator
     */
    public function getIterator()
    {
        foreach ($this->sites as $slug => $values) {
            yield $slug => Site::create($slug, $values);
        }
    }

}

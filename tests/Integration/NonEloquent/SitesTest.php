<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\NonEloquent;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Entities\Site;
use CloudCreativity\LaravelJsonApi\Tests\Entities\SiteRepository;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

class SitesTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'sites';

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->withDefaultApi(['prefix' => '/api'], function (ApiGroup $api) {
            $api->resource('sites');
        });
    }

    public function testCreate()
    {
        $data = [
            'type' => 'sites',
            'id' => 'my-site',
            'attributes' => [
                'name' => 'My Blog',
                'domain' => 'http://blog.example.com',
            ],
        ];

        $id = $this->doCreate($data)->assertCreateResponse($data);
        $this->assertEquals('my-site', $id);

        return $data;
    }

    public function testRead()
    {
        $this->createSite();

        $expected = [
            'type' => 'sites',
            'id' => 'my-site',
            'attributes' => [
                'name' => 'My Blog',
                'domain' => 'http://blog.example.com',
            ],
        ];

        $this->doRead('my-site')->assertReadResponse($expected);
    }

    public function testUpdate()
    {
        $site = $this->createSite();

        $data = [
            'type' => 'sites',
            'id' => 'my-site',
            'attributes' => [
                'name' => 'My New Blog',
            ],
        ];

        $expected = $data;
        $expected['domain'] = $site->getDomain();

        $this->doUpdate($data)->assertUpdateResponse($expected);
    }

    public function testDelete()
    {
        $this->createSite();
        $this->doDelete('my-site')->assertDeleteResponse();
        $this->assertNull(app(SiteRepository::class)->find('my-site'));
    }

    /**
     * @return Site
     */
    private function createSite()
    {
        $site = Site::create('my-site', [
            'name' => 'My Blog',
            'domain' => 'http://blog.example.com',
        ]);

        app(SiteRepository::class)->store($site);

        return $site;
    }

}

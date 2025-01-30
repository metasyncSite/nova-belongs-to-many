<?php

declare(strict_types=1);

namespace MetasyncSite\NovaBelongsToMany\Tests;

use MetasyncSite\NovaBelongsToMany\BelongsToManySearchable;
use MetasyncSite\NovaBelongsToMany\Exception\BelongToManyException;
use Mockery;
use stdClass;
use Tests\TestCase;

class BelongsToManySearchableTest extends TestCase
{
    protected BelongsToManySearchable $field;

    protected function setUp(): void
    {
        parent::setUp();
        $this->field = new BelongsToManySearchable('Test Field');

    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_be_instantiated()
    {
        $this->assertInstanceOf(BelongsToManySearchable::class, $this->field);
        $this->assertEquals('belongs-to-many', $this->field->component);
    }

    public function test_it_requires_relationship_config()
    {
        $this->expectException(BelongToManyException::class);
        $this->field->resolve(new stdClass);
    }

    public function test_it_stores_meta_data_correctly()
    {
        $meta = $this->field->meta();

        $this->assertArrayHasKey('options', $meta);
        $this->assertArrayHasKey('placeholder', $meta);
        $this->assertEquals('Search...', $meta['placeholder']);
    }

    public function test_it_can_configure_create_button()
    {
        $this->field->withCreateButton(true, 'Add New');

        $meta = $this->field->meta();
        $this->assertTrue($meta['showCreateButton']);
        $this->assertEquals('Add New', $meta['createButtonLabel']);
    }
}

<?php

declare(strict_types=1);

namespace MetasyncSite\NovaBelongsToMany\Tests;

use Illuminate\Support\Collection;
use Laravel\Nova\Nova;
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

    public function test_it_handles_empty_values()
    {
        $testResource = $this->createTestResource();
        Nova::resources([$testResource]);

        $this->field->relationshipConfig(
            resourceClass: $testResource,
            relationName: 'testRelation',
            pivotTable: 'test_pivot',
            foreignPivotKey: 'test_id',
            relatedPivotKey: 'related_id'
        );

        $resource = $this->createTestModel();
        $resource->{$this->field->attribute} = null;
        $resource->testRelation = function () use ($resource) {
            return $resource->belongsToMany('TestRelated');
        };

        $this->field->resolve($resource);

        $this->assertEquals(0, $this->field->value);
    }

    protected function createTestModel(): object
    {
        return new class
        {
            public function belongsToMany($related): object
            {
                return new class
                {
                    public function getTable(): string
                    {
                        return 'test_pivot';
                    }

                    public function getForeignPivotKeyName(): string
                    {
                        return 'test_id';
                    }

                    public function getRelatedPivotKeyName(): string
                    {
                        return 'related_id';
                    }

                    public function count(): int
                    {
                        return 0;
                    }
                };
            }

            public function test_relation()
            {
                return $this->belongsToMany('TestRelated');
            }

            public static function all()
            {
                return new Collection;
            }
        };
    }

    protected function createTestResource(): string
    {
        $testResource = new class
        {
            public static $model;

            public static function uriKey(): string
            {
                return 'test-resources';
            }

            public static function newModel()
            {
                $class = self::$model;

                return new $class;
            }
        };
        $testResource::$model = get_class($this->createTestModel());

        return get_class($testResource);
    }
}

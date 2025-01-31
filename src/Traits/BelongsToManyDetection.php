<?php

declare(strict_types=1);

namespace MetasyncSite\NovaBelongsToMany\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use MetasyncSite\NovaBelongsToMany\Exception\BelongToManyException;
use Throwable;

trait BelongsToManyDetection
{
    /**
     * Detect pivot table information with bidirectional support
     *
     * @param mixed $resource Current model instance
     * @param string $relationName Relationship name
     * @param string $resourceClass Nova Resource class of the related model
     *
     * @throws BelongToManyException
     */
    protected function detectPivotInfo($resource, string $relationName, string $resourceClass): array
    {
        try {
            $sourceModelClass = get_class($resource);
            $targetModelClass = $resourceClass::$model;

            if (method_exists($resource, $relationName)) {
                $relation = $resource->{$relationName}();

                if ($relation instanceof BelongsToMany) {
                    return [
                        'pivotTable' => $relation->getTable(),
                        'foreignPivotKey' => $relation->getForeignPivotKeyName(),
                        'relatedPivotKey' => $relation->getRelatedPivotKeyName(),
                    ];
                }
            }

            $sourceModel = new $sourceModelClass;
            $targetModel = new $targetModelClass;

            $sourceTable = $sourceModel->getTable();
            $targetTable = $targetModel->getTable();

            $tables = [
                Str::singular($sourceTable).'_'.Str::singular($targetTable),
                Str::singular($targetTable).'_'.Str::singular($sourceTable),
            ];

            foreach ($tables as $tableName) {
                if (Schema::hasTable($tableName)) {
                    return [
                        'pivotTable' => $tableName,
                        'foreignPivotKey' => Str::singular($sourceTable).'_id',
                        'relatedPivotKey' => Str::singular($targetTable).'_id',
                    ];
                }
            }

            throw new BelongToManyException(
                sprintf(
                    'Could not find pivot table. Tried: %s. Models: %s, %s',
                    implode(', ', $tables),
                    class_basename($sourceModelClass),
                    class_basename($targetModelClass)
                )
            );

        } catch (Throwable $e) {
            throw new BelongToManyException(
                "Failed to detect pivot information: {$e->getMessage()}. ".
                'Please provide pivot details manually via relationshipConfig().'
            );
        }
    }
}

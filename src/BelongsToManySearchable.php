<?php

declare(strict_types=1);

namespace MetasyncSite\NovaBelongsToMany;

use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use MetasyncSite\NovaBelongsToMany\Exception\BelongToManyException;
use MetasyncSite\NovaBelongsToMany\Traits\BelongsToManyDetection;
use MetasyncSite\NovaBelongsToMany\Traits\WithCreateBtn;
use Override;
use Throwable;

class BelongsToManySearchable extends Field
{
    use BelongsToManyDetection;
    use WithCreateBtn;

    public $component = 'belongs-to-many';

    public $displayCallback;

    protected string|Resource|null $resourceClass = null;

    protected ?string $relationName = null;

    public function __construct($name, $attribute = null, $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->withMeta([
            'options' => [],
            'placeholder' => 'Search...',
            'resourceClass' => null,
            'displayField' => 'name',
            'relationName' => null,
            'pivotTable' => null,
            'foreignPivotKey' => null,
            'relatedPivotKey' => null,
        ]);
    }

    /**
     * @param string $resourceClass Nova Resource class
     * @param string $relationName Relationship method name
     * @param string|null $pivotTable Optional pivot table name
     * @param string|null $foreignPivotKey Optional foreign pivot key
     * @param string|null $relatedPivotKey Optional related pivot key
     * @param callable|null $displayCallback Optional display callback
     */
    public function relationshipConfig(
        string $resourceClass,
        string $relationName,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?callable $displayCallback = null
    ): static {
        $this->resourceClass = $resourceClass;
        $this->relationName = $relationName;
        $this->displayCallback = $displayCallback;

        try {
            $currentModelClass = $this->getCurrentModelClass();
            $currentModel = new $currentModelClass;

            if (! $pivotTable || ! $foreignPivotKey || ! $relatedPivotKey) {
                $pivotInfo = $this->detectPivotInfo($currentModel, $relationName, $resourceClass);

                $pivotTable1 = $pivotTable ?? $pivotInfo['pivotTable'];
                $foreignPivotKey1 = $foreignPivotKey ?? $pivotInfo['foreignPivotKey'];
                $relatedPivotKey1 = $relatedPivotKey ?? $pivotInfo['relatedPivotKey'];
            } else {
                $pivotTable1 = $pivotTable;
                $foreignPivotKey1 = $foreignPivotKey;
                $relatedPivotKey1 = $relatedPivotKey;
            }
        } catch (BelongToManyException) {
        }

        $this->withMeta([
            'resourceClass' => $resourceClass,
            'relationName' => $relationName,
            'pivotTable' => $pivotTable1 ?? null,
            'foreignPivotKey' => $foreignPivotKey1 ?? null,
            'relatedPivotKey' => $relatedPivotKey1 ?? null,
        ]);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveForDisplay($resource, $attribute = null): void
    {
        $relationName = $this->relationName ?? $this->attribute;
        $novaPath = config('nova.path');
        $links = $resource->{$relationName}()
            ->get()
            ->map(function ($relatedModel) use ($novaPath) {
                $resourceClass = $this->resourceClass;
                $title = $this->displayCallback
                    ? call_user_func($this->displayCallback, $relatedModel)
                    : ($relatedModel->{$this->meta['displayField']} ?? $relatedModel->getKey());

                return sprintf(
                    '<a class="link-default" href="%s/resources/%s/%s">%s</a>',
                    $novaPath,
                    $resourceClass::uriKey(),
                    $relatedModel->getKey(),
                    htmlspecialchars($title)
                );
            });

        $this->value = $links->toArray();
    }

    /**
     * @throws BelongToManyException
     */
    public function resolve($resource, $attribute = null): void
    {
        if (! $this->resourceClass || ! $this->relationName) {
            throw new BelongToManyException('relationshipConfig must be called with required parameters');
        }

        $modelClass = $this->resourceClass::$model;

        if (request()->route('resource') && ! request()->route('resourceId')) {
            $this->resolveForDisplay($resource, $attribute);

            return;
        }

        if (optional($resource)->exists) {
            $this->value = $resource->{$this->relationName}()
                ->get()
                ->map(fn ($item) => $item->getKey())
                ->values()
                ->all();
        }

        $options = $modelClass::all()
            ->map(function ($item) {
                try {
                    if (! $item) {
                        return null;
                    }

                    if ($this->displayCallback) {
                        $label = call_user_func($this->displayCallback, $item);
                    } else {
                        $label = $item->{$this->meta['displayField']} ?? '';
                    }

                    if (empty($label)) {
                        $label = 'ID: '.$item->getKey();
                    }

                    return [
                        'value' => $item->getKey(),
                        'label' => $label,
                    ];
                } catch (Throwable $e) {
                    if (config('app.debug')) {
                        Log::error('BelongsToMany display error: '.$e->getMessage());
                    }

                    return null;
                }
            })
            ->filter()
            ->values();

        $this->withMeta(['options' => $options]);
    }

    /**
     * {@inheritDoc}
     *
     * @throws BelongToManyException
     */
    #[Override]
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute): void
    {
        if (! $request->exists($requestAttribute)) {
            return;
        }

        try {
            $selectedIds = json_decode($request->input($requestAttribute) ?? '[]', true);

            if (! is_array($selectedIds)) {
                $selectedIds = [];
            }

            $model->{$this->relationName}()->sync($selectedIds);

        } catch (Throwable $e) {
            if (config('app.debug')) {
                Log::error('BelongsToManySearchable fillAttributeFromRequest error: '.$e->getMessage());
            }

            throw new BelongToManyException($e->getMessage());
        }
    }

    /**
     * @throws BelongToManyException
     */
    protected function getCurrentModelClass(): string
    {
        $request = app(NovaRequest::class);
        $resource = $request->route('resource');
        $resourceClass = Nova::resourceForKey($resource);

        if (! $resourceClass) {
            throw new BelongToManyException("Could not find resource class for key: {$resource}");
        }

        return $resourceClass::$model;
    }
}

<?php

declare(strict_types=1);

namespace MetasyncSite\NovaBelongsToMany\Traits;

use Override;

trait WithCreateBtn
{
    protected bool $showCreateButton = false;

    protected string $createButtonLabel = 'Create New';

    /**
     * @param bool $show
     * @param string|null $label
     *
     * @return $this
     */
    public function withCreateButton(bool $show = true, ?string $label = null): static
    {
        $this->showCreateButton = $show;

        if ($label) {
            $this->createButtonLabel = $label;
        }

        $this->withMeta([
            'showCreateButton' => $this->showCreateButton,
            'createButtonLabel' => $this->createButtonLabel,
            'resourceName' => $this->resourceClass ? $this->resourceClass::uriKey() : null,
        ]);

        return $this;
    }

    /**
     * @return void
     */
    protected function appendCreateButtonMeta(): void
    {
        $this->withMeta([
            'showCreateButton' => $this->showCreateButton,
            'createButtonLabel' => $this->createButtonLabel,
            'resourceName' => $this->resourceClass ? $this->resourceClass::uriKey() : null,
        ]);
    }

    #[Override]
    public function resolveUsing($callback): static
    {
        parent::resolveUsing($callback);
        $this->appendCreateButtonMeta();

        return $this;
    }
}

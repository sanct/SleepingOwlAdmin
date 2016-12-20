<?php

namespace SleepingOwl\Admin\Display\Column;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use SleepingOwl\Admin\Display\ControlLink;
use SleepingOwl\Admin\Display\TableColumn;
use SleepingOwl\Admin\Display\ControlButton;
use SleepingOwl\Admin\Contracts\AdminInterface;
use SleepingOwl\Admin\Contracts\Display\ControlButtonInterface;
use SleepingOwl\Admin\Contracts\Display\TableHeaderColumnInterface;

class Control extends TableColumn
{
    /**
     * @var string
     */
    protected $view = 'column.control';

    /**
     * @var string
     */
    protected $width = '90px';

    /**
     * @var Collection
     */
    protected $buttons;

    /**
     * @var bool
     */
    protected $editable = true;

    /**
     * @var bool
     */
    protected $deletable = true;

    /**
     * @var bool
     */
    protected $destroyable = true;

    /**
     * @var bool
     */
    protected $restorable = true;

    /**
     * Control constructor.
     *
     * @param AdminInterface $admin
     * @param TableHeaderColumnInterface $headerColumn
     * @param string|null $label
     */
    public function __construct(AdminInterface $admin, TableHeaderColumnInterface $headerColumn, $label = null)
    {
        parent::__construct($admin, $headerColumn, $label);

        $this->buttons = new Collection();

        $this->setHtmlAttribute('class', 'text-right');
    }

    public function initialize()
    {
        parent::initialize();

        $this->buttons->put('edit', $button = new ControlLink(function (Model $model) {
            return $this->getModelConfiguration()->getEditUrl($model->getKey());
        }, trans('sleeping_owl::lang.table.edit'), 100));
        $button->hideText();
        $button->setCondition(function () {
            return $this->isEditable();
        });
        $button->setIcon('fa fa-pencil');
        $button->setHtmlAttribute('class', 'btn-primary');

        $this->buttons->put('delete', $button = new ControlButton(function (Model $model) {
            return $this->getModelConfiguration()->getDeleteUrl($model->getKey());
        }, trans('sleeping_owl::lang.table.delete'), 200));
        $button->setCondition(function () {
            return $this->isDeletable();
        });

        $button->setMethod('delete');
        $button->hideText();
        $button->setIcon('fa fa-trash');
        $button->setHtmlAttribute('class', 'btn-danger btn-delete');

        $this->buttons->put('destroy', $button = new ControlButton(function (Model $model) {
            return $this->getModelConfiguration()->getDestroyUrl($model->getKey());
        }, trans('sleeping_owl::lang.table.destroy'), 300));
        $button->setCondition(function () {
            return $this->isDestroyable();
        });

        $button->setMethod('delete');
        $button->hideText();
        $button->setIcon('fa fa-trash');
        $button->setHtmlAttribute('class', 'btn-danger btn-destroy');

        $this->buttons->put('restore', $button = new ControlButton(function (Model $model) {
            return $this->getModelConfiguration()->getRestoreUrl($model->getKey());
        }, trans('sleeping_owl::lang.table.restore'), 400));
        $button->setCondition(function () {
            return $this->isRestorable();
        });
        $button->hideText();
        $button->setIcon('fa fa-reply');
        $button->setHtmlAttribute('class', 'btn-warning');
    }

    /**
     * @param ControlButtonInterface $button
     *
     * @return $this
     */
    public function addButton(ControlButtonInterface $button)
    {
        $this->buttons->push($button);

        return $this;
    }

    /**
     * @param bool $editable
     * @return $this
     */
    public function setEditable($editable)
    {
        $this->editable = (bool) $editable;

        return $this;
    }

    /**
     * @param bool $deletable
     * @return $this
     */
    public function setDeletable($deletable)
    {
        $this->deletable = (bool) $deletable;

        return $this;
    }

    /**
     * @param bool $destroyable
     * @return $this
     */
    public function setDestroyable($destroyable)
    {
        $this->destroyable = (bool) $destroyable;

        return $this;
    }

    /**
     * @param bool $restorable
     * @return $this
     */
    public function setRestorable($restorable)
    {
        $this->restorable = (bool) $restorable;

        return $this;
    }

    /**
     * Check if instance supports soft-deletes and trashed.
     *
     * @return bool
     */
    protected function isTrashed()
    {
        if (method_exists($this->getModel(), 'trashed')) {
            return $this->getModel()->trashed();
        }

        return false;
    }

    /**
     * Check if instance editable.
     *
     * @return bool
     */
    protected function isEditable()
    {
        return
            $this->editable
            &&
            ! $this->isTrashed()
            &&
            $this->getModelConfiguration()->isEditable(
                $this->getModel()
            );
    }

    /**
     * Check if instance is deletable.
     *
     * @return bool
     */
    protected function isDeletable()
    {
        return
            $this->deletable
            &&
            ! $this->isTrashed()
            &&
            $this->getModelConfiguration()->isDeletable(
                $this->getModel()
            );
    }

    /**
     * Check if instance is force deletable.
     *
     * @return bool
     */
    protected function isDestroyable()
    {
        return
            $this->destroyable
            &&
            $this->isTrashed()
            &&
            $this->getModelConfiguration()->isDestroyable(
                $this->getModel()
            );
    }

    /**
     * Check if instance is restorable.
     *
     * @return bool
     */
    protected function isRestorable()
    {
        return
            $this->restorable
            &&
            $this->isTrashed()
            &&
            $this->getModelConfiguration()->isRestorable(
                $this->getModel()
            );
    }

    /**
     * @param Model $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        parent::setModel($model);

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return parent::toArray() + [
            'buttons' => $this->buttons
                ->each(function (ControlButtonInterface $button) {
                    $button->setModel($this->getModel());
                })
                ->filter(function (ControlButtonInterface $button) {
                    return $button->isActive();
                })
                ->sortBy(function (ControlButtonInterface $button) {
                    return $button->getPosition();
                }),
        ];
    }
}

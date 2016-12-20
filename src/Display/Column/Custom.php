<?php

namespace SleepingOwl\Admin\Display\Column;

use Closure;
use Illuminate\Database\Eloquent\Model;
use SleepingOwl\Admin\Display\TableColumn;
use SleepingOwl\Admin\Contracts\AdminInterface;
use SleepingOwl\Admin\Contracts\Display\TableHeaderColumnInterface;

class Custom extends TableColumn
{
    /**
     * Callback to render column contents.
     * @var Closure
     */
    protected $callback;

    /**
     * @var string
     */
    protected $view = 'column.custom';

    /**
     * @var bool
     */
    protected $orderable = false;

    /**
     * Custom constructor.
     *
     * @param AdminInterface $admin
     * @param TableHeaderColumnInterface $headerColumn
     * @param null|string $label
     * @param Closure $callback
     */
    public function __construct(AdminInterface $admin,
                                TableHeaderColumnInterface
                                $headerColumn,
                                $label = null,
                                Closure $callback = null)
    {
        parent::__construct($admin, $headerColumn, $label);
        if (! is_null($callback)) {
            $this->setCallback($callback);
        }
    }

    /**
     * @return Closure
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param Closure $callback
     *
     * @return $this
     */
    public function setCallback(Closure $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get value from callback.
     *
     * @param Model $model
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getValue(Model $model)
    {
        if (! is_callable($callback = $this->getCallback())) {
            throw new \Exception('Invalid custom column callback');
        }

        return call_user_func($callback, $model);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function toArray()
    {
        return parent::toArray() + [
            'value'  => $this->getValue($this->getModel()),
        ];
    }
}

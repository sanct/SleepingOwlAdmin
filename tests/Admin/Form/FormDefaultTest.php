<?php

use Mockery as m;
use SleepingOwl\Admin\Contracts\FormButtonsInterface;
use SleepingOwl\Admin\Contracts\FormElementInterface;
use SleepingOwl\Admin\Contracts\ModelConfigurationInterface;
use SleepingOwl\Admin\Contracts\RepositoryInterface;
use SleepingOwl\Admin\Form\FormDefault;

class FormDefaultTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function getForm(array $elements = [])
    {
        return new FormDefault($elements);
    }

    /**
     * FormDefault::__construct
     * FormDefault::getElements
     * FormDefault::getButtons
     */
    public function test_constructor()
    {
        \KodiCMS\Assets\Facades\PackageManager::shouldReceive('load')->once();
        \KodiCMS\Assets\Facades\PackageManager::shouldReceive('add')->once();

        $form = $this->getForm([
            m::mock(FormElementInterface::class)
        ]);

        $this->assertCount(1, $form->getElements());
    }

    /**
     * @covers FormDefault::initialize
     * @covers FormDefault::getRepository
     */
    public function test_initialize()
    {
        \KodiCMS\Assets\Facades\Meta::shouldReceive('loadPackage')->once();

        $this->app->instance(
            RepositoryInterface::class,
            $repository = m::mock(RepositoryInterface::class)
        );

        $class = FormDefaultTestMockModel::class;

        $this->app->instance(
            FormButtonsInterface::class,
            $buttons = m::mock(FormButtonsInterface::class)
        );

        $this->getSleepingOwlMock()->shouldReceive('getModel')
            ->once()
            ->with($class)
            ->andReturn(m::mock(ModelConfigurationInterface::class));

        $buttons->shouldReceive('setModel')->once();
        $buttons->shouldReceive('setModelConfiguration')->once();

        $form = $this->getForm([
            $element = m::mock(FormElementInterface::class),
            $uploadElement = m::mock(\SleepingOwl\Admin\Form\Element\Upload::class)
        ]);

        $element->shouldReceive('setModel')->once();
        $element->shouldReceive('initialize')->once();

        $uploadElement->shouldReceive('setModel')->once();
        $uploadElement->shouldReceive('initialize')->once();

        $this->assertFalse($form->hasHtmlAttribute('enctype'));
        $form->setAction('action');

        $form->setModelClass($class);
        $form->initialize();

        $this->assertEquals('multipart/form-data', $form->getHtmlAttribute('enctype'));

        //$this->assertEquals('POST', $form->getHtmlAttribute('method'));
        //$this->assertEquals('action', $form->getHtmlAttribute('action'));

        $this->assertEquals($repository, $form->getRepository());
    }

    /**
     * @covers FormDefault::getButtons
     * @covers FormDefault::setButtons
     */
    public function test_gets_and_sets_buttons()
    {
        $form = $this->getForm();

        $this->assertInstanceOf(FormButtonsInterface::class, $form->getButtons());

        $form->setButtons($buttons = m::mock(FormDefaultTestMockFormButtons::class));

        $this->assertEquals($buttons, $form->getButtons());
    }

    /**
     * @covers FormDefault::getButtons
     */
    public function test_redefine_default_buttons()
    {
        $this->app->instance(
            FormButtonsInterface::class,
            $buttons = m::mock(FormButtonsInterface::class)
        );

        $form = $this->getForm();
        $this->assertEquals($buttons, $form->getButtons());
    }

    /**
     * @covers FormDefault::getView
     * @covers FormDefault::setView
     */
    public function test_gets_and_sets_view()
    {
        $form = $this->getForm();
        $this->assertEquals('form.default', $form->getView());

        $form->setView($view = 'custom.template');
        $this->assertEquals($view, $form->getView());
    }

    /**
     * @covers FormDefault::setAction
     * @covers FormDefault::getAction
     */
    public function test_gets_and_sets_action()
    {
        $form = $this->getForm();

        $form->setAction('action');
        $this->assertEquals('action', $form->getAction());
    }

    /**
     * @covers FormDefault::setModelClass
     * @covers FormDefault::getClass
     * @covers FormDefault::getModel
     */
    public function test_gets_and_sets_model_class()
    {
        $form = $this->getForm();

        $form->setModelClass($class = FormDefaultTestMockModel::class);

        $this->assertEquals($class, $form->getClass());
    }

    public function test_sets_model_class_exception()
    {
        $form = $this->getForm();

        $form->setModelClass($class = FormDefaultTestMockModel::class);
        $form->setModelClass(\Illuminate\Database\Eloquent\Model::class);

        $this->assertEquals($class, $form->getClass());
    }

    /**
     * @covers FormDefault::getModelConfiguration
     */
    public function test_gets_model_configuration()
    {
        $this->getSleepingOwlMock()
            ->shouldReceive('getModel')
            ->once()
            ->with($model = FormDefaultTestMockModel::class)
            ->andReturn($return = 'model_configuration');

        $form = $this->getForm();
        $form->setModelClass($model);

        $this->assertEquals($return, $form->getModelConfiguration());
    }

    /**
     * @covers FormDefault::getModel
     * @covers FormDefault::setModel
     */
    public function test_gets_and_sets_model()
    {
        $this->app->instance(
            FormButtonsInterface::class,
            $buttons = m::mock(FormButtonsInterface::class)
        );

        $model = new FormDefaultTestMockModel();

        $buttons->shouldReceive('setModel')->once()->with($model);

        $form = $this->getForm([
            $element = m::mock(FormElementInterface::class)
        ]);

        $element->shouldReceive('setModel')->once()->with($model);

        $form->setModel($model);
    }

    /**
     * @covers FormDefault::saveForm
     */
    public function test_save_form()
    {
        $model = m::mock(FormDefaultTestMockModel::class);

        $model->shouldReceive('getRelations')->twice()->andReturn([]);
        $model->shouldReceive('save')->once();

        $modelConfiguration = m::mock(ModelConfigurationInterface::class);
        $modelConfiguration->shouldReceive('fireEvent')->times(4)->andReturn(true);

        $this->getSleepingOwlMock()
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($modelConfiguration);

        $this->app->instance(
            RepositoryInterface::class,
            $repository = m::mock(RepositoryInterface::class)
        );

        $form = $this->getForm([
            $element = m::mock(FormElementInterface::class)
        ]);

        $element->shouldReceive('setModel')->once()->with($model);
        $element->shouldReceive('isReadonly')->twice()->andReturn(false);
        $element->shouldReceive('isVisible')->twice()->andReturn(true);
        $element->shouldReceive('save')->once();
        $element->shouldReceive('afterSave')->once();

        $form->setModel($model);
        $form->saveForm($modelConfiguration);
    }

    public function test_save_relations()
    {
        $this->markTestSkipped('
            TODO need to write tests for FormDefault::saveBelongsToRelations and FormDefault::saveHasOneRelations
        ');
    }

    public function test_validate()
    {
        $request = $this->getRequest();
        $this->app['request'] = $request;
        $request->offsetSet('element', 'test');

        $this->validate();
    }

    /**
     * @expectedException \Illuminate\Validation\ValidationException
     */
    public function test_validate_with_exception()
    {
        $request = $this->getRequest();
        $this->app['request'] = $request;

        $this->validate();
    }

    protected function validate()
    {
        $modelConfiguration = m::mock(ModelConfigurationInterface::class);
        $modelConfiguration->shouldReceive('fireEvent')->once()->andReturn(true);

        $this->app->instance(
            RepositoryInterface::class,
            $repository = m::mock(RepositoryInterface::class)
        );

        $model = m::mock(FormDefaultTestMockModel::class);

        $model->shouldReceive('getConnectionName')->once()->andReturn('default');

        $this->getSleepingOwlMock()
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($modelConfiguration);

        $form = $this->getForm([
            $element = m::mock(FormElementInterface::class)
        ]);

        $element->shouldReceive('setModel')->once()->with($model);
        $element->shouldReceive('getValidationRules')->once()
            ->andReturn(['element' => 'required']);
        $element->shouldReceive('getValidationMessages')->once()->andReturn([]);
        $element->shouldReceive('getValidationLabels')->once()->andReturn([
            'element' => 'Element label'
        ]);

        $element->shouldReceive('isReadonly')->andReturn(false);
        $element->shouldReceive('isVisible')->andReturn(true);

        $form->setModel($model);

        $form->validateForm($modelConfiguration);
    }
}

class FormDefaultTestMockModel extends \Illuminate\Database\Eloquent\Model
{

}

abstract class FormDefaultTestMockFormButtons implements FormButtonsInterface
{

}
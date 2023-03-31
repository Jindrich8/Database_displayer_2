<?php
abstract class ListPage extends CRUDPage
{
    private $alert = [];

    protected function prepare(): void
    {
        parent::prepare();
        //pokud přišel výsledek, zachytím ho
        $crudResult = filter_input(INPUT_GET, 'success', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_GET, 'action');
        $crudAction = $action !== null ? CrudAction::tryFrom($action) : null;

        if (is_int($crudResult)) {
            $this->alert = [
                'alertClass' => $crudResult === 0 ? 'danger' : 'success'
            ];

            $this->alert['message'] = $this->getMessage($crudAction, $crudResult !== 0);
        }
    }

    abstract protected function getMessage(CrudAction $action, bool $success): string;

    abstract protected function getData(): string;


    protected function pageBody()
    {
        $html = "";
        //zobrazit alert
        if ($this->alert) {
            $html .= MustacheProvider::get()->render('crudResult', $this->alert);
        }

        //získat a prezentovat data
        $html .= $this->getData();

        return $html;
    }
}

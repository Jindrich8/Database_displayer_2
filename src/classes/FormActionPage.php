<?php
abstract class FormActionPage extends CRUDPage
{
    protected FormState $state;

    protected function findState(): void
    {
        $this->state = $_SERVER['REQUEST_METHOD'] === 'POST' ?
            FormState::DATA_SENT
            : FormState::FORM_REQUESTED;
    }
}

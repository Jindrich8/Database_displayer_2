<?php
abstract class FormActionPage extends CRUDPage
{
    protected FormState $state;

    protected function findState(): void
    {
        $this->state = Utils::findFormState();
    }
}

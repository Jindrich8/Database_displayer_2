<?php
abstract class FormActionPage extends BaseLoggedInPage
{
    protected FormState $state;

    protected function findState(): void
    {
        $this->state = Utils::findFormState();
    }

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        switch ($this->state) {
            case FormState::FORM_REQUESTED:
                $this->formRequested();
                break;
            case FormState::DATA_SENT:
                $this->formDataSent();
                break;
        }
    }

    protected function formRequested(): void
    {
    }

    protected function formDataSent(): void
    {
    }
}

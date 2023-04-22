<?php
abstract class BaseLoggedInPage extends BasePage
{
    use Authentication;
    private ?Employee $user = null;

    protected function get_user(): ?Employee
    {
        if (!$this->user) {
            $id = $_SESSION['id'] ?? null;
            if ($id !== null) {
                $this->user = Employee::findByID($id);
            }
        }
        return $this->user;
    }

    protected function authorize(?Employee $user): void
    {
    }

    protected function authenticate_and_authorize(): void
    {
        $user = $this->get_user();

        $this->authenticate($user);
        $this->authorize($user);
    }

    protected function pageHeader(): string
    {
        return MustacheProvider::get()->render("navHeader", ['loggedUser' => $this->get_user()]);
    }
}

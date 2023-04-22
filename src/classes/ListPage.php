<?php
abstract class ListPage extends BaseLoggedInPage
{
    private $alert = [];
    private $columns = [];
    private $sorting = [];

    protected function extraHTMLHeaders(): string
    {
        return parent::extraHTMLHeaders() . "<link rel='stylesheet' href='/styles/listStyle.css' />";
    }

    protected function prepare(): void
    {
        parent::prepare();
        //pokud přišel výsledek, zachytím ho
        $operation = Utils::get_last_operation();
        if ($operation->action !== null) {

            $subject = null;
            if ($operation->model !== null && $operation->id !== null) {
                $template = "alert";
                switch ($operation->model) {
                    case Model::EMPLOYEE:
                        $template .= "Employee";
                        $employee = Employee::findByID($operation->id);
                        if ($employee) {
                            $subject = get_object_vars($employee);
                        }
                        break;
                    case Model::ROOM:
                        $template .= "Room";
                        $room = Room::findByID($operation->id);
                        if ($room) {
                            $subject = get_object_vars($room);
                        }
                        break;
                }
                if ($subject) {
                    $template .= "Subject";
                    $subject = MustacheProvider::get()->render($template, $subject);
                }
            }
            $this->alert = [
                'alertClass' => $operation->errorCode !== null ? 'danger' : 'success',
                'message' => ActionHelper::get_message(
                    $operation->action,
                    $operation->model,
                    $subject,
                    $operation->errorCode === null
                ),
                'reason' => ErrorCodes::get_message($operation->errorCode)
            ];
        }
        $columns = filter_input(INPUT_GET, 'columns');
        if ($columns !== null && $columns !== false) {
            $columns = filter_var_array(explode(',', $columns), FILTER_VALIDATE_INT);
            foreach ($columns as $column) {
                $columnNum = filter_var($column, FILTER_VALIDATE_INT);
                if ($columnNum !== false) {
                    $columnName = $this->colNumToName($columnNum);
                    if ($columnName) {
                        $this->columns[$columnNum] = $columnName;
                    }
                }
            }
        }
        $this->transformColumnsBeforeSorting($this->columns);
        $sorting = filter_input(INPUT_GET, 'sorting');
        if ($sorting) {
            foreach (explode(',', $sorting) as $sortValue) {
                $colNumAndOrder = explode('.', $sortValue);
                $sortOrder = filter_var($colNumAndOrder[1] ?? null, FILTER_VALIDATE_INT);
                if ($sortOrder !== false && ($colName = $this->columns[$colNumAndOrder[0]] ?? null)) {
                    $this->sorting[$colName] = $sortOrder ? 'DESC' : 'ASC';
                }
            }
        }
    }
    /**
     * @param int[] $columns
     */
    abstract protected function getData(array $sorting, array $columns): string;

    abstract protected function colNumToName(int $col): ?string;

    protected function transformColumnsBeforeSorting(array &$columns)
    {
    }


    protected function pageBody()
    {
        $html = "";
        //zobrazit alert
        if ($this->alert) {
            $html .= MustacheProvider::get()->render('crudResult', $this->alert);
        }

        //získat a prezentovat data
        return $html . $this->getData($this->sorting, $this->columns);
    }
}

<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeesPage extends ListPage
{
    private ?int $fetchRoomColIndex = null;
    private array $numToColumn;
    private array $columnToNum;
    public function __construct()
    {
        $this->title = "Výpis zaměstnanců";
        $this->numToColumn = Employee::FIELDS_NO_ID_PASSWORD_NAME_SURNAME;
        $this->columnToNum = array_flip($this->numToColumn);
    }



    protected function colNumToName(int $col): ?string
    {
        $name = $this->numToColumn[$col] ?? null;
        if ($name === Employee::ROOM) {
            $this->fetchRoomColIndex = $col;
        }
        return $name;
    }

    protected function transformColumnsBeforeSorting(?array &$columns)
    {
        if ($columns === null) {
            $columns = [
                $this->columnToNum[Employee::JOB] => Employee::JOB,
                $this->columnToNum[Employee::WAGE] => Employee::WAGE,
                $this->columnToNum[Employee::ROOM] => Employee::ROOM
            ];
            $this->fetchRoomColIndex = 2;
        }
        $columns[count($this->columnToNum)] = 'name';
    }

    /**
     * @param int[] $columns
     */
    protected function getData(array $sorting, ?array $columns): string
    {

        unset($columns[count($this->columnToNum)]);
        $employees = null;
        $fromAlias = null;
        $conns = [];
        if ($this->fetchRoomColIndex !== null) {
            unset($columns[$this->columnToNum[Employee::ROOM]]);
            $fromAlias = 'e';
            $roomAlias = 'r';
            $conns = [new JoinConn(
                'room',
                $roomAlias,
                on: "`$fromAlias`.`" . Employee::ROOM . "`=`$roomAlias`.`room_id`",
                columns: [
                    'room_id' => 'rRoom_id',
                ],
                rawColumns: [
                    "CONCAT(`$roomAlias`.`name`,'(',`$roomAlias`.`no`,')')" => 'room'
                ]
            )];
            $columns[$this->columnToNum[Employee::ROOM]] = Employee::ROOM;
        }
        $columns[] = Employee::ID;
        $stmt = Utils::select(
            PDOProvider::get(),
            columns: $columns,
            from: Employee::DB_TABLE,
            fromAlias: $fromAlias,
            conns: $conns,
            sorting: $sorting,
            rawColumns: ["CONCAT(`$fromAlias`.`" . Employee::SURNAME . "`,' ',`$fromAlias`.`" . Employee::NAME . '`)' => 'name']
        );
        if ($stmt) {
            $employees = $stmt->fetchAll();
        }
        if (array_key_exists(0, $columns)) {
            $first = $columns[0];
            unset($columns[0]);
            $columns[] = $first;
        }
        $this->columnToNum['name'] = count($this->columnToNum);
        //prezentovat data
        $i = 0;
        $sortingOrder = array_map(function () use (&$i) {
            return $i++;
        }, $sorting);

        $colsIndexes = array_flip($columns);
        $mustache = MustacheProvider::get();
        return $mustache->render(
            'employeeList',
            [
                'modal' => $mustache->render(
                    'visColsModal',
                    [
                        'content' => $mustache->render(
                            'employeeVisColsModalContent',
                            [
                                'columns' => $colsIndexes,
                                'colsindexes' => $this->columnToNum,
                            ]
                        )
                    ]
                ),
                'employees' => $employees,
                'isAdmin' => $this->get_user()->admin,
                'columns' => $colsIndexes,
                'colsindexes' => $this->columnToNum,
                'sorting' => $sorting,
                'sortingorder' => $sortingOrder
            ]
        );
    }
}

$page = new EmployeesPage();
$page->render();

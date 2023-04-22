<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomsPage extends ListPage
{
    private array $numToColumn;
    private array $columnToNum;

    public function __construct()
    {
        $this->title = "Výpis místností";
        $this->numToColumn = Room::FIELDS_NO_ID_NAME;
        $this->columnToNum = array_flip($this->numToColumn);
    }

    protected function colNumToName(int $col): ?string
    {
        return $this->numToColumn[$col] ?? null;
    }

    protected function transformColumnsBeforeSorting(array &$columns)
    {
        if (!$columns) {
            $columns = [

                $this->columnToNum[Room::NO] => Room::NO,
                $this->columnToNum[Room::PHONE] => Room::PHONE
            ];
        }
        $columns[count($this->columnToNum)] = Room::NAME;
    }

    protected function getData(array $sorting, array $columns): string
    {
        //získat data
        $columns[] = Room::ID;
        $stmt = Utils::select(
            PDOProvider::get(),
            $columns,
            Room::DB_TABLE,
            sorting: $sorting
        );
        $rooms = [];
        if ($stmt) {
            $rooms = $stmt->fetchAll();
        }
        foreach ($rooms as $key => $room) {
            $rooms[$key] =  Room::create($room);
        }
        $this->columnToNum[Room::NAME] = count($this->columnToNum);
        //prezentovat data
        $i = 0;
        $sortingOrder = array_map(function () use (&$i) {
            return $i++;
        }, $sorting);

        if (array_key_exists(0, $columns)) {
            $value = $columns[0];
            unset($columns[0]);
            $columns[] = $value;
        }

        $colsIndexes = array_flip($columns);
        //prezentovat data
        //  dump($columns);
        // dump($colsIndexes);

        $mustache = MustacheProvider::get();
        return $mustache->render(
            'roomList',
            [
                'modal' => $mustache->render(
                    'visColsModal',
                    [
                        'content' => $mustache->render(
                            'roomVisColsModalContent',
                            [
                                'columns' => $colsIndexes,
                                'colsindexes' => $this->columnToNum,
                            ]
                        )
                    ]
                ),
                'rooms' => $rooms,
                'admin' => $this->get_user()->admin,
                'columns' => $colsIndexes,
                'colsindexes' => $this->columnToNum,
                'sorting' => $sorting,
                'sortingorder' => $sortingOrder
            ]
        );
    }
}

$page = new RoomsPage();
$page->render();

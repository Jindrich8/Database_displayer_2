<?php
class ActionHelper
{
    public static function get_message(Action $action, ?Model $model, ?string $subject, bool $success): string
    {

        if ($subject) {
            $subject = " '$subject'";
        }
        switch ($model) {
            case Model::ROOM:
                switch ($action) {
                    case Action::CREATE:
                        return "Vytvoření místnosti$subject bylo " . ($success ? "úspěšné" : "neúspěšné") . '.';
                    case Action::UPDATE:
                        return "Úprava místnosti$subject proběhla " . ($success ? "úspěšně" : "neúspěšně") . '.';
                    case Action::DELETE:
                        return "Smazání místnosti$subject bylo " . ($success ? "úspěšné" : "neúspěšné") . '.';
                }
                return "Akce na místnosti$subject proběhla " . ($success ? "úspěšně" : "neúspěšně") . '.';
            case Model::EMPLOYEE:
                switch ($action) {
                    case Action::CREATE:
                        return "Vytvoření zaměstnance$subject bylo " . ($success ? "úspěšné" : "neúspěšné") . '.';
                    case Action::UPDATE:
                        return "Úprava zaměstnance$subject proběhla " . ($success ? "úspěšně" : "neúspěšně") . '.';
                    case Action::DELETE:
                        return "Smazání zaměstance$subject bylo " . ($success ? "úspěšné" : "neúspěšné") . '.';
                }
                return "Akce na zaměstnanci$subject proběhla " . ($success ? "úspěšně" : "neúspěšně") . '.';
        }
        if ($subject != null) {
            $subject = " na$subject";
        }
        return "Akce$subject proběhla " . ($success ? "úspěšně" : "neúspěšně") . '.';
    }
}

<?php
class ErrorCodes
{

    public static function get_message(?ErrorCode $code): ?string
    {
        switch ($code) {
            case ErrorCode::YouCannotDeleteYourself:
                return "Nemůžeš smazat sám sebe.";

            case ErrorCode::YouCannotDeleteHomeRoom:
                return "Nemůžeš smazat něčí domovskou místnost.";

            case ErrorCode::FailedToInserNewKeys:
                return "Nepovedlo se vytvořit nové klíče zaměstance.";

            case ErrorCode::FailedToDeleteOldKeys:
                return "Nepovedlo se smazat staré klíče zaměstance.";
        }
        return null;
    }
}

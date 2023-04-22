<?php
enum ErrorCode: int
{
    case Uknown = 0;
    case YouCannotDeleteYourself = 1;
    case YouCannotDeleteHomeRoom = 2;
    case FailedToInserNewKeys = 3;
    case FailedToDeleteOldKeys = 4;
}

<?php


namespace HotelSystem\Utils;


use Nette\Database\Context;
use Nette\InvalidStateException;

class DatabaseUtils
{
    /**
     * Utilita pro vložení nového řádku do databáze nebo úpravy již vloženého řádku
     * @param Context $database
     * @param string $table
     * @param array $wherePairs
     * @param array $pairs
     */
    public static function insertOrUpdate(Context $database, string $table, array $wherePairs, array $pairs)
    {
        $table = $database->table($table);

        $count = $table->where($wherePairs)->count();
        if ($count > 1) {
            throw new InvalidStateException('Cannot update');
        }

        if ($count === 0) {
            $table->insert($pairs);
        } else {
            $table->where($wherePairs)->update($pairs);
        }
    }
}
<?php


namespace HotelSystem\Model\Repository;


use HotelSystem\Model\Entity\BaseEntity;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

abstract class BaseRepository extends \YetORM\Repository
{
    /**
     * Vytvoří entitu z $row, pokud je $row NULL, vytvoří novou entitu
     * @param ActiveRow|NULL $row
     * @return BaseEntity
     */
    public function createEntity($row = NULL): BaseEntity
    {
        return new $this->entity($this, $row);
    }


    /**
     * Vrátí entitu podle ID, pokud řádek s ID neexistuje, vytvoří novou entitu
     * @param mixed $id
     * @return BaseEntity|\YetORM\Entity|NULL
     */
    public function getByID($id): BaseEntity
    {
        $row = $this->getTable()->get($id);
        return $this->createEntity($row);
    }


    /**
     * @return Context
     */
    public function getDatabase(): Context
    {
        return $this->database;
    }


    /**
     * Override pro zpřístupnění metody mimo třídu
     * @param null $table
     * @return \Nette\Database\Table\Selection
     */
    public function getTable($table = NULL): Selection
    {
        return parent::getTable($table);
    }
}
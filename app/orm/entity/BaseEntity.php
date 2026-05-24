<?php


namespace HotelSystem\Model\Entity;


use HotelSystem\Model\Repository\BaseRepository;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

abstract class BaseEntity extends \YetORM\Entity
{
    /** @var string */
    protected $idColumn;

    /** @var BaseRepository */
    protected $repository;

    /** @var array */
    protected $fastData = [];



    public function __construct(BaseRepository $repository, ?ActiveRow $row = NULL)
    {
        parent::__construct($row);
        $this->repository = $repository;
    }


    /**
     * @param string $columnName
     * @return mixed|ActiveRow|null
     */
    public function get(string $columnName)
    {
        return $this->record->{$columnName} ?? NULL;
    }


    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->get($this->idColumn);
    }


    /**
     * @param string $columnName
     * @param $value
     */
    public function set(string $columnName, $value): void
    {
        $this->record->{$columnName} = $value instanceof DateTime ? $value->getTimestamp() : $value;
    }


    /**
     * Nastaví dané sloupce na požadované hodnoty, očekává pole ve formátu columnName => value
     * @param array $data $columnName => $value
     * @return $this
     */
    public function setData(array $data): BaseEntity
    {
        foreach ($data as $columnName => $value) {
            $this->set($columnName, $value);
            $this->fastData[$columnName] = $value;
        }
        return $this;
    }


    /**
     * Vrací pole hodnot sloupců entity
     * @return array
     */
    public function getData(): array
    {
        return $this->record->getRow()
            ? $this->record->getRow()->toArray()
            : $this->fastData;
    }


    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return !(bool) $this->getId();
    }


    /**
     * @param string $table
     * @param string|null $throughColumn
     * @return ActiveRow|null
     */
    protected function findOneToOne(string $table, ?string $throughColumn = NULL): ?ActiveRow
    {
        return $this->record->getRow() && $this->record->ref($table, $throughColumn)
            ? $this->record->ref($table, $throughColumn)->getRow()
            : NULL;
    }


    /**
     * @param string $entity
     * @param string $table
     * @param string|null $throughColumn
     * @return BaseEntity
     */
    protected function getOneToOne(string $entity, string $table, ?string $throughColumn = NULL): BaseEntity
    {
        $data = $this->findOneToOne($table, $throughColumn);
        $entity = 'HotelSystem\Model\Entity\\'.$entity;
        return new $entity($this->repository, $data);
    }
}
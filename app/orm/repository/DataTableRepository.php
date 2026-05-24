<?php


namespace HotelSystem\Model\Repository;


use HotelSystem\Model\Entity\BaseEntityCollection;
use Nette\Database\Context as NdbContext;
use Nette\Database\Table\Selection;
use Nette\InvalidStateException;

/**
 * Abstraktní třída pro získání dat na vyrenderování v komponentě DataTable
 * Class DataTableRepository
 * @package HotelSystem\Model\Repository
 */
abstract class DataTableRepository extends BaseRepository
{
    /** @var array */
    protected $dataCollection;

    /** @var int */
    protected static $imagesCount = 3;

    /** @var Selection */
    protected $baseSelection;



    public function __construct(NdbContext $database)
    {
        parent::__construct($database);
        $this->baseSelection = $this->getTable();
    }


    /**
     * Vrátí pole informací o entitách v následujícím formátu:
     * entityID => [
     *          title => title
     *          description => description
     *          images => images
     *      ]
     * @return array
     * @throws InvalidStateException
     */
    public function getDataTableArray()
    {
        if (!isset($this->dataCollection)) {
            throw new InvalidStateException('Data collection must be set first');
        }
    }



    /**
     * Funkce nastaví kolekci entit, jejichž data se budou renderovat
     * @param int $limit
     * @param int $offset
     */
    public function setDataCollection(int $limit, int $offset): DataTableRepository
    {
        $this->dataCollection = (new BaseEntityCollection(
            $this->baseSelection->limit($limit, $offset),
            $this->entity,
            $this
        ))->toArray();
        return $this;
    }


    /**
     * Funkce aplikuje podmínky pro $baseSelection
     * @param array $filters
     * @return DataTableRepository
     */
    public abstract function applyDataTableFilters(array $filters): DataTableRepository;
}
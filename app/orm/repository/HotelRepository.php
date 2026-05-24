<?php


namespace HotelSystem\Model\Repository;


use HotelSystem\Model\Entity\Hotel;
use HotelSystem\Utils\DatabaseUtils;
use Nette\Database\Context as NdbContext;
use Nette\InvalidArgumentException;
use YetORM\Entity;

class HotelRepository extends DataTableRepository
{
    public function __construct(NdbContext $database)
    {
        $this->entity = 'HotelSystem\Model\Entity\Hotel';
        $this->table = TABLE_HOTELS;
        parent::__construct($database);
    }


    /**
     * Override persist metody pro uložení obrázků
     * @param Entity $entity
     * @return bool|void
     */
    public function persist(Entity $entity)
    {
        /** @var $entity Hotel */
        $this->transaction(function () use ($entity) {
            parent::persist($entity);

            foreach ($entity->findReceptionists() as $receptionistId => $userId) {
                if (!in_array($userId, $entity->getReceptionistsToInsert())) {
                    $this->getTable(TABLE_HOTEL_RECEPTIONISTS)
                        ->where(HOTEL_ID, $entity->getId())
                        ->where(USER_ID, $userId)
                        ->delete();
                }
            }

            foreach ($entity->getReceptionistsToInsert() as $receptionistId) {
                DatabaseUtils::insertOrUpdate($this->database, TABLE_HOTEL_RECEPTIONISTS, [
                    HOTEL_ID => $entity->getId(),
                    USER_ID => $receptionistId
                ], [
                    HOTEL_ID => $entity->getId(),
                    USER_ID => $receptionistId
                ]);
            }

            foreach ($entity->getImagesToInsert() as $imagePath) {
                DatabaseUtils::insertOrUpdate($this->database, TABLE_HOTEL_IMAGES, [
                    IMAGE_HOTEL_ID => $entity->getId(),
                    IMAGE_PATH => $imagePath
                ], [
                    IMAGE_HOTEL_ID => $entity->getId(),
                    IMAGE_PATH => $imagePath
                ]);
            }
        });
    }


    /**
     * Funkce pro DataTable komponentu
     */

    /**
     * @return array
     */
    final public function getDataTableArray(): array
    {
        parent::getDataTableArray();
        return array_combine(
            array_map(function (Hotel $hotel) { return $hotel->getId(); }, $this->dataCollection),
            array_map(function (Hotel $hotel) {
                return [
                    'title' => $hotel->getName(),
                    'description' => $hotel->getDescription(),
                    'images' => array_slice($hotel->getImages(), 0, self::$imagesCount)
                ];
            }, $this->dataCollection)
        );
    }


    /**
     * @param array $filters
     * @return $this|DataTableRepository
     */
    final public function applyDataTableFilters(array $filters): DataTableRepository
    {
        foreach ($filters as $filterType => $filterValue) {
            switch ($filterType) {
                case HOTEL_CITY:
                    $this->baseSelection->where(HOTEL_CITY . ' LIKE ?', '%' . $filterValue . '%');
                    break;
                case HOTEL_STAR_RATING:
                    $this->baseSelection->where(HOTEL_STAR_RATING, $filterValue);
                    break;
                default:
                    throw new InvalidArgumentException('Unknown filter type');
            }
        }
        return $this;
    }
}
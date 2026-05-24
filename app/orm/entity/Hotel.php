<?php


namespace HotelSystem\Model\Entity;


use HotelSystem\Model\Repository\BaseRepository;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Html;

class Hotel extends BaseEntity
{
    use EntityImageTrait;

    /** @var array */
    private $receptionistsToInsert = [];



    public function __construct(BaseRepository $repository, ?ActiveRow $row = NULL)
    {
        parent::__construct($repository, $row);
        $this->idColumn = HOTEL_ID;
    }


    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->get(HOTEL_NAME);
    }


    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->get(HOTEL_EMAIL);
    }


    /**
     * @return Html
     */
    public function getEmailLink(): Html
    {
        return Html::el('a', ['href' => 'mailto:' . $this->getEmail()])
            ->setText($this->getEmail());
    }


    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->get(HOTEL_PHONE);
    }


    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->get(HOTEL_DESCRIPTION);
    }


    /**
     * @return string
     */
    public function getFullAddress(): string
    {
        return $this->get(HOTEL_ADDRESS) && $this->get(HOTEL_CITY)
            ? $this->get(HOTEL_ADDRESS) . ', ' . $this->get(HOTEL_CITY)
            : $this->get(HOTEL_ADDRESS) . $this->get(HOTEL_CITY);
    }


    /**
     * @return int
     */
    public function getStarRating(): int
    {
        return $this->get(HOTEL_STAR_RATING);
    }


    /**
     * @return array
     */
    public function getImagesPath(): ?array
    {
        return $this->record->related(TABLE_HOTEL_IMAGES, IMAGE_HOTEL_ID)
            ->fetchPairs(IMAGE_ID, IMAGE_PATH);
    }


    /**
     * @return array
     */
    public function findReceptionists(): array
    {
        if ($this->isNew()) {
            return [];
        }
        return $this->record->related(TABLE_HOTEL_RECEPTIONISTS, HOTEL_ID)
            ->fetchPairs(HOTEL_RECEPTIONIST_ID, USER_ID);
    }


    /**
     * @return BaseEntityCollection
     */
    public function getReceptionists(): BaseEntityCollection
    {
        $receptionistsIds = $this->findReceptionists();
        $userSelection = $this->repository->getTable(TABLE_USERS)
            ->where(USER_ID, array_values($receptionistsIds));
        return new BaseEntityCollection(
            $userSelection,
            '\HotelSystem\Model\Entity\User',
            $this->repository
        );
    }


    /**
     * @param array $receptionists
     * @return $this
     */
    public function setReceptionistsToInsert(array $receptionists): Hotel
    {
        $this->receptionistsToInsert = $receptionists;
        return $this;
    }


    /**
     * @return array
     */
    public function getReceptionistsToInsert(): array
    {
        return $this->receptionistsToInsert;
    }


    /**
     * @param $owner
     * @return $this
     */
    public function setOwner($owner): Hotel
    {
        $this->set(HOTEL_OWNER_ID, $owner instanceof User ? $owner->getId() : $owner);
        return $this;
    }


    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->getOneToOne('User', TABLE_USERS, HOTEL_OWNER_ID);
    }


    /**
     * @param User $user
     * @return bool
     */
    public function isUserOwner(User $user): bool
    {
        return $this->getOwner()->getId() === $user->getId();
    }


    /**
     * @param User $user
     * @return bool
     */
    public function isUserReceptionist(User $user): bool
    {
        return in_array($user->getId(), $this->findReceptionists());
    }


    /**
     * @param int $roomNumber
     * @param int $roomId
     * @return bool
     */
    public function hasRoomWithNumber(int $roomNumber, int $roomId): bool
    {
        return $this->record->related(TABLE_ROOMS, ROOM_HOTEL_ID)
            ->where(ROOM_NUMBER, $roomNumber)
            ->where(ROOM_ID . ' <> ?', $roomId)
            ->count('*') > 0;
    }
}
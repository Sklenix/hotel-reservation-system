<?php


namespace HotelSystem\Model\Entity;


use HotelSystem\Model\Repository\BaseRepository;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

class Reservation extends BaseEntity
{
    public function __construct(BaseRepository $repository, ?ActiveRow $row = NULL)
    {
        parent::__construct($repository, $row);
        $this->idColumn = RESERVATION_ID;
    }


    /**
     * @param $user
     * @return $this
     */
    public function setUser($user): Reservation
    {
        $this->set(USER_ID, $user instanceof User ? $user->getId() : $user);
        return $this;
    }


    /**
     * @param $room
     * @return $this
     */
    public function setRoom($room): Reservation
    {
        $this->set(ROOM_ID, $room instanceof Room ? $room->getId() : $room);
        return $this;
    }


    /**
     * @param DateTime $date
     * @return $this
     */
    public function setDateFrom(\DateTimeImmutable $date): Reservation
    {
        $this->set(RESERVATION_DATE_FROM, $date);
        return $this;
    }


    /**
     * @param $date
     * @return $this
     */
    public function setDateTo(\DateTimeImmutable $date): Reservation
    {
        $this->set(RESERVATION_DATE_TO, $date);
        return $this;
    }


    /**
     * @return Room
     */
    public function getRoom(): Room
    {
        return $this->getOneToOne('Room', TABLE_ROOMS, ROOM_ID);
    }


    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->getOneToOne('User', TABLE_USERS, USER_ID);
    }


    /**
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->get(RESERVATION_USER_NAME);
    }


    /**
     * @return string|null
     */
    public function getUserSurname(): ?string
    {
        return $this->get(RESERVATION_USER_SURNAME);
    }


    /**
     * @return string|null
     */
    public function getUserEmail(): ?string
    {
        return $this->get(RESERVATION_USER_EMAIL);
    }


    /**
     * @return DateTime
     */
    public function getDateFrom(): DateTime
    {
        return $this->get(RESERVATION_DATE_FROM);
    }


    /**
     * @return DateTime
     */
    public function getDateTo(): DateTime
    {
        return $this->get(RESERVATION_DATE_TO);
    }


    /**
     * @return int
     */
    public function getLength(): int
    {
        return (int) $this->getDateTo()->diff($this->getDateFrom())->days;
    }


    /**
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return (bool) $this->get(RESERVATION_CONFIRMED);
    }


    /**
     * @return bool
     */
    public function isCheckedIn(): bool
    {
        return (bool) $this->get(RESERVATION_CHECK_IN);
    }


    /**
     * @return bool
     */
    public function isCheckedOut(): bool
    {
        return (bool) $this->get(RESERVATION_CHECK_OUT);
    }


    /**
     * @param bool $value
     * @return $this
     */
    public function setConfirmed(bool $value): Reservation
    {
        $this->set(RESERVATION_CONFIRMED, $value);
        return $this;
    }


    /**
     * @param bool $value
     * @return $this
     */
    public function checkIn(bool $value): Reservation
    {
        $this->set(RESERVATION_CHECK_IN, $value);
        return $this;
    }


    /**
     * @param bool $value
     * @return $this
     */
    public function checkOut(bool $value): Reservation
    {
        $this->set(RESERVATION_CHECK_OUT, $value);
        return $this;
    }


    /**
     * Vrátí pole objektů DateTime obsahující všechny datumy rezervace
     * @return array
     */
    public function getDates(): array
    {
        $from = clone $this->getDateFrom();
        $to = clone $this->getDateTo();

        do {
            $dates[] = clone $from;
            $from->modify('+1 day');
        } while ($from->getTimestamp() <= $to->getTimestamp());
        return $dates;
    }


    /**
     * Zjistí, jestli je zadané datum v rozsahu této rezervace
     * @param DateTime $dateFor
     * @return bool
     */
    public function isForDate(DateTime $dateFor): bool
    {
        return !empty(array_filter($this->getDates(), function (DateTime $date) use ($dateFor) {
            return $date->getTimestamp() === $dateFor->getTimestamp();
        }));
    }
}
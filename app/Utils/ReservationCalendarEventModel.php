<?php


namespace HotelSystem\Utils;


use HotelSystem\Model\Entity\Reservation;
use HotelSystem\Model\Entity\Room;
use Nette\Utils\DateTime;

class ReservationCalendarEventModel implements \EventCalendar\IEventModel
{
    /** @var Room */
    private $room;



    public function __construct(Room $room)
    {
        $this->room = $room;
    }


    /**
     * @inheritDoc
     */
    public function isForDate(int $year, int $month, int $day): bool
    {
        $date = DateTime::from($day . '.' . $month . '.' . $year);
        return in_array(TRUE, array_map(function (Reservation $reservation) use ($date) {
            return $reservation->isForDate($date);
        }, $this->room->getReservations()->toArray()));
    }


    /**
     * @inheritDoc
     */
    public function getForDate(int $year, int $month, int $day): array
    {
        return $this->isForDate($year, $month, $day)
            ? ['Termín je již rezervovaný']
            : [];
    }
}
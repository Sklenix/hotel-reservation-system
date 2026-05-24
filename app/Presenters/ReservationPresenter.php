<?php


namespace App\Presenters;


use EventCalendar\Simple\SimpleCalendar;
use Grido\Components\Filters\Filter;
use Grido\Grid;
use Grido\Translations\FileTranslator;
use HotelSystem\Model\Entity\BaseEntityCollection;
use HotelSystem\Model\Entity\Hotel;
use HotelSystem\Model\Entity\Reservation;
use HotelSystem\Model\Entity\Room;
use HotelSystem\Model\Repository\UserRepository;
use HotelSystem\Utils\ReservationCalendarEventModel;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Http\IResponse;
use Nette\Utils\Html;

class ReservationPresenter extends BasePresenter
{
    /** @var Room */
    private $room;

    /** @var Reservation */
    private $reservation;

    /** @var Selection */
    private $reservationSelection;

    /** @var array */
    private $hotelsToFilter = [];

    /** @var bool */
    private $onlyLoggedUserReservations = FALSE;



    public function actionEdit($roomId, $reservationId = NULL)
    {
        $this->room = $this->roomRepository->getByID($roomId);
        $this->reservation = $this->reservationRepository->getByID($reservationId);
    }


    /**
     * Action metoda pro vyrenderování přehledu rezervací,
     * kontroluje se, zda se vykresluje pro uživatele, pokoj nebo celý hotel,
     * podle toho bude vypadat Selection pro Grido komponentu,
     * zároveň zde pro každou větev probíhá kontrola práv (uživatel si nemůže prohlížet rezervace cizího hotelu atd.)
     * @param null $userId
     * @param null $roomId
     * @param null $hotelId
     * @throws \Nette\Application\BadRequestException
     */
    public function actionDefault($userId = NULL, $roomId = NULL, $hotelId = NULL)
    {
        $this->reservationSelection = $this->reservationRepository->getTable();
        if ($userId) {
            if (!$this->getUser()->isInRole(UserRepository::ROLE_RECEPTIONIST) && $userId != $this->getUser()->getId()) {
                $this->error('Na tuto akci nemáte dostatečná oprávnění', IResponse::S403_FORBIDDEN);
            }
            $this->hotelsToFilter = $this->hotelRepository->getTable()->fetchPairs(HOTEL_ID, HOTEL_NAME);
            $this->onlyLoggedUserReservations = $this->getUser()->getId() == $userId;

            $this->reservationSelection->where(USER_ID, $userId);
            $this->template->title = $userId == $this->getUser()->getId()
                ? 'Přehled Vašich rezervací'
                : 'Přehled rezervací uživatele ' . $this->userRepository->getByID($userId)->getFullName();
        } elseif ($roomId) {
            $this->reservationSelection->where(ROOM_ID, $roomId);
            $room = $this->roomRepository->getByID($roomId);
            $this->hotelsToFilter[$room->getHotel()->getId()] = $room->getHotel()->getName();

            if (!$this->getUser()->isInRole(UserRepository::ROLE_ADMIN)
                && !$room->getHotel()->isUserOwner($this->loggedUser) && !$room->getHotel()->isUserReceptionist($this->loggedUser)) {
                $this->error('Na tuto akci nemáte dostatečná oprávnění', IResponse::S403_FORBIDDEN);
            }

            $this->template->title = 'Přehled rezervací pro ' . $room->getCapacity() . ' lůžkový ' . $room->getTypeName() . ' pokoj v hotelu '
                . $room->getHotel()->getName();
        } elseif ($hotelId) {
            $hotel = $this->hotelRepository->getByID($hotelId);
            $this->hotelsToFilter[$hotelId] = $hotel->getName();

            if (!$this->getUser()->isInRole(UserRepository::ROLE_ADMIN)
                && !$hotel->isUserOwner($this->loggedUser) && !$hotel->isUserReceptionist($this->loggedUser)) {
                $this->error('Na tuto akci nemáte dostatečná oprávnění', IResponse::S403_FORBIDDEN);
            }

            $this->reservationSelection->where(ROOM_ID . ' IN ('
                . ' SELECT ' . ROOM_ID
                . ' FROM '   . TABLE_ROOMS
                . ' WHERE '  . ROOM_HOTEL_ID . ' = ?)', $hotelId);
            $this->template->title = 'Přehled rezervací hotelu ' . $this->hotelRepository->getByID($hotelId)->getName();
        } else {
            $hotels = (new BaseEntityCollection(
                $this->hotelRepository->getTable(),
                '\HotelSystem\Model\Entity\Hotel',
                $this->hotelRepository
            ))->toArray();
            $hotelsToFilter = array_filter($hotels, function (Hotel $hotel) {
                return $this->getUser()->isInRole(UserRepository::ROLE_ADMIN)
                    || $hotel->isUserReceptionist($this->loggedUser) || $hotel->isUserOwner($this->loggedUser);
            });
            $this->hotelsToFilter = array_combine(
                array_map(function (Hotel $hotel) { return $hotel->getId(); }, $hotelsToFilter),
                array_map(function (Hotel $hotel) { return $hotel->getName(); }, $hotelsToFilter)
            );
            $this->reservationSelection->where(ROOM_ID . ' IN ('
                . ' SELECT ' . ROOM_ID
                . ' FROM '   . TABLE_ROOMS
                . ' WHERE '  . ROOM_HOTEL_ID . ' IN ?)', array_keys($this->hotelsToFilter));

            $this->template->title = 'Přehled rezervací';
        }
    }



    public function renderEdit()
    {
        $this->template->roomType = $this->room->getTypeName();
        $this->template->hotel = $this->room->getHotel()->getName();
        $this->template->roomCapacity = $this->room->getCapacity();
    }


    /**
     * Kalendář zobrazující obsazenost pokoje
     * @return SimpleCalendar
     */
    protected function createComponentReservationCalendar(): SimpleCalendar
    {
        $calendar = new SimpleCalendar;
        $calendar->setLanguage(SimpleCalendar::LANG_CZ);
        $calendar->setFirstDay(SimpleCalendar::FIRST_MONDAY);
        $calendar->setOptions([
            SimpleCalendar::OPT_WDAY_MAX_LEN => 3,
            SimpleCalendar::OPT_BOTTOM_NAV_NEXT => 'Další měsíc',
            SimpleCalendar::OPT_BOTTOM_NAV_PREV => 'Předchozí měsíc',
            SimpleCalendar::OPT_TOP_NAV_PREV => Html::el('span', ['class' => 'fa fa-arrow-left']),
            SimpleCalendar::OPT_TOP_NAV_NEXT => Html::el('span', ['class' => 'fa fa-arrow-right'])
        ]);
        $calendar->setEvents(new ReservationCalendarEventModel($this->room));

        return $calendar;
    }


    /**
     * Rezervační formulář
     * @return Form
     */
    protected function createComponentReservationForm(): Form
    {
        $form = new Form;

        $form->addDatePicker(RESERVATION_DATE_FROM, 'Datum od (*)')
            ->setRequired()
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addDatePicker(RESERVATION_DATE_TO, 'Datum do (*)')
            ->setRequired()
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        if (!$this->getUser()->isLoggedIn()) {
            $form->addText(RESERVATION_USER_NAME, 'Jméno (*)')
                ->setRequired()
                ->setHtmlAttribute('class', 'form-control form-control-lg')
                ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

            $form->addText(RESERVATION_USER_SURNAME, 'Příjmení (*)')
                ->setRequired()
                ->setHtmlAttribute('class', 'form-control form-control-lg')
                ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

            $form->addText(RESERVATION_USER_PHONE, 'Telefon')
                ->setHtmlAttribute('class', 'form-control form-control-lg')
                ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

            $form->addEmail(RESERVATION_USER_EMAIL, 'Email (*)')
                ->setRequired()
                ->setHtmlAttribute('class', 'form-control form-control-lg')
                ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');
        }

        $form->addSubmit('save', 'Uložit rezervaci')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg btn-block');

        $form->onSuccess[] = [$this, 'onReservationFormSuccess'];
        $form->onValidate[] = [$this, 'onReservationFormValidate'];

        return $form;
    }


    /**
     * Callback pro uložení rezervace
     * @param Form $form
     */
    public function onReservationFormSuccess(Form $form): void
    {
        $values = $form->getValues(TRUE);
        try {
            $this->reservation->setRoom($this->room)
                ->setData($values);
            if ($this->getUser()->isLoggedIn()) {
                $this->reservation->setUser($this->loggedUser);
            }
            $this->reservationRepository->persist($this->reservation);
            if ($this->getUser()->isLoggedIn()) {
                $this->flashMessage('Rezervace úspěšně uložena.', 'success');
                $this->redirect('Reservation:default', ['userId' => $this->getUser()->getId()]);
            } else {
                $this->flashMessage('Rezervace úspěšně uložena. Pokud chcete, můžete dokončit registraci.', 'success');
                $this->redirect('User:edit', [
                    'defaultValues' => [
                        USER_NAME => $values[RESERVATION_USER_NAME],
                        USER_SURNAME => $values[RESERVATION_USER_SURNAME],
                        USER_EMAIL => $values[RESERVATION_USER_EMAIL],
                        USER_PHONE => $values[RESERVATION_USER_PHONE]
                    ]
                ]);
            }
        } catch (\PDOException $exception) {
            \Tracy\Debugger::barDump($exception);
            $form->addError('Při ukládání došlo k chybě');
        }
    }


    /**
     * Validace formuláře, rezervace na jeden pokoj se nemohou překrývat,
     * datum začátku rezervace musí být větší než datum konce rezervace
     * @param Form $form
     */
    public function onReservationFormValidate(Form $form): void
    {
        $values = $form->getValues(TRUE);

        if ($this->room->reservationExistInInterval($values[RESERVATION_DATE_FROM], $values[RESERVATION_DATE_TO])) {
            $form->addError('Je nám líto, ale Vámi vybrané dny jsou již rezervované.');
        }

        if ($values[RESERVATION_DATE_TO] <= $values[RESERVATION_DATE_FROM]) {
            $form->addError('Datum od musí být větší než datum do');
        }
    }


    /**
     * Komponenta pro vytvoření tabulky s rezervacemi
     * @return Grid
     * @throws \Grido\Exception
     */
    protected function createComponentReservationsGrid(): Grid
    {
        $grid = new Grid($this, 'reservationsGrid');
        $grid->setModel($this->reservationSelection)
            ->setPrimaryKey(RESERVATION_ID)
            ->setFilterRenderType(Filter::RENDER_INNER)
            ->setTranslator(new FileTranslator('cs'))
            ->setTemplateFile(__DIR__ . '/../../vendor/o5/grido/src/templates/bootstrap.latte');

        $grid->addColumnNumber(RESERVATION_ID, 'ID rezervace')
            ->setSortable()
            ->setFilterNumber()
            ->getControl()->getControlPrototype()->size = 3;

        $grid->addColumnLink(HOTEL_ID, 'Hotel')
            ->setCustomRender(function (ActiveRow $row) {
                $hotel = $this->reservationRepository->getByID($row[RESERVATION_ID])->getRoom()->getHotel();
                return Html::el('a', [
                    'href' => $this->link('Hotel:view', ['hotelId' => $hotel->getId()])
                ])
                    ->setText($hotel->getName());
            })
            ->setFilterSelect([0 => '- Vše -'] + $this->hotelsToFilter)
            ->setWhere(function ($value, Selection $source) {
                if ($value != 0) {
                    $source->where(ROOM_ID . ' IN ('
                        . ' SELECT ' . ROOM_ID
                        . ' FROM '   . TABLE_ROOMS
                        . ' WHERE '  . ROOM_HOTEL_ID . ' = ?)', $value);
                }
                return $source;
            });

        $grid->addColumnLink(ROOM_ID, 'Číslo pokoje')
            ->setCustomRender(function (ActiveRow $row) {
                $room = $this->reservationRepository->getByID($row[RESERVATION_ID])->getRoom();
                return Html::el('a', [
                    'href' => $this->link('Room:view', ['roomId' => $room->getId()])
                ])
                    ->setText($room->getNumber());
            });

        if ($this->getUser()->isInRole(UserRepository::ROLE_RECEPTIONIST) && !$this->onlyLoggedUserReservations) {
            $users = $this->userRepository->getTable()
                ->select(USER_ID . ', CONCAT_WS(" ", ' . USER_NAME . ', ' . USER_SURNAME.') AS full_name')
                ->fetchPairs(USER_ID, 'full_name');
            $grid->addColumnLink(USER_ID, 'Uživatel')
                ->setCustomRender(function (ActiveRow $row) {
                    $reservation = $this->reservationRepository->getByID($row[RESERVATION_ID]);
                    $user = $reservation->getUser();
                    return $user->isNew()
                        ? $reservation->getUserName() . ' ' . $reservation->getUserSurname()
                        : Html::el('a', [
                            'href' => $this->link('User:view', ['userId' => $user->getId()])
                        ])
                            ->setText($user->getFullName());
                })
                ->setFilterSelect([0 => '- Všichni -'] + $users)
                ->setWhere(function ($value, Selection $source) {
                    if ($value != 0) {
                        $source->where(USER_ID, $value);
                    }
                    return $source;
                });

            $grid->addColumnEmail(USER_EMAIL, 'Email')
                ->setCustomRender(function (ActiveRow $row) {
                    $reservation = $this->reservationRepository->getByID($row[RESERVATION_ID]);
                    $user = $reservation->getUser();
                    $emailHref = Html::el('a');
                    return $user->isNew()
                        ? $emailHref->setAttribute('href', 'mailto:' . $reservation->getUserEmail())
                            ->setText($reservation->getUserEmail())
                        : $emailHref->setAttribute('href', 'mailto:' . $user->getEmail())
                            ->setText($user->getEmail());
                });
        }

        $grid->addColumnDate(RESERVATION_DATE_FROM, 'Datum od')
            ->setCustomRender(function (ActiveRow $row) {
                return $this->reservationRepository->getByID($row[RESERVATION_ID])->getDateFrom()->format('d.m.Y');
            })
            ->setSortable();

        $grid->addColumnDate(RESERVATION_DATE_TO, 'Datum do')
            ->setCustomRender(function (ActiveRow $row) {
                return $this->reservationRepository->getByID($row[RESERVATION_ID])->getDateTo()->format('d.m.Y');
            })
            ->setSortable();

        $grid->addColumnNumber('price', 'Cena')
            ->setCustomRender(function (ActiveRow $row) {
                $reservation = $this->reservationRepository->getByID($row[RESERVATION_ID]);
                return ($reservation->getLength() * $reservation->getRoom()->getPrice()) . ' Kč';
            });

        $grid->addColumnNumber(RESERVATION_CONFIRMED, 'Potvrzeno')
            ->setCustomRender(function (ActiveRow $row) {
                $reservation = $this->reservationRepository->getByID($row[RESERVATION_ID]);
                return $reservation->isConfirmed()
                    ? Html::el('span', ['class' => 'fa fa-check'])
                    : Html::el('span', ['class' => 'fa fa-close']);
            });

        $grid->addColumnNumber(RESERVATION_CHECK_IN, 'Check in')
            ->setCustomRender(function (ActiveRow $row) {
                $reservation = $this->reservationRepository->getByID($row[RESERVATION_ID]);
                return $reservation->isCheckedIn()
                    ? Html::el('span', ['class' => 'fa fa-check'])
                    : Html::el('span', ['class' => 'fa fa-close']);
            });

        $grid->addColumnNumber(RESERVATION_CHECK_OUT, 'Check out')
            ->setCustomRender(function (ActiveRow $row) {
                $reservation = $this->reservationRepository->getByID($row[RESERVATION_ID]);
                return $reservation->isCheckedOut()
                    ? Html::el('span', ['class' => 'fa fa-check'])
                    : Html::el('span', ['class' => 'fa fa-close']);
            });

        if ($this->getUser()->isInRole(UserRepository::ROLE_RECEPTIONIST) && !$this->onlyLoggedUserReservations) {
            $grid->addActionEvent('confirm', '', function ($rowId) {
                $reservation = $this->reservationRepository->getByID($rowId);
                $value = !$reservation->isConfirmed();
                $reservation->setConfirmed($value);
                $this->reservationRepository->persist($reservation);
                if ($value) {
                    $this->flashMessage('Rezervace potvrzena', 'success');
                } else {
                    $this->flashMessage('Potvrzení rezervace zrušeno', 'success');
                }
            })
                ->setCustomRender(function (ActiveRow $row, Html $el) {
                    $el->class[] = 'btn btn-primary';
                    $el->style[] = 'margin: 2.5px;';
                    $el->title = 'Potvrdit';
                    return $el->addHtml(Html::el('span', ['class' => 'fa fa-check']));
                });

            $grid->addActionEvent('checkIn', '', function ($rowId) {
                $reservation = $this->reservationRepository->getByID($rowId);
                $value = !$reservation->isCheckedIn();
                $reservation->checkIn($value);
                $this->reservationRepository->persist($reservation);
                if ($value) {
                    $this->flashMessage('Check in potvrzen', 'success');
                } else {
                    $this->flashMessage('Check in zrušen', 'success');
                }
            })
                ->setCustomRender(function (ActiveRow $row, Html $el) {
                    $el->class[] = 'btn btn-primary';
                    $el->style[] = 'margin: 2.5px;';
                    $el->title = 'Check in';
                    return $el->addHtml(Html::el('span', ['class' => 'fa fa-sign-in']));
                });

            $grid->addActionEvent('checkOut', '', function ($rowId) {
                $reservation = $this->reservationRepository->getByID($rowId);
                $value = !$reservation->isCheckedOut();
                $reservation->checkOut($value);
                $this->reservationRepository->persist($reservation);
                if ($value) {
                    $this->flashMessage('Check out potvrzen', 'success');
                } else {
                    $this->flashMessage('Check out zrušen', 'success');
                }
            })
                ->setCustomRender(function (ActiveRow $row, Html $el) {
                    $el->class[] = 'btn btn-primary';
                    $el->style[] = 'margin: 2.5px;';
                    $el->title = 'Check out';
                    return $el->addHtml(Html::el('span', ['class' => 'fa fa-sign-out']));
                });

            $grid->addActionEvent('delete', '', function ($rowId) {
                if ($row = $this->reservationRepository->getTable()->get($rowId)) {
                    $row->delete();
                }
                $this->flashMessage('Rezervace úspěšně smazána', 'success');
            })
                ->setCustomRender(function (ActiveRow $row, Html $el) {
                    $el->class[] = 'btn btn-danger';
                    $el->style[] = 'margin: 2.5px;';
                    $el->title = 'Smazat';
                    return $el->addHtml(Html::el('span', ['class' => 'fa fa-trash']));
                });
        }

        return $grid;
    }
}
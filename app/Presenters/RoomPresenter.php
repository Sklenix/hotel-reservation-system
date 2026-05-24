<?php


namespace App\Presenters;


use EventCalendar\Simple\SimpleCalendar;
use HotelSystem\Components\DataTable;
use HotelSystem\Model\Entity\Room;
use HotelSystem\Model\Repository\UserRepository;
use HotelSystem\Utils\ReservationCalendarEventModel;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Http\IResponse;
use Nette\IOException;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Nette\Utils\ImageException;

class RoomPresenter extends BasePresenter
{
    /** @var Room */
    private $room;

    /** @var int */
    private $hotelId;



    public function actionEdit($roomId = NULL, $hotelId = NULL)
    {
        $this->room = $this->roomRepository->getByID($roomId);
        $this->hotelId = $hotelId;
        $owner = $this->room->getHotel()->getOwner();
        if (!$this->getUser()->isAllowed('room', 'edit')
            && (!$this->getUser()->isInRole(UserRepository::ROLE_ADMIN) || $owner !== $this->getUser()->getId())) {
            $this->error('Na tuto akci nemáte dostatečná práva', IResponse::S403_FORBIDDEN);
        }
    }



    public function actionDefault($hotelId = NULL)
    {
        $this->hotelId = $hotelId;
    }



    public function actionView($roomId)
    {
        $this->room = $this->roomRepository->getByID($roomId);
    }



    public function renderEdit()
    {
        $this->template->isNew = $this->room->isNew();
        $this->template->images = $this->room->isNew()
            ? []
            : $this->room->getImages();
    }



    public function renderView()
    {
        $this->template->roomNumber = $this->room->getNumber();
        $this->template->hotelId = $this->room->getHotel()->getId();
        $this->template->hotelName = $this->room->getHotel()->getName();
        $this->template->roomId = $this->room->getId();
        $this->template->isUserOwner = $this->room->getHotel()->isUserOwner($this->loggedUser);
        $this->template->isUserReceptionist = $this->room->getHotel()->isUserReceptionist($this->loggedUser);
        $this->template->capacity = $this->room->getCapacity();
        $this->template->price = $this->room->getPrice();
        $this->template->type = $this->room->getTypeName();
        $this->template->equipment = implode(', ', $this->room->getEquipment());
        $this->template->images = $this->room->getImages();
    }



    public function handleDeleteImage($imageId)
    {
        $this->roomRepository->getTable(TABLE_ROOM_IMAGES)->get($imageId)->delete();
        $this->flashMessage('Obrázek smazán', 'success');
        $this->redrawControl('images');
    }


    /**
     * Komponenta pro přehled pokojů
     * @return DataTable
     */
    protected function createComponentRoomDataTable(): DataTable
    {
        $filters = [
            [
                'type' => DataTable::SELECT_BOX_FILTER,
                'name' => ROOM_TYPE,
                'label' => 'Typ pokoje',
                'items' => Room::ROOM_TYPES
            ], [
                'type' => DataTable::INTEGER_INPUT_FILTER,
                'name' => ROOM_CAPACITY,
                'label' => 'Počet lůžek'
            ], [
                'type' => DataTable::TEXT_INPUT_FILTER,
                'name' => HOTEL_CITY,
                'label' => 'Město'
            ], [
                'type' => DataTable::CHECKBOX_LIST_FILTER,
                'name' => ROOM_EQUIPMENT_ID,
                'label' => 'Vybavení pokoje',
                'items' => $this->roomRepository->getEquipment()
            ], [
                'type' => DataTable::RANGE_FILTER,
                'name' => ROOM_PRICE,
                'label' => 'Cena'
            ], [
                'type' => DataTable::SELECT_BOX_FILTER,
                'name' => HOTEL_ID,
                'label' => 'Hotel',
                'items' => $this->hotelRepository->getTable()->fetchPairs(HOTEL_ID, HOTEL_NAME),
                'defaultValue' => $this->hotelId
            ]
        ];
        return new DataTable($this->roomRepository, $filters);
    }



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
     * Formulář pro vytvoření nebo editaci pokoje
     * @return Form
     */
    protected function createComponentRoomForm(): Form
    {
        $form = new Form;

        if ($this->getUser()->isInRole(UserRepository::ROLE_ADMIN)) {
            $hotels = $this->hotelRepository->getTable()->fetchPairs(HOTEL_ID, HOTEL_NAME);
        } else {
            $hotels = $this->hotelRepository->getTable()
                ->where(HOTEL_OWNER_ID, $this->loggedUser->getId())
                ->fetchPairs(HOTEL_ID, HOTEL_NAME);
        }

        $form->addHidden(ROOM_ID, $this->room->getId());

        $hotel = $form->addSelect(ROOM_HOTEL_ID, 'Hotel (*)', $hotels)
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Počet lůžek ...')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        if ($this->hotelId) {
            $hotel->setDefaultValue($this->hotelId);
        }

        $form->addInteger(ROOM_CAPACITY, 'Počet lůžek (*)')
            ->setRequired('Prosím vyplňte počet lůžek')
            ->setDefaultValue(1)
            ->setHtmlAttribute('class', 'form-control form-control-lg text-center')
            ->setHtmlAttribute('placeholder', 'Počet lůžek ...')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addText(ROOM_PRICE, 'Cena za noc (*)')
            ->setRequired('Prosím vyplňte cenu za noc')
            ->addRule(Form::FLOAT, 'Prosím zadejte platné desetinné číslo')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte cenu ...')
            ->setHtmlAttribute(' size', '70')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addSelect(ROOM_TYPE, 'Typ pokoje', Room::ROOM_TYPES)
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addInteger(ROOM_NUMBER, 'Číslo pokoje')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->setDefaults($this->room->getData());

        $form->addCheckboxList(EQUIPMENT_ID, 'Vybavení pokoje', $this->roomRepository->getEquipment())
            ->setDefaultValue(array_keys($this->room->getEquipment()))
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addMultiUpload(IMAGE_ROOM_ID, 'Obrázky')
            ->setHtmlAttribute('class', 'btn btn-danger')
            ->setHtmlAttribute('style', 'margin-left:15px;margin-bottom:15px;');

        $form->addSubmit('save', 'Uložit pokoj')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg btn-block')
            ->setHtmlAttribute('style', 'margin-left:15px;');
        $form->onSuccess[] = [$this, 'onRoomFormSuccess'];
        $form->onValidate[] = [$this, 'onRoomFormValidate'];

        return $form;
    }


    /**
     * Validace formuláře pro vytvoření pokoje, zkontroluje unikátnost čísla pokoje v rámci daného hotelu
     * @param Form $form
     */
    public function onRoomFormValidate(Form $form): void
    {
        $values = $form->getValues(TRUE);
        $hotel = $this->hotelRepository->getByID($values[ROOM_HOTEL_ID]);

        if ($hotel->hasRoomWithNumber($values[ROOM_NUMBER], $values[ROOM_ID])) {
            $form->addError('Číslo pokoje musí být unikátní v rámci hotelu');
        }
    }


    /**
     * Callback pro uložení pokoje
     * @param Form $form
     */
    public function onRoomFormSuccess(Form $form): void
    {
        $values = $form->getValues(TRUE);
        $this->room->setEquipmentToInsert($values[EQUIPMENT_ID]);
        unset($values[EQUIPMENT_ID]);
        unset($values[ROOM_ID]);

        try {
            $images = $values[IMAGE_ROOM_ID];
            unset($values[IMAGE_ROOM_ID]);
            $this->room->setData($values);
            $this->roomRepository->persist($this->room);

            /**
             * Obrázky se ukládájí až po persist, jelikož pro orientaci v souborovém systému je použito ID pokoje
             */
            $roomImagesPath = ROOM_IMAGES_FOLDER . $this->room->getId();
            FileSystem::createDir($roomImagesPath);
            /** @var $image FileUpload */
            foreach ($images as $image) {
                $image->toImage();
                $imagePath = $roomImagesPath . '/' . $image->getName();
                $image->move($imagePath);
                $this->room->addImage($imagePath);
            }
            $this->roomRepository->persist($this->room);
            $this->flashMessage('Pokoj úspěšně uložen.', 'success');
            $this->redirect('Room:view', ['roomId' => $this->room->getId()]);
        } catch (\PDOException $PDOException) {
            \Tracy\Debugger::barDump($PDOException);
            $form->addError('Při ukládání došlo k chybě');
        } catch (ImageException $imageException) {
            $form->addError('Prosím nahrávejte pouze obrázky');
        } catch (IOException $IOException) {
            $form->addError('Při nahrávání obrázků došlo k chybě');
        }
    }
}
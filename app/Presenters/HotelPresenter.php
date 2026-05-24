<?php


namespace App\Presenters;


use HotelSystem\Components\DataTable;
use HotelSystem\Model\Entity\Hotel;
use HotelSystem\Model\Repository\UserRepository;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Http\IResponse;
use Nette\IOException;
use Nette\Utils\FileSystem;
use Nette\Utils\ImageException;

class HotelPresenter extends BasePresenter
{
    /** @var Hotel */
    private $hotel;



    public function actionEdit($hotelId = NULL)
    {
        $this->hotel = $this->hotelRepository->getByID($hotelId);
        if (!$this->getUser()->isAllowed('hotel', 'edit')
            && (!$this->getUser()->isInRole(UserRepository::ROLE_ADMIN) || $this->hotel->isUserOwner($this->loggedUser))) {
            $this->error('Na tuto akci nemáte dostatečná oprávnění', IResponse::S403_FORBIDDEN);
        }
    }



    public function actionView($hotelId)
    {
        $this->hotel = $this->hotelRepository->getByID($hotelId);
    }



    public function renderEdit()
    {
        $this->template->owner = $this->hotel->getOwner();
        $this->template->hotelId = $this->hotel->getId();
        $this->template->isNew = $this->hotel->isNew();
        $this->template->images = $this->hotel->isNew()
            ? []
            : $this->hotel->getImages();
    }



    public function renderView()
    {
        $this->template->userAllowedToViewReservations = $this->hotel->isUserOwner($this->loggedUser)
            || $this->hotel->isUserReceptionist($this->loggedUser) || $this->getUser()->isInRole(UserRepository::ROLE_ADMIN);
        $this->template->hotelId = $this->hotel->getId();
        $this->template->name = $this->hotel->getName();
        $this->template->description = $this->hotel->getDescription();
        $this->template->starRating = $this->hotel->getStarRating();
        $this->template->fullAddress = $this->hotel->getFullAddress();
        $this->template->images = $this->hotel->getImages();
        $this->template->owner = $this->hotel->getOwner();
        $this->template->email = $this->hotel->getEmailLink();
        $this->template->phone = $this->hotel->getPhone();
        $this->template->receptionists = $this->hotel->getReceptionists();
    }



    public function handleDeleteImage($imageId)
    {
        $this->hotelRepository->getTable(TABLE_HOTEL_IMAGES)->get($imageId)->delete();
        $this->flashMessage('Obrázek smazán', 'success');
        $this->redrawControl('images');
    }


    /**
     * Komponenta pro přehled hotelů
     * @return DataTable
     */
    protected function createComponentHotelDataTable(): DataTable
    {
        $filters = [
            [
                'type' => DataTable::TEXT_INPUT_FILTER,
                'name' => HOTEL_CITY,
                'label' => 'Město'
            ], [
                'type' => DataTable::INTEGER_INPUT_FILTER,
                'name' => HOTEL_STAR_RATING,
                'label' => 'Počet hvězdiček'
            ]
        ];
        return new DataTable($this->hotelRepository, $filters);
    }


    /**
     * Formulář pro tvorbu hotelů
     * @return Form
     */
    protected function createComponentHotelForm(): Form
    {
        $form = new Form;

        $form->addText(HOTEL_NAME, 'Název hotelu (*)')
            ->setRequired('Prosím vyplňte název hotelu')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Název hotelu...')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addText(HOTEL_CITY, 'Město')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Lokace hotelu ...')
            ->setHtmlAttribute(' size', '70')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addText(HOTEL_ADDRESS, 'Adresa')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Adresa hotelu ...')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addInteger(HOTEL_STAR_RATING, 'Počet hvězdiček')
            ->setDefaultValue(1)
            ->addRule(Form::RANGE, 'Zvolte hodnotu mezi 1 a 5', [1, 5])
            ->setHtmlAttribute('class', 'form-control form-control-lg text-center')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addText(HOTEL_PHONE, 'Telefon')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Telefon hotelu ...')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addEmail(HOTEL_EMAIL, 'Email')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Email hotelu ...')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addTextArea(HOTEL_DESCRIPTION, 'Popis')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('placeholder', 'Zadejte popisek ...')
            ->setHtmlAttribute('style', 'margin-bottom:15px;')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $receptionists = $this->userRepository->getReceptionists();
        if (array_key_exists($this->getUser()->getId(), $receptionists)) {
            unset($receptionists[$this->getUser()->getId()]);
        }

        $form->addMultiSelect(HOTEL_RECEPTIONIST_ID, 'Recepční', $receptionists)
            ->setDefaultValue($this->hotel->findReceptionists())
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addMultiUpload(IMAGE_HOTEL_ID, 'Obrázky')
            ->setHtmlAttribute('class', 'btn btn-danger')
            ->setHtmlAttribute('style', 'margin-left:15px;margin-bottom:15px;');

        $form->addSubmit('save', 'Uložit hotel')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg btn-block')
            ->setHtmlAttribute('style', 'margin-left:15px;');

        $form->onSuccess[] = [$this, 'onHotelFormSuccess'];

        $form->setDefaults($this->hotel->getData());

        return $form;
    }


    /**
     * Callback pro uložení hotelu
     * @param Form $form
     */
    public function onHotelFormSuccess(Form $form): void
    {
        $values = $form->getValues(TRUE);
        try {
            $images = $values[IMAGE_HOTEL_ID];
            unset($values[IMAGE_HOTEL_ID]);
            $this->hotel->setReceptionistsToInsert($values[HOTEL_RECEPTIONIST_ID]);
            unset($values[HOTEL_RECEPTIONIST_ID]);

            $this->hotel->setOwner($this->loggedUser)
                ->setData($values);
            $this->hotelRepository->persist($this->hotel);

            /**
             * Obrázky se ukládájí až po persist, jelikož pro orientaci v souborovém systému je použito ID hotelu
             */
            $hotelImagesPath = HOTEL_IMAGES_FOLDER . $this->hotel->getId();
            FileSystem::createDir($hotelImagesPath);
            /** @var $image FileUpload */
            foreach ($images as $image) {
                $image->toImage();
                $imagePath = $hotelImagesPath . '/' . $image->getName();
                $image->move($imagePath);
                $this->hotel->addImage($imagePath);
            }
            $this->hotelRepository->persist($this->hotel);
            $this->flashMessage('Hotel úspěšně uložen', 'success');
            $this->redirect('Hotel:view', ['hotelId' => $this->hotel->getId()]);
        } catch (\PDOException $PDOexception) {
            \Tracy\Debugger::barDump($PDOexception);
            $form->addError('Při ukládání došlo k chybě');
        } catch (ImageException $imageException) {
            $form->addError('Prosím nahrávejte pouze obrázky');
        } catch (IOException $IOException) {
            $form->addError('Při nahrávání obrázků došlo k chybě');
        }
    }
}
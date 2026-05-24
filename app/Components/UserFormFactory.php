<?php


namespace HotelSystem\Components;


use App\Presenters\BasePresenter;
use HotelSystem\Model\Entity\User;
use HotelSystem\Model\Repository\UserRepository;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;

class UserFormFactory
{
    /** @var User */
    private $user;

    /** @var UserRepository */
    private $userRepository;

    /** @var BasePresenter */
    private $presenter;

    /** @var bool */
    private $isAdmin;

    /** @var Passwords */
    private $passwords;



    public function __construct(User $user, UserRepository $userRepository, BasePresenter $presenter, bool $isAdmin)
    {
        $this->user = $user;
        $this->userRepository = $userRepository;
        $this->presenter = $presenter;
        $this->isAdmin = $isAdmin;
        $this->passwords = new Passwords;
    }


    /**
     * Formulář pro registraci nebo editaci uživatele
     * @return Form
     */
    public function createUserForm(): Form
    {
        $form = new Form;

        $form->addText(USER_NAME, 'Jméno (*)')
            ->setRequired('Prosím vyplňte jméno')
            ->setHtmlAttribute('placeholder', 'Zadejte jméno ...')
            ->setHtmlAttribute(' size', '70')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');

        $form->addText(USER_SURNAME, 'Příjmení (*)')
            ->setRequired('Prosím vyplňte příjmení')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;')
            ->setHtmlAttribute('placeholder', 'Zadejte příjmení ...');

        $form->addText(USER_PHONE, 'Telefon')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;')
            ->setHtmlAttribute('placeholder', 'Zadejte telefonní číslo ...');

        $form->addEmail(USER_EMAIL, 'Email (*)')
            ->setRequired('Prosím vyplňte email')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;')
            ->setHtmlAttribute('placeholder', 'Zadejte email ...');

        $form->addText(USER_LOGIN, 'Login (*)')
            ->setRequired('Prosím vyplňte login')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;')
            ->setHtmlAttribute('placeholder', 'Zadejte login ...');

        $password = $form->addPassword(USER_PASSWORD, 'Heslo (*)')
            ->setHtmlAttribute('class', 'form-control form-control-lg')
            ->setHtmlAttribute('style', 'margin-bottom:30px;margin-left:15px;')
             ->setHtmlAttribute('placeholder', 'Zadejte heslo ...');
        if ($this->user->isNew()) {
            $password->setRequired('Prosím zvolte si heslo');
        }

        if ($this->isAdmin) {
            $roles = $this->userRepository->getDatabase()->table(TABLE_ROLES)->fetchPairs(ROLE_ID, ROLE_NAME);
            $form->addSelect(ROLE_ID, 'Oprávnění', $roles)
                ->setDefaultValue($this->user->getHighestRole())
                ->setHtmlAttribute('placeholder', 'Zadejte oprávnění ...')
                ->setHtmlAttribute('class', 'form-control form-control-lg')
                ->setHtmlAttribute('style', 'margin-bottom:30px;margin-left:15px;');
        }

        $form->addSubmit('send', $this->user->isNew() ? 'Registrovat' : 'Uložit změny')
            ->setHtmlAttribute('class', 'btn btn-dark btn-lg btn-block')
            ->setHtmlAttribute('style', 'margin-left:15px;');

        $defaults = $this->user->getData();
        unset($defaults[USER_PASSWORD]);
        $form->setDefaults($defaults);

        $form->onSuccess[] = [$this, 'onRegisterFormSuccess'];

        return $form;
    }



    public function onRegisterFormValidate(Form $form): void
    {
        $values = $form->getValues(TRUE);

        if ($this->userRepository->userWithLoginExists($values[USER_LOGIN])) {
            $form->addError('Vybraný login je již zabraný, zvolte prosím jiný');
        }
    }



    /**
     * Callback pro zpracování registračního formuláře, zahashuje uživateli heslo a pokusí se vytvořit uživatele
     * @param Form $form
     */
    public function onRegisterFormSuccess(Form $form): void
    {
        $values = $form->getValues(TRUE);
        if ($values[USER_PASSWORD]) {
            $values[USER_PASSWORD] = $this->passwords->hash($values[USER_PASSWORD]);
        } else {
            unset($values[USER_PASSWORD]);
        }

        if ($this->isAdmin) {
            $this->user->setRolesToInsert($values[ROLE_ID]);
            unset($values[ROLE_ID]);
        } else {
            $this->user->setRolesToInsert($this->user->getHighestRole());
        }

        try {
            $this->user->setData($values);
            $this->userRepository->persist($this->user);
            if (!$this->user->isNew()) {
                $this->presenter->flashMessage('Editace uživatele proběhla úspěšně', 'success');
            } else {
                $this->presenter->flashMessage('Registrace proběhla úspěšně', 'success');
            }
            $this->presenter->redirect('this');
        } catch (\PDOException $exception) {
            \Tracy\Debugger::barDump($exception);
            if (!$this->user->isNew()) {
                $form->addError('Při editaci uživatele došlo k chybě');
            } else {
                $form->addError('Při registraci došlo k chybě');
            }
        }
    }
}
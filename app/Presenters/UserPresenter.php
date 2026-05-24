<?php


namespace App\Presenters;


use Grido\Components\Filters\Filter;
use Grido\Grid;
use Grido\Translations\FileTranslator;
use HotelSystem\Components\UserFormFactory;
use HotelSystem\Model\Entity\User;
use HotelSystem\Model\Repository\UserRepository;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Http\IResponse;
use Nette\Utils\Html;

class UserPresenter extends BasePresenter
{
    /** @var UserFormFactory */
    private $userFormFactory;

    /** @var User */
    private $viewedUser;



    public function actionDefault()
    {
        if (!$this->getUser()->isAllowed('user', 'overview')) {
            $this->error('Na tuto akci nemáte dostatečná práva', IResponse::S403_FORBIDDEN);
        }
    }



    public function actionEdit($userId = NULL, $clearForm = FALSE, $defaultValues = [])
    {
        $userToEdit = $clearForm
            ? $this->userRepository->createEntity()
            : ($userId
                ? $this->userRepository->getByID($userId)
                : $this->loggedUser);
        $userToEdit->setData($defaultValues);

        $this->userFormFactory = new UserFormFactory(
            $userToEdit,
            $this->userRepository,
            $this,
            $this->getUser()->isAllowed('user', 'edit')
        );
    }



    public function actionView($userId)
    {
        if (!$this->getUser()->isAllowed('user', 'view')) {
            $this->error('Na tuto akci nemáte dostatečná oprávnění', IResponse::S403_FORBIDDEN);
        }
        $this->viewedUser = $this->userRepository->getByID($userId);
    }



    public function renderView()
    {
        $this->template->userId = $this->viewedUser->getId();
        $this->template->userName = $this->viewedUser->getFullName();
        $this->template->email = $this->viewedUser->getEmailLink();
        $this->template->phone = $this->viewedUser->getPhone();
        $this->template->role = $this->viewedUser->getHighestRoleName();
    }


    /**
     * Registrační formulář
     * @return Form
     */
    protected function createComponentRegisterForm(): Form
    {
        return $this->userFormFactory->createUserForm();
    }



    protected function createComponentUserGrid(): Grid
    {
        $grid = new Grid($this, 'userGrid');
        $grid->setModel($this->userRepository->getTable())
            ->setPrimaryKey(USER_ID)
            ->setFilterRenderType(Filter::RENDER_INNER)
            ->setTranslator(new FileTranslator('cs'))
            ->setTemplateFile(__DIR__ . '/../../vendor/o5/grido/src/templates/bootstrap.latte');

        $grid->addColumnText(USER_NAME, 'Jméno')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText(USER_SURNAME, 'Příjmení')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText(USER_PHONE, 'Telefon')
            ->setFilterText();

        $grid->addColumnEmail(USER_EMAIL, 'Email')
            ->setFilterText();

        if ($this->getUser()->isAllowed('user', 'delete')) {
            $grid->addActionEvent('delete', '', function ($rowId) {
                if ($row = $this->userRepository->getTable()->get($rowId)) {
                    $row->delete();
                }
                $this->flashMessage('Uživatel úspěšně smazán', 'success');
            })
                ->setCustomRender(function (ActiveRow $row, Html $el) {
                    $el->class[] = 'btn btn-danger';
                    $el->style[] = 'margin: 5px;';
                    $el->title = 'Smazat';
                    return $el->addHtml(Html::el('span', ['class' => 'fa fa-trash']));
                });
        }

        if ($this->getUser()->isInRole(UserRepository::ROLE_ADMIN)) {
            $grid->addActionHref('edit', '')
                ->setCustomHref(function (ActiveRow $row) {
                    return $this->link('User:edit', ['userId' => $row[USER_ID]]);
                })
                ->setCustomRender(function (ActiveRow $row, Html $el) {
                    $el->class[] = 'btn btn-primary';
                    $el->style[] = 'margin: 5px;';
                    $el->title = 'Upravit';
                    return $el->addHtml(Html::el('span', ['class' => 'fa fa-edit']));
                });
        }

        $grid->addActionHref('view', '')
            ->setCustomHref(function (ActiveRow $row) {
                return $this->link('User:view', ['userId' => $row[USER_ID]]);
            })
            ->setCustomRender(function (ActiveRow $row, Html $el) {
                $el->class[] = 'btn';
                $el->style[] = 'margin: 5px;';
                $el->title = 'Zobrazit';
                return $el->addHtml(Html::el('span', ['class' => 'fa fa-eye']));
            });

        return $grid;
    }
}
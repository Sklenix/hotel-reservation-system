<?php


namespace HotelSystem\Model\Entity;


use HotelSystem\Model\Repository\BaseRepository;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Html;

class User extends BaseEntity
{
    /** @var array */
    private $rolesToInsert = [];



    public function __construct(BaseRepository $repository, ?ActiveRow $row = NULL)
    {
        parent::__construct($repository, $row);
        $this->idColumn = USER_ID;
    }


    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->get(USER_LOGIN);
    }


    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->get(USER_PASSWORD);
    }


    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->get(USER_NAME) . ' ' . $this->get(USER_SURNAME);
    }


    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->get(USER_EMAIL);
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
        return $this->get(USER_PHONE);
    }


    /**
     * @return array
     */
    public function getRoles(): array
    {
        $roleIds = $this->record->related(TABLE_USER_ROLES, USER_ID)->fetchPairs(USER_ROLE_ID, ROLE_ID);
        return $this->repository->getDatabase()->table(TABLE_ROLES)
            ->where(ROLE_ID, array_values($roleIds))
            ->order(ROLE_ID . ' DESC')
            ->fetchPairs(ROLE_ID, ROLE_NAME);
    }


    /**
     * @return int
     */
    public function getHighestRole(): int
    {
        if ($this->isNew()) {
            return $this->repository->getDatabase()->table(TABLE_ROLES)
                ->order(ROLE_ID)
                ->fetch()[ROLE_ID];
        }
        return max(array_keys($this->getRoles()));
    }


    /**
     * @return string
     */
    public function getHighestRoleName(): string
    {
        return $this->getRoles()[$this->getHighestRole()];
    }


    /**
     * Vloží do databáze zvolenou roli uživatele společně i s nižšími rolemi, než je role zvolená
     * @param int $role
     * @return $this
     */
    public function setRolesToInsert(int $role): User
    {
        for ($roleId = 1; $roleId <= $role; $roleId++) {
            $this->rolesToInsert[] = $roleId;
        }
        return $this;
    }


    /**
     * @return array
     */
    public function getRolesToInsert(): array
    {
        return $this->rolesToInsert;
    }
}
<?php


namespace HotelSystem\Model\Repository;


use HotelSystem\Model\Entity\User;
use HotelSystem\Utils\DatabaseUtils;
use Nette\Database\Context as NdbContext;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IAuthorizator;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use YetORM\Entity;

class UserRepository extends BaseRepository implements IAuthenticator, IAuthorizator
{
    /**
     * Seznam rolí
     */
    const
        ROLE_CUSTOMER = 'Customer',
        ROLE_CUSTOMER_ID = 1,
        ROLE_RECEPTIONIST = 'Receptionist',
        ROLE_RECEPTIONIST_ID = 2,
        ROLE_OWNER = 'Owner',
        ROLE_OWNER_ID = 3,
        ROLE_ADMIN = 'Admin',
        ROLE_ADMIN_ID = 4;


    /** @var Passwords */
    private $passwords;



    public function __construct(NdbContext $database, Passwords $passwords)
    {
        parent::__construct($database);
        $this->entity = 'HotelSystem\Model\Entity\User';
        $this->table = TABLE_USERS;
        $this->passwords = $passwords;
    }


    /**
     * Implementace funkce pro autentizaci a přihlášení uživatele
     * @param array $credentials
     * @return IIdentity
     * @throws AuthenticationException
     */
    public function authenticate(array $credentials): IIdentity
    {
        list($login, $password) = $credentials;
        $row = $this->getTable(TABLE_USERS)->where(USER_LOGIN, $login)->fetch();

        if (!$row) {
            throw new AuthenticationException('Neplatný login');
        }
        if (!$this->passwords->verify($password, $row[USER_PASSWORD])) {
            throw new AuthenticationException('Špátné heslo');
        }
        /** @var $user User */
        $user = $this->createEntity($row);
        $array = $row->toArray();
        unset($array[USER_PASSWORD]);

        return new Identity($user->getId(), $user->getRoles(), $array);
    }


    /**
     * Implementace funkce isAllowed, určuje privilegia uživatelských rolí
     * @param string|null $role
     * @param string|null $resource
     * @param string|null $privilege
     * @return bool
     */
    public function isAllowed($role, $resource, $privilege): bool
    {
        if ($role === self::ROLE_ADMIN) {
            return TRUE;
        }

        if ($role === self::ROLE_OWNER) {
            if ($resource === 'hotel') {
                return TRUE;
            }
            if ($resource === 'room') {
                return TRUE;
            }
        }

        if ($role === self::ROLE_RECEPTIONIST || $role === self::ROLE_OWNER) {
            if ($resource === 'reservation') {
                return TRUE;
            }
            if ($resource === 'user' && $privilege === 'overview') {
                return TRUE;
            }
            if ($resource === 'user' && $privilege === 'view') {
                return TRUE;
            }
        }

        return FALSE;
    }


    /**
     * Override persist kvůli vložení oprávnění do mezitabulky
     * @param Entity $entity
     * @return bool|void
     */
    public function persist(Entity $entity)
    {
        /** @var $entity User */
        $this->transaction(function () use ($entity) {
            parent::persist($entity);

            /**
             * Nejdříve se podíváme, zda uživateli nemáme nějaké oprávnění smazat (snižujeme mu oprávnění)
             */
            foreach ($entity->getRoles() as $roleId => $roleName) {
                if (!in_array($roleId, $entity->getRolesToInsert())) {
                    $this->getTable(TABLE_USER_ROLES)
                        ->where(USER_ID, $entity->getId())
                        ->where(ROLE_ID, $roleId)
                        ->delete();
                }
            }

            /**
             * Potom vložíme nová oprávnění
             */
            foreach ($entity->getRolesToInsert() as $role) {
                DatabaseUtils::insertOrUpdate($this->database, TABLE_USER_ROLES, [
                    USER_ID => $entity->getId(),
                    ROLE_ID => $role
                ], [
                    USER_ID => $entity->getId(),
                    ROLE_ID => $role
                ]);
            }
        });
    }


    /**
     * @return array
     */
    public function getReceptionists(): array
    {
        return $this->getTable()
            ->select(USER_ID . ', CONCAT_WS(" ", ' . USER_NAME . ', ' . USER_SURNAME . ') AS full_name')
            ->where(USER_ID . ' IN ('
                . ' SELECT ' . USER_ID
                . ' FROM '   . TABLE_USER_ROLES
                . ' WHERE '  . ROLE_ID . ' = ?)', self::ROLE_RECEPTIONIST_ID)
            ->fetchPairs(USER_ID, 'full_name');
    }



    public function userWithLoginExists(string $login): bool
    {
        return $this->getTable()->where(USER_LOGIN, $login)
            ->count('*') > 0;
    }
}
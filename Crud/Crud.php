<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 17.06.2020
 * Time: 21:04
 */

namespace Igoooor\ApiBundle\Crud;

use Igoooor\ApiBundle\Crud\ListWrapper\GenericCrudWrapper;
use Igoooor\ApiBundle\Repository\AbstractRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Crud
 */
class Crud
{
    /**
     * @var string
     */
    private string $entityFqcn;
    /**
     * @var array
     */
    private array $methods = [
        'list' => true,
        'new' => true,
        'detail' => true,
        'update' => true,
        'delete' => true,
    ];
    /**
     * @var string[string|null]
     */
    private array $roles = [
        'list' => null,
        'new' => null,
        'detail' => null,
        'update' => null,
        'delete' => null,
    ];
    /**
     * @var string
     */
    private string $serializerGroupsPrefix = '';
    /**
     * @var string[string|null]
     */
    private array $serializerGroups = [
        'list' => 'list',
        'new' => null,
        'detail' => 'detail',
        'update' => null,
        'delete' => null,
    ];
    /**
     * @var string
     */
    private string $defaultRole = 'PUBLIC_ACCESS';
    /**
     * @var string
     */
    private string $listWrapperFqcn = GenericCrudWrapper::class;
    /**
     * @var string
     */
    private string $newFormFqcn;
    /**
     * @var array
     */
    private array $newFormOptions = [];
    /**
     * @var string
     */
    private string $updateFormFqcn;
    /**
     * @var array
     */
    private array $updateFormOptions = [];
    /**
     * @var string
     */
    private string $idGetter = 'getId';
    /**
     * @var string
     */
    private string $softDeleteMethod = 'setDeletedAt';
    /**
     * @var bool
     */
    private bool $softDelete = false;
    /**
     * @var string|null
     */
    private ?string $findEntityRepositoryMethod = null;
    /**
     * @var string|null
     */
    private ?string $preCreatedEventFqcn = null;
    /**
     * @var string|null
     */
    private ?string $preSubmitUpdatedEventFqcn = null;
    /**
     * @var string|null
     */
    private ?string $preUpdatedEventFqcn = null;
    /**
     * @var string|null
     */
    private ?string $preDeletedEventFqcn = null;
    /**
     * @var string|null
     */
    private ?string $postCreatedEventFqcn = null;
    /**
     * @var string|null
     */
    private ?string $postUpdatedEventFqcn = null;
    /**
     * @var string|null
     */
    private ?string $postDeletedEventFqcn = null;
    /**
     * @var int
     */
    private int $maxPerPage = 0;
    /**
     * @var int
     */
    private int $defaultPerPage = 0;

    /**
     * @return string
     */
    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    /**
     * @param string $entityFqcn
     *
     * @return Crud
     */
    public function setEntityFqcn(string $entityFqcn): Crud
    {
        $this->entityFqcn = $entityFqcn;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function isMethodEnabled(string $method): bool
    {
        return $this->methods[$method];
    }

    /**
     * disable list endpoint
     *
     * @return Crud
     */
    public function disableList(): Crud
    {
        $this->methods['list'] = false;

        return $this;
    }

    /**
     * disable new endpoint
     *
     * @return Crud
     */
    public function disableNew(): Crud
    {
        $this->methods['new'] = false;

        return $this;
    }

    /**
     * disable detail endpoint
     *
     * @return Crud
     */
    public function disableDetail(): Crud
    {
        $this->methods['detail'] = false;

        return $this;
    }

    /**
     * disable update endpoint
     *
     * @return Crud
     */
    public function disableUpdate(): Crud
    {
        $this->methods['update'] = false;

        return $this;
    }

    /**
     * disable delete endpoint
     *
     * @return Crud
     */
    public function disableDelete(): Crud
    {
        $this->methods['delete'] = false;

        return $this;
    }

    /**
     * @param string $defaultRole
     *
     * @return Crud
     */
    public function setDefaultRole(string $defaultRole): Crud
    {
        $this->defaultRole = $defaultRole;

        return $this;
    }

    /**
     * @param string $crudAction
     *
     * @return string|null
     */
    public function getSerializerGroup(string $crudAction): ?string
    {
        if (!array_key_exists($crudAction, $this->serializerGroups)) {
            return null;
        }

        return sprintf('%s%s', $this->serializerGroupsPrefix, $this->serializerGroups[$crudAction]);
    }

    /**
     * @param string $crudAction
     * @param string $serializerGroup
     *
     * @return Crud
     */
    public function serializerGroup(string $crudAction, string $serializerGroup): Crud
    {
        $this->serializerGroups[$crudAction] = $serializerGroup;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    public function getRole(string $method): string
    {
        return $this->roles[$method] ?? $this->defaultRole;
    }

    /**
     * @param string $role
     *
     * @return Crud
     */
    public function listRole(string $role): Crud
    {
        $this->roles['list'] = $role;

        return $this;
    }

    /**
     * @param string $role
     *
     * @return Crud
     */
    public function newRole(string $role): Crud
    {
        $this->roles['new'] = $role;

        return $this;
    }

    /**
     * @param string $role
     *
     * @return Crud
     */
    public function detailRole(string $role): Crud
    {
        $this->roles['detail'] = $role;

        return $this;
    }

    /**
     * @param string $role
     *
     * @return Crud
     */
    public function updateRole(string $role): Crud
    {
        $this->roles['update'] = $role;

        return $this;
    }

    /**
     * @param string $role
     *
     * @return Crud
     */
    public function deleteRole(string $role): Crud
    {
        $this->roles['delete'] = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getListWrapperFqcn(): string
    {
        return $this->listWrapperFqcn;
    }

    /**
     * @param string $listWrapperFqcn
     *
     * @return Crud
     */
    public function setListWrapperFqcn(string $listWrapperFqcn): Crud
    {
        $this->listWrapperFqcn = $listWrapperFqcn;

        return $this;
    }

    /**
     * @return string
     */
    public function getNewFormFqcn(): string
    {
        return $this->newFormFqcn;
    }

    /**
     * @param string $newFormFqcn
     *
     * @return Crud
     */
    public function setNewFormFqcn(string $newFormFqcn): Crud
    {
        $this->newFormFqcn = $newFormFqcn;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpdateFormFqcn(): string
    {
        return $this->updateFormFqcn;
    }

    /**
     * @param string $updateFormFqcn
     *
     * @return Crud
     */
    public function setUpdateFormFqcn(string $updateFormFqcn): Crud
    {
        $this->updateFormFqcn = $updateFormFqcn;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdGetter(): string
    {
        return $this->idGetter;
    }

    /**
     * @param string $idGetter
     *
     * @return Crud
     */
    public function setIdGetter(string $idGetter): Crud
    {
        $this->idGetter = $idGetter;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreCreatedEventFqcn(): ?string
    {
        return $this->preCreatedEventFqcn;
    }

    /**
     * @param string $preCreatedEventFqcn
     *
     * @return Crud
     */
    public function setPreCreatedEventFqcn(string $preCreatedEventFqcn): Crud
    {
        $this->preCreatedEventFqcn = $preCreatedEventFqcn;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreUpdatedEventFqcn(): ?string
    {
        return $this->preUpdatedEventFqcn;
    }

    /**
     * @param string $preUpdatedEventFqcn
     *
     * @return Crud
     */
    public function setPreUpdatedEventFqcn(string $preUpdatedEventFqcn): Crud
    {
        $this->preUpdatedEventFqcn = $preUpdatedEventFqcn;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreSubmitUpdatedEventFqcn(): ?string
    {
        return $this->preSubmitUpdatedEventFqcn;
    }

    /**
     * @param string|null $preSubmitUpdatedEventFqcn
     *
     * @return Crud
     */
    public function setPreSubmitUpdatedEventFqcn(?string $preSubmitUpdatedEventFqcn): Crud
    {
        $this->preSubmitUpdatedEventFqcn = $preSubmitUpdatedEventFqcn;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreDeletedEventFqcn(): ?string
    {
        return $this->preDeletedEventFqcn;
    }

    /**
     * @param string $preDeletedEventFqcn
     *
     * @return Crud
     */
    public function setPreDeletedEventFqcn(string $preDeletedEventFqcn): Crud
    {
        $this->preDeletedEventFqcn = $preDeletedEventFqcn;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostCreatedEventFqcn(): ?string
    {
        return $this->postCreatedEventFqcn;
    }

    /**
     * @param string $postCreatedEventFqcn
     *
     * @return Crud
     */
    public function setPostCreatedEventFqcn(string $postCreatedEventFqcn): Crud
    {
        $this->postCreatedEventFqcn = $postCreatedEventFqcn;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostUpdatedEventFqcn(): ?string
    {
        return $this->postUpdatedEventFqcn;
    }

    /**
     * @param string $postUpdatedEventFqcn
     *
     * @return Crud
     */
    public function setPostUpdatedEventFqcn(string $postUpdatedEventFqcn): Crud
    {
        $this->postUpdatedEventFqcn = $postUpdatedEventFqcn;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostDeletedEventFqcn(): ?string
    {
        return $this->postDeletedEventFqcn;
    }

    /**
     * @param string $postDeletedEventFqcn
     *
     * @return Crud
     */
    public function setPostDeletedEventFqcn(string $postDeletedEventFqcn): Crud
    {
        $this->postDeletedEventFqcn = $postDeletedEventFqcn;

        return $this;
    }

    /**
     * @return array
     */
    public function getNewFormOptions(): array
    {
        return $this->newFormOptions;
    }

    /**
     * @param array $newFormOptions
     *
     * @return Crud
     */
    public function setNewFormOptions(array $newFormOptions): Crud
    {
        $this->newFormOptions = $newFormOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getUpdateFormOptions(): array
    {
        return $this->updateFormOptions;
    }

    /**
     * @param array $updateFormOptions
     *
     * @return Crud
     */
    public function setUpdateFormOptions(array $updateFormOptions): Crud
    {
        $this->updateFormOptions = $updateFormOptions;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFindEntityRepositoryMethod(): ?string
    {
        return $this->findEntityRepositoryMethod;
    }

    /**
     * @param string $findEntityRepositoryMethod
     *
     * @return Crud
     */
    public function setFindEntityRepositoryMethod(string $findEntityRepositoryMethod): Crud
    {
        $this->findEntityRepositoryMethod = $findEntityRepositoryMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getSoftDeleteMethod(): string
    {
        return $this->softDeleteMethod;
    }

    /**
     * @param string $softDeleteMethod
     *
     * @return Crud
     */
    public function setSoftDeleteMethod(string $softDeleteMethod): Crud
    {
        $this->softDeleteMethod = $softDeleteMethod;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSoftDelete(): bool
    {
        return $this->softDelete;
    }

    /**
     * @return Crud
     */
    public function softDelete(): Crud
    {
        $this->softDelete = true;

        return $this;
    }

    /**
     * @param int $defaultPerPage
     * @param int $maxPerPage
     *
     * @return Crud
     */
    public function pagination(int $defaultPerPage = 0, int $maxPerPage = 0): Crud
    {
        $this->defaultPerPage = $defaultPerPage;
        $this->maxPerPage = $maxPerPage;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxPerPage(): int
    {
        return $this->maxPerPage;
    }

    /**
     * @return int
     */
    public function getDefaultPerPage(): int
    {
        return $this->defaultPerPage;
    }

    /**
     * @param string $serializerGroupsPrefix
     *
     * @return Crud
     */
    public function setSerializerGroupsPrefix(string $serializerGroupsPrefix): Crud
    {
        $this->serializerGroupsPrefix = $serializerGroupsPrefix;

        return $this;
    }
}

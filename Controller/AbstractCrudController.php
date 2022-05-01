<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 18.06.2020
 * Time: 07:47
 */

namespace Igoooor\ApiBundle\Controller;

use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Igoooor\ApiBundle\Crud\Crud;
use Igoooor\ApiBundle\Crud\Exception\CrudMethodNotAllowedHttpException;
use Igoooor\ApiBundle\Crud\Exception\InvalidCrudRepositoryException;
use Igoooor\ApiBundle\Event\CrudEventInterface;
use Igoooor\ApiBundle\Exception\InvalidApiResponseException;
use Igoooor\ApiBundle\Repository\AbstractRepository;
use Igoooor\ApiBundle\Response\ApiResponseFactoryInterface;
use Igoooor\ApiBundle\Response\ApiResponseInterface;
use Igoooor\ApiBundle\Crud\ListWrapper\CrudWrapperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class AbstractCrudController
 */
abstract class AbstractCrudController extends AbstractController
{
    /**
     * @var Crud
     */
    private Crud $crud;
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $registry;

    /**
     * AbstractCrudController constructor.
     *
     * @param ApiResponseFactoryInterface   $apiResponseFactory
     * @param RouterInterface               $router
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param FormFactoryInterface          $formFactory
     * @param ManagerRegistry               $registry
     */
    public function __construct(ApiResponseFactoryInterface $apiResponseFactory, RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage, FormFactoryInterface $formFactory, ManagerRegistry $registry)
    {
        parent::__construct($apiResponseFactory, $router, $authorizationChecker, $tokenStorage, $formFactory);
        $this->registry = $registry;
        $this->crud = $this->configureCrud(new Crud());
    }

    /**
     * @Route("/", name="_index", methods={"GET"})
     *
     * @param Request $request
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     * @throws InvalidCrudRepositoryException
     */
    public function index(Request $request): ApiResponseInterface
    {
        $this->denyAccessUnlessGranted('list');
        $data = $this->findAll($request, $this->getRepository());
        $dataMeta = $this->processPagination($data, $request, $this->crud);
        /** @var CrudWrapperInterface $listWrapperFqcn */
        $listWrapperFqcn = $this->crud->getListWrapperFqcn();
        $meta = array_merge($dataMeta['meta'], ['serializerGroups' => $this->getSerializerGroups($request, 'list')]);

        return $this->createResponse(
            new $listWrapperFqcn($dataMeta['data']),
            $meta
        );
    }

    /**
     * @Route("/new", name="_new", methods={"GET", "POST"})
     *
     * @param Request                  $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return ApiResponseInterface
     *
     * @throws ORMException
     * @throws InvalidApiResponseException
     * @throws InvalidCrudRepositoryException
     */
    public function new(Request $request, EventDispatcherInterface $eventDispatcher): ApiResponseInterface
    {
        $this->denyAccessUnlessGranted('new');
        $form = $this->createNewForm($request, $this->crud);
        if ($request->isMethod(Request::METHOD_POST)) {
            $this->processForm($request, $form);
            if (!$form->isSubmitted()) {
                $form->submit(null, false);

                return $this->createFormValidationErrorResponse($form);
            }
            if (!$form->isValid()) {
                return $this->createFormValidationErrorResponse($form);
            }
            $entity = $form->getData();
            $preEventResponse = $this->dispatchEvent($eventDispatcher, $this->crud->getPreCreatedEventFqcn(), $entity, $request, $form);
            if (null !== $preEventResponse) {
                return $preEventResponse;
            }
            $this->persistEntity($entity);
            $postEventResponse = $this->dispatchEvent($eventDispatcher, $this->crud->getPostCreatedEventFqcn(), $entity, $request, $form);
            if (null !== $postEventResponse) {
                return $postEventResponse;
            }
            $idGetter = $this->crud->getIdGetter();

            return $this->createResponse([
                'id' => $entity->$idGetter(),
            ]);
        }

        return $this->createResponse($form);
    }

    /**
     * @Route("/{entityId}", name="_detail", methods={"GET"})
     *
     * @param Request $request
     * @param string  $entityId
     *
     * @return ApiResponseInterface
     *
     * @throws InvalidApiResponseException
     * @throws InvalidCrudRepositoryException
     */
    public function detail(Request $request, string $entityId): ApiResponseInterface
    {
        $this->denyAccessUnlessGranted('detail');
        $entity = $this->getEntity($entityId);
        if (!$this->isEntityAccessible($entity)) {
            throw $this->createAccessDeniedException();
        }

        return $this->createResponse($entity, ['serializerGroups' => $this->getSerializerGroups($request, 'detail')]);
    }

    /**
     * @Route("/{entityId}", name="_update", methods={"PUT"})
     *
     * @param string                   $entityId
     * @param Request                  $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return ApiResponseInterface
     *
     * @throws ORMException
     * @throws InvalidApiResponseException
     * @throws InvalidCrudRepositoryException
     */
    public function update(string $entityId, Request $request, EventDispatcherInterface $eventDispatcher): ApiResponseInterface
    {
        $this->denyAccessUnlessGranted('update');
        $entity = $this->getEntity($entityId);
        if (!$this->isEntityAccessible($entity)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createUpdateForm($entity, $request, $this->crud);
        $preSubmitUpdatedResponse = $this->dispatchEvent($eventDispatcher, $this->crud->getPreSubmitUpdatedEventFqcn(), $entity, $request, $form);
        if (null !== $preSubmitUpdatedResponse) {
            return $preSubmitUpdatedResponse;
        }
        $this->processForm($request, $form);
        if (!$form->isSubmitted()) {
            $form->submit(null, false);

            return $this->createFormValidationErrorResponse($form);
        }
        if (!$form->isValid()) {
            return $this->createFormValidationErrorResponse($form);
        }
        $preEventResponse = $this->dispatchEvent($eventDispatcher, $this->crud->getPreUpdatedEventFqcn(), $entity, $request, $form);
        if (null !== $preEventResponse) {
            return $preEventResponse;
        }
        $this->updateEntity($entity);
        $postEventResponse = $this->dispatchEvent($eventDispatcher, $this->crud->getPostUpdatedEventFqcn(), $entity, $request, $form);
        if (null !== $postEventResponse) {
            return $postEventResponse;
        }

        return $this->createResponse();
    }

    /**
     * @Route("/{entityId}", name="_delete", methods={"DELETE"})
     *
     * @param string                   $entityId
     * @param EventDispatcherInterface $eventDispatcher
     * @param Request                  $request
     *
     * @return ApiResponseInterface
     *
     * @throws ORMException
     * @throws InvalidApiResponseException
     * @throws InvalidCrudRepositoryException
     */
    public function delete(string $entityId, EventDispatcherInterface $eventDispatcher, Request $request): ApiResponseInterface
    {
        $this->denyAccessUnlessGranted('delete');
        $entity = $this->getEntity($entityId);
        if (!$this->isEntityAccessible($entity)) {
            throw $this->createAccessDeniedException();
        }

        $preEventResponse = $this->dispatchEvent($eventDispatcher, $this->crud->getPreDeletedEventFqcn(), $entity, $request);
        if (null !== $preEventResponse) {
            return $preEventResponse;
        }
        $this->deleteEntity($entity, $this->crud);
        $postEventResponse = $this->dispatchEvent($eventDispatcher, $this->crud->getPostDeletedEventFqcn(), $entity, $request);
        if (null !== $postEventResponse) {
            return $postEventResponse;
        }

        return $this->createResponse();
    }

    /**
     * Configure CRUD options
     *
     * @param Crud $crud
     *
     * @return Crud
     */
    abstract protected function configureCrud(Crud $crud): Crud;

    /**
     * @param string $entityFqcn
     *
     * @return object
     */
    protected function createEntity(string $entityFqcn): object
    {
        return new $entityFqcn();
    }

    /**
     * @param mixed $entity
     * @param Crud  $crud
     *
     * @throws ORMException
     * @throws InvalidCrudRepositoryException
     */
    protected function deleteEntity($entity, Crud $crud): void
    {
        if (!$crud->isSoftDelete()) {
            $this->getRepository()->remove($entity);

            return;
        }

        $this->softDeleteEntity($entity, $crud);
    }

    /**
     * @param mixed $entity
     * @param Crud  $crud
     *
     * @throws InvalidCrudRepositoryException
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function softDeleteEntity($entity, Crud $crud): void
    {
        $softDeleteMethod = $crud->getSoftDeleteMethod();
        $entity->$softDeleteMethod(new \DateTime());
        $this->getRepository()->flush();
    }

    /**
     * @param mixed $entity
     *
     * @throws ORMException
     * @throws InvalidCrudRepositoryException
     */
    protected function persistEntity($entity): void
    {
        $this->getRepository()->persist($entity);
    }

    /**
     * @param mixed $entity
     *
     * @throws ORMException
     * @throws InvalidCrudRepositoryException
     */
    protected function updateEntity($entity): void
    {
        $this->getRepository()->persist($entity);
    }

    /**
     * @param Request $request
     * @param Crud    $crud
     *
     * @return FormInterface
     */
    protected function createNewForm(Request $request, Crud $crud): FormInterface
    {
        $entity = $this->createEntity($crud->getEntityFqcn());

        return $this->createForm($crud->getNewFormFqcn(), $entity, $this->getNewFormOptions($crud, $request));
    }

    /**
     * @param Crud    $crud
     * @param Request $request
     *
     * @return array
     */
    protected function getNewFormOptions(Crud $crud, Request $request): array
    {
        return $crud->getNewFormOptions();
    }

    /**
     * @param mixed   $entity
     * @param Request $request
     * @param Crud    $crud
     *
     * @return FormInterface
     */
    protected function createUpdateForm($entity, Request $request, Crud $crud): FormInterface
    {
        return $this->createForm($crud->getUpdateFormFqcn(), $entity, $this->getUpdateFormOptions($crud, $request));
    }

    /**
     * @param Crud    $crud
     * @param Request $request
     *
     * @return array
     */
    protected function getUpdateFormOptions(Crud $crud, Request $request): array
    {
        return $crud->getUpdateFormOptions();
    }

    /**
     * @return AbstractRepository
     *
     * @throws InvalidCrudRepositoryException
     */
    protected function getRepository(): AbstractRepository
    {
        $repository = $this->registry
            ->getManagerForClass($this->crud->getEntityFqcn())
            ->getRepository($this->crud->getEntityFqcn());

        if (!$repository instanceof AbstractRepository) {
            throw new InvalidCrudRepositoryException();
        }

        return $repository;
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    protected function isEntityAccessible($entity): bool
    {
        return true;
    }

    /**
     * @param Request $request
     * @param string  $crudAction
     *
     * @return array
     */
    protected function getSerializerGroups(Request $request, string $crudAction): array
    {
        return [
            $this->crud->getSerializerGroup($crudAction),
            'any',
        ];
    }

    /**
     * @param Request            $request
     * @param AbstractRepository $repository
     *
     * @return array|QueryBuilder
     *
      @throws InvalidCrudRepositoryException
     */
    protected function findAll(Request $request, AbstractRepository $repository)
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @param array|QueryBuilder $data
     *
     * @return array
     */
    protected function processPagination($data, Request $request, Crud $crud): array
    {
        if (is_array($data)) {
            return [
                'data' => $data,
                'meta' => [
                    'total' => count($data),
                    'count' => count($data),
                    'totalPages' => 1,
                ],
            ];
        }

        $perPage = $request->query->get('per_page', $crud->getDefaultPerPage());
        if ($perPage > $crud->getMaxPerPage() && 0 !== $crud->getMaxPerPage()) {
            $perPage = $crud->getMaxPerPage();
        }

        if (0 === $perPage) {
            $results = $data->getQuery()->execute();

            return [
                'data' => $results,
                'meta' => [
                    'total' => count($results),
                    'count' => count($results),
                    'totalPages' => 1,
                ],
            ];
        }

        $page = $request->query->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $firstResult = ($page - 1) * $perPage;
        $qb = clone $data;
        $results = $qb->setMaxResults($perPage)
            ->setFirstResult($firstResult)
            ->getQuery()->execute();

        $rootAlias = $data->getRootAliases();
        $total = count($data->select(sprintf('%s.id', $rootAlias[0]))->getQuery()->execute());

        return [
            'data' => $results,
            'meta' => [
                'total' => $total,
                'count' => count($results),
                'totalPages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * @param Crud   $crud
     * @param string $entityId
     *
     * @return object|null
     *
     * @throws InvalidCrudRepositoryException
     */
    protected function findEntity(Crud $crud, string $entityId): ?object
    {
        $findMethod = $crud->getFindEntityRepositoryMethod();
        if (null === $findMethod) {
            if ($crud->isSoftDelete()) {
                return $this->getRepository()->createQueryBuilder('e')
                    ->where('e.id = :ID')
                    ->andWhere('e.deletedAt IS NULL')
                    ->setParameter('ID', $entityId)
                    ->setMaxResults(1)
                    ->getQuery()->getOneOrNullResult();
            }
            $findMethod = 'find';
        }

        return $this->getRepository()->$findMethod($entityId);
    }

    /**
     * @param string $entityId
     *
     * @return object
     *
     * @throws InvalidCrudRepositoryException
     */
    private function getEntity(string $entityId): object
    {
        $entity = $this->findEntity($this->crud, $entityId);
        if (null === $entity) {
            throw $this->createNotFoundException(sprintf(
                '%s object not found.',
                $this->crud->getEntityFqcn()
            ));
        }

        return $entity;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null              $eventFqcn
     * @param object                   $entity
     * @param Request                  $request
     * @param FormInterface|null       $form
     *
     * @return ApiResponseInterface|null
     */
    private function dispatchEvent(EventDispatcherInterface $eventDispatcher, ?string $eventFqcn, object  $entity, Request $request, ?FormInterface $form = null): ?ApiResponseInterface
    {
        if (null === $eventFqcn) {
            return null;
        }

        /** @var CrudEventInterface $event */
        $event = new $eventFqcn($entity);
        $event->setRequest($request);
        if (null !== $form) {
            $event->setForm($form);
        }
        $eventDispatcher->dispatch($event);

        if (!$event->isPropagationStopped()) {
            return null;
        }

        return $event->getResponse($this->getApiResponseFactory());
    }

    /**
     * @param string $method
     *
     * @throws CrudMethodNotAllowedHttpException
     * @throws AccessDeniedException
     */
    private function denyAccessUnlessGranted(string $method): void
    {
        if (!$this->crud->isMethodEnabled($method)) {
            throw new CrudMethodNotAllowedHttpException();
        }

        if (!$this->isGranted($this->crud->getRole($method))) {
            throw $this->createAccessDeniedException();
        }
    }
}

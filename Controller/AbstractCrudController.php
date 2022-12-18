<?php
/**
 * Created by PhpStorm.
 * User: igorweigel
 * Date: 18.06.2020
 * Time: 07:47
 */

namespace Igoooor\ApiBundle\Controller;

use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Igoooor\ApiBundle\Crud\Crud;
use Igoooor\ApiBundle\Crud\Exception\CrudMethodNotAllowedHttpException;
use Igoooor\ApiBundle\Crud\Exception\InvalidCrudRepositoryException;
use Igoooor\ApiBundle\Event\CrudEventInterface;
use Igoooor\ApiBundle\Repository\AbstractRepository;
use Igoooor\ApiBundle\Response\ApiResponseInterface;
use Igoooor\ApiBundle\Crud\ListWrapper\CrudWrapperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractCrudController extends AbstractController
{
    private Crud $crud;
    private ManagerRegistry $registry;

    #[Required]
    public function setRegistry(ManagerRegistry $registry): void
    {
        $this->registry = $registry;
    }

    public function __construct()
    {
        $this->crud = $this->configureCrud(new Crud());
    }

    #[Route('/', name: '.index', methods: ['GET'])]
    public function index(Request $request): ApiResponseInterface
    {
        $this->denyAccessUnlessGrantedMethod('list');
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

    #[Route('/new', name: '.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EventDispatcherInterface $eventDispatcher): ApiResponseInterface
    {
        $this->denyAccessUnlessGrantedMethod('new');
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

    #[Route('/{entityId}', name: '.detail', methods: ['GET'])]
    public function detail(Request $request, string $entityId): ApiResponseInterface
    {
        $this->denyAccessUnlessGrantedMethod('detail');
        $entity = $this->getEntity($entityId);
        if (!$this->isEntityAccessible($entity)) {
            throw $this->createAccessDeniedException();
        }

        return $this->createResponse($entity, ['serializerGroups' => $this->getSerializerGroups($request, 'detail')]);
    }

    #[Route('/{entityId}', name: '.update', methods: ['PUT'])]
    public function update(string $entityId, Request $request, EventDispatcherInterface $eventDispatcher): ApiResponseInterface
    {
        $this->denyAccessUnlessGrantedMethod('update');
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

    #[Route('/{entityId}', name: '.delete', methods: ['DELETE'])]
    public function delete(string $entityId, EventDispatcherInterface $eventDispatcher, Request $request): ApiResponseInterface
    {
        $this->denyAccessUnlessGrantedMethod('delete');
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

    abstract protected function configureCrud(Crud $crud): Crud;

    protected function createEntity(string $entityFqcn): object
    {
        return new $entityFqcn();
    }

    protected function deleteEntity(object $entity, Crud $crud): void
    {
        if (!$crud->isSoftDelete()) {
            $this->getRepository()->delete($entity);

            return;
        }

        $this->softDeleteEntity($entity, $crud);
    }

    protected function softDeleteEntity(object $entity, Crud $crud): void
    {
        $softDeleteMethod = $crud->getSoftDeleteMethod();
        $entity->$softDeleteMethod(new \DateTime());
        $this->getRepository()->flush();
    }

    protected function persistEntity(object $entity): void
    {
        $this->getRepository()->save($entity);
    }

    protected function updateEntity(object $entity): void
    {
        $this->getRepository()->save($entity);
    }

    protected function createNewForm(Request $request, Crud $crud): FormInterface
    {
        $entity = $this->createEntity($crud->getEntityFqcn());

        return $this->createForm($crud->getNewFormFqcn(), $entity, $this->getNewFormOptions($crud, $request));
    }

    protected function getNewFormOptions(Crud $crud, Request $request): array
    {
        return $crud->getNewFormOptions();
    }

    protected function createUpdateForm(object $entity, Request $request, Crud $crud): FormInterface
    {
        return $this->createForm($crud->getUpdateFormFqcn(), $entity, $this->getUpdateFormOptions($crud, $request));
    }

    protected function getUpdateFormOptions(Crud $crud, Request $request): array
    {
        return $crud->getUpdateFormOptions();
    }

    protected function getRepository(): AbstractRepository
    {
        $repository = $this->registry
            ->getManagerForClass($this->crud->getEntityFqcn())
            ?->getRepository($this->crud->getEntityFqcn());

        if (!$repository instanceof AbstractRepository) {
            throw new InvalidCrudRepositoryException();
        }

        return $repository;
    }

    protected function isEntityAccessible(object $entity): bool
    {
        return true;
    }

    protected function getSerializerGroups(Request $request, string $crudAction): array
    {
        return [
            $this->crud->getSerializerGroup($crudAction),
            'any',
        ];
    }

    protected function findAll(Request $request, AbstractRepository $repository)
    {
        return $this->getRepository()->findAll();
    }

    protected function processPagination(array|QueryBuilder $data, Request $request, Crud $crud): array
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
        $results = $qb->setMaxResults($perPage)->setFirstResult($firstResult)->getQuery()->execute();

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

    private function dispatchEvent(EventDispatcherInterface $eventDispatcher, ?string $eventFqcn, object $entity, Request $request, ?FormInterface $form = null): ?ApiResponseInterface
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

    private function denyAccessUnlessGrantedMethod(string $method): void
    {
        if (!$this->crud->isMethodEnabled($method)) {
            throw new CrudMethodNotAllowedHttpException();
        }

        if (!$this->isGranted($this->crud->getRole($method))) {
            throw $this->createAccessDeniedException();
        }
    }
}

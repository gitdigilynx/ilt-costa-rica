<?php

namespace App\Wicrew\CoreBundle\Controller\Admin;

use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\CoreBundle\Service\Cache;
use App\Wicrew\CoreBundle\Service\Mailer;
use App\Wicrew\CoreBundle\Service\Utils;
use App\Wicrew\CoreBundle\Service\Writer;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController as BaseAdminController;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use RuntimeException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * AdminController
 */
class AdminController extends BaseAdminController {
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    public const PRE_DUPLICATE = 'wicrew.pre_duplicate';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array {
        return parent::getSubscribedServices() + [
                'translator' => TranslatorInterface::class,
                'wicrew.core.utils' => Utils::class,
                'wicrew.core.security' => Utils::class,
                'wicrew.core.cache' => Cache::class,
                'wicrew.core.writer' => Writer::class,
                'wicrew.core.mailer' => Mailer::class,
                'kernel' => KernelInterface::class
//                'wicrew.core.mailchimp' => \App\Wicrew\CoreBundle\Service\Mailchimp::class
            ];
    }

    public function getEM(): ObjectManager {
        return $this->getDoctrine()->getManager();
    }

    /* @var TranslatorInterface $translator */
    private $translator = null;

    protected function translator(): ?TranslatorInterface {
        if ($this->translator === null) {
            if ($this->container !== null) {
                $this->translator = $this->container->get('translator');
            }
            $this->createNotFoundException();
        }

        return $this->translator;
    }

    /**
     * @param string id
     * @param string|null $default
     *
     * @return string|null
     */
    protected function getURLParameter(string $id, ?string $default = null): ?string {
        return $this->request->get($id, $default);
    }

    private function requestParameterBag(Request $request, string $key) {
        $result = $request->request->get($key, null);
        if ($result === null) {
            $result = $request->query->get($key, null);
        }
        if ($result === null) {
            $result = $request->attributes->get($key, null);
        }

        return $result;
    }

    public function getRequestDataNoThrow(Request $request, string $key, $defaultValue = null) {
        $result = $this->requestParameterBag($request, $key);
        if ($result === null) {
            $result = $defaultValue;
        }
        return $result;
    }

    /**
     * @param string $view
     * @param array $parameters
     *
     * @return string
     */
    public function renderTwigToString(string $view, array $parameters = []): string {
        $response = $this->render($view, $parameters);
        return $response->getContent();
    }

    public function returnSuccessResponse(array $parameters = []): JsonResponse {
        $data = ['status' => 'success'];
        $data = array_merge($data, $parameters);
        return new JsonResponse($data);
    }

    public function returnExceptionResponse(Throwable $e): JsonResponse {
        $this->logError($e);
        $data = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
        return new JsonResponse($data);
    }

    public function logError(Throwable $e): void {
        $now = new DateTime("now");
        $now = $now->format('Y-m-d H:i');
        $str = "[$now]: $e";
        file_put_contents($this->get('kernel')->getProjectDir() . '/var/log/error.log', $str);
    }

    /**
     * {@inheritdoc}
     */
    public function persistEntity($entity) {
        parent::persistEntity($entity);
        $this->addFlash('success', 'core.create.success');
    }

    /**
     * {@inheritdoc}
     */
    public function updateEntity($entity) {
        parent::updateEntity($entity);
        $this->addFlash('success', 'core.update.success');
    }

    /**
     * {@inheritdoc}
     */
    public function removeEntity($entity) {
        parent::removeEntity($entity);
        $this->addFlash('success', 'core.delete.success');
    }

    /**
     * {@inheritDoc}
     */
    protected function newAction() {
        $this->dispatch(EasyAdminEvents::PRE_NEW);

        $entity = $this->executeDynamicMethod('createNew<EntityName>Entity');

        $easyadmin = $this->request->attributes->get('easyadmin');
        $easyadmin['item'] = $entity;
        $this->request->attributes->set('easyadmin', $easyadmin);

        $fields = $this->entity['new']['fields'];

        /* @var Form $newForm */
        $newForm = $this->executeDynamicMethod('create<EntityName>NewForm', [$entity, $fields]);

        $newForm->handleRequest($this->request);
        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $flashMessage = '';
            $warning = false;

            $canSubmit = $this->onValidNewOrEditSubmit($entity, $flashMessage, $warning);
            if ($flashMessage !== '') {
                $this->addFlash($warning ? 'warning' : 'error', $flashMessage);
            }

            if ($canSubmit) {
                $this->processUploadedFiles($newForm);

                $this->dispatch(EasyAdminEvents::PRE_PERSIST, ['entity' => $entity]);
                $this->executeDynamicMethod('persist<EntityName>Entity', [$entity, $newForm]);
                $this->dispatch(EasyAdminEvents::POST_PERSIST, ['entity' => $entity]);

                return $this->redirectToReferrer();
            }
        }

        $this->dispatch(EasyAdminEvents::POST_NEW, [
            'entity_fields' => $fields,
            'form' => $newForm,
            'entity' => $entity,
        ]);

        $parameters = [
            'form' => $newForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['new', $this->entity['templates']['new'], $parameters]);
    }

    /**
     * {@inheritdoc}
     */
    public function editAction() {
        $this->dispatch(EasyAdminEvents::PRE_EDIT);

        $id = $this->request->query->get('id');
        $easyadmin = $this->request->attributes->get('easyadmin');
        $entity = $easyadmin['item'];

        if ($this->request->isXmlHttpRequest() && $property = $this->request->query->get('property')) {
            $newValue = 'true' === mb_strtolower($this->request->query->get('newValue'));
            $fieldsMetadata = $this->entity['list']['fields'];

            if (!isset($fieldsMetadata[$property]) || 'toggle' !== $fieldsMetadata[$property]['dataType']) {
                throw new RuntimeException(sprintf('The type of the "%s" property is not "toggle".', $property));
            }

            $this->updateEntityProperty($entity, $property, $newValue);

            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int)$newValue);
        }

        $fields = $this->entity['edit']['fields'];

        /* @var Form $editForm */
        $editForm = $this->executeDynamicMethod('create<EntityName>EditForm', [$entity, $fields]);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        $editForm->handleRequest($this->request);
        if ($editForm->isSubmitted() ) {
            $flashMessage = '';
            $warning = false;

            $canSubmit = $this->onValidNewOrEditSubmit($entity, $flashMessage, $warning);
            if ($flashMessage !== '') {
                $this->addFlash($warning ? 'warning' : 'error', $flashMessage);
            }

            if ($canSubmit) {
                $this->processUploadedFiles($editForm);

                $this->dispatch(EasyAdminEvents::PRE_UPDATE, ['entity' => $entity]);
                $this->executeDynamicMethod('update<EntityName>Entity', [$entity, $editForm]);
                $this->dispatch(EasyAdminEvents::POST_UPDATE, ['entity' => $entity]);

                return $this->redirectToReferrer();
            }
        }

        $this->dispatch(EasyAdminEvents::POST_EDIT);

        $parameters = [
            'form' => $editForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];

        return $this->executeDynamicMethod('render<EntityName>Template', ['edit', $this->entity['templates']['edit'], $parameters]);
    }

    /**
     * Executed when the entity is edited/create and its changes are about to be persisted.
     *
     * @param BaseEntity $entity
     * @param string $flashMessage
     * @param bool $warning
     *
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    protected function onValidNewOrEditSubmit(BaseEntity $entity, string &$flashMessage, bool &$warning): bool {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAction(): RedirectResponse {
        $this->dispatch(EasyAdminEvents::PRE_DELETE);

        if ('DELETE' !== $this->request->getMethod()) {
            return $this->redirect($this->generateUrl('easyadmin', ['action' => 'list', 'entity' => $this->entity['name']]));
        }

        $id = $this->request->query->get('id');
        $form = $this->createDeleteForm($this->entity['name'], $id);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $easyadmin = $this->request->attributes->get('easyadmin');
            $entity = $easyadmin['item'];

            $flashMessage = '';
            if ($this->onValidDeleteSubmit($entity, $flashMessage)) {

                $this->dispatch(EasyAdminEvents::PRE_REMOVE, ['entity' => $entity]);

                $this->executeDynamicMethod('remove<EntityName>Entity', [$entity, $form]);

                $this->dispatch(EasyAdminEvents::POST_REMOVE, ['entity' => $entity]);
            }

            if ($flashMessage !== '') {
                $this->addFlash('error', $flashMessage);
            }
        }

        $this->dispatch(EasyAdminEvents::POST_DELETE);

        return $this->redirectToReferrer();
    }

    /**
     * Executed when the entity is about to be deleted.
     *
     * @param BaseEntity $entity
     * @param string $flashMessage
     *
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    protected function onValidDeleteSubmit(BaseEntity $entity, string &$flashMessage): bool {
        return true;
    }

}

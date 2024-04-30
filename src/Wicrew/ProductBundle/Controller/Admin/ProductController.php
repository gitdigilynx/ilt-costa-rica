<?php

namespace App\Wicrew\ProductBundle\Controller\Admin;

use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\ProductBundle\Entity\Product;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Translation\TranslatorInterface;
use App\Wicrew\AddonBundle\Entity\Extra;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;

/**
 * ProductController
 */
class ProductController extends BaseAdminController {
    /**
     * Duplicate a pre-existing Product into a new database row.
     *
     * @return RedirectResponse
     */
    public function duplicateAction() {
        $this->dispatch(self::PRE_DUPLICATE);

        if ($this->request->isMethod('POST')) {
            return $this->newAction();
        }

        /** @var $translator TranslatorInterface */
        $translator = $this->get('translator');

        /* @var Product $original */
        $original = $this->em->getRepository(Product::class)->findOneBy(['id' => $this->request->query->get('id')]);

        // Copy over the original entity's data into the 'new' action form.
        $entity = clone $original;
        $fields = $this->entity['new']['fields'];
        $newForm = $this->createNewForm($entity, $fields);

        $parameters = [
            'form' => $newForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
        ];

        $message = $translator->trans('product.duplicate.successful');
        $this->addFlash('success', $message);

        return $this->executeDynamicMethod('render<EntityName>Template', ['new', $this->entity['templates']['new'], $parameters]);
    }

    protected function onValidNewOrEditSubmit(BaseEntity $entity, string &$flashMessage, bool &$warning): bool {
        assert($entity instanceof Product);

        if ($entity->getTransportationType()->getId() !== TransportationType::TYPE_SHARED_SHUTTLE && $entity->getDepartureTimes()->count() > 0) {
            $entity->setDepartureTimes(new ArrayCollection());
        }
 
        return true;
    }
 
    /**
     * archive a product
     *
     * @return RedirectResponse
     */
    public function archiveAction() {
        $product = $this->em->getRepository(Product::class)->findOneBy(['id' => $this->request->query->get('id')]);
        $product->setArchived(true);
        $this->em->persist($product);
        $this->em->flush();

        $flashMessage = 'Product #'. $product->getId() . ' archived successfully';

        $this->addFlash('success', $flashMessage);

        return $this->redirectToReferrer();
    }

    /**
     * unarchive a product
     *
     * @return RedirectResponse
     */
    public function unarchiveAction() {
        $product = $this->em->getRepository(Product::class)->findOneBy(['id' => $this->request->query->get('id')]);
        $product->setArchived(false);
        $this->em->persist($product);
        $this->em->flush();

        $flashMessage = 'Product #'. $product->getId() . ' unarchived successfully';

        $this->addFlash('success', $flashMessage);

        return $this->redirectToReferrer();
    }

    public function dispatch($eventName, array $arguments = []) {  
        // add the extra for "Private shuttle" if the option is checked
        if (
            in_array($eventName, [EasyAdminEvents::POST_UPDATE, EasyAdminEvents::POST_PERSIST]) && 
            isset($arguments['entity']) &&
            $arguments['entity'] instanceof Product
        ) {
            $entity = $arguments['entity'];
          
            if ($entity->getTransportationType()->getId() == TransportationType::TYPE_PRIVATE_SHUTTLE) {
                $em = $this->container->get('wicrew.core.utils')->getEntityManager();
                $extras = $em->getRepository(Extra::Class)->findByAddByDefault(true);   
                foreach ($extras as $extra) {
                    $entity->addExtra($extra);
                } 
                $em->persist($entity);
                $em->flush(); 
                
            }  
        } 
        parent::dispatch($eventName,$arguments); 
    }
}

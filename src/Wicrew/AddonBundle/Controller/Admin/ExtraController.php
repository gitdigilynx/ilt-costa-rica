<?php


namespace App\Wicrew\AddonBundle\Controller\Admin;


use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\SaleBundle\Entity\OrderItemHasExtra;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use App\Wicrew\ProductBundle\Entity\TransportationType;
use App\Wicrew\ProductBundle\Entity\Product;

class ExtraController extends BaseAdminController {
    /**
     * @param Extra $addon
     *
     * @return OrderItemHasExtra[]
     */
    private function getOrdersUsingExtra(Extra $addon): array {
        $em = $this->container->get('wicrew.core.utils')->getEntityManager();
        return $em->getRepository(OrderItemHasExtra::Class)->findBy(['extra' => $addon]);
    }

    /**
     * {@inheritDoc}
     */
    protected function onValidNewOrEditSubmit(BaseEntity $entity, string &$flashMessage, bool &$warning): bool {
        assert($entity instanceof Extra);
 
        if ($entity->getType() === Extra::TYPE_CHECKBOX && count($entity->getOptions()) > 0) {
            $entity->setOptions(new ArrayCollection());
            $flashMessage = $this->translator()->trans('addon.option.remove');
            $warning = true;
        }

        // // add the extra for "Private shuttle" if the option is checked
        // if ($entity->getAddByDefault() && $entity->getId()) {  
        //     $em = $this->container->get('wicrew.core.utils')->getEntityManager();
        //     $private_shuttle = $em->getRepository(TransportationType::class)->findOneById(TransportationType::TYPE_PRIVATE_SHUTTLE);
        //     $private_shuttle_products = $em->getRepository(Product::Class)->findBy(['transportationType' => $private_shuttle]);
        //     $private_shuttle_products = [ ];
      
        //     foreach($private_shuttle_products as $private_shuttle_product) {
        //         $extra = $em->getRepository(Extra::Class)->findOneById($entity->getId());
        //         $private_shuttle_product->addExtra($extra);
        //         $em->persist($private_shuttle_product);
        //     }
        //     $em->flush();
        // }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function onValidDeleteSubmit(BaseEntity $entity, string &$flashMessage): bool {
        assert($entity instanceof Extra);

        if (count($entity->getProducts()) > 0) {
            $urlParameters = [
                'entity' => 'Extra',
                'action' => 'view_products',
                'id' => $entity->getId()
            ];
            $path = $this->generateUrl('easyadmin', $urlParameters);
            $flashMessage = $this->translator()->trans('addon.delete.products');
            $flashMessage .= " <a href='$path'>" . $this->translator()->trans('addon.delete.products_link') . '</a>';

            return false;
        }

        if (count($this->getOrdersUsingExtra($entity)) > 0) {
            $urlParameters = [
                'entity' => 'Extra',
                'action' => 'view_orders',
                'id' => $entity->getId()
            ];
            $path = $this->generateUrl('easyadmin', $urlParameters);
            $flashMessage = $this->translator()->trans('addon.delete.orders');
            $flashMessage .= " <a href='$path'>" . $this->translator()->trans('addon.delete.orders_link') . '</a>';

            return false;
        }

        return true;
    }

    /**
     * @return Response
     * @noinspection PhpUnused
     */
    public function view_productsAction(): Response {
        /* @var EntityManager $em */
        $em = $this->container->get('wicrew.core.utils')->getEntityManager();

        /* @var Extra $entity */
        $entity = $em->getRepository(Extra::Class)->find($this->getURLParameter('id'));

        return $this->render('@WicrewAddon/Admin/IntegrityViewers/view_associated_products.html.twig', [
            'addon' => $entity
        ]);
    }

    /**
     * @return Response
     * @noinspection PhpUnused
     */
    public function view_ordersAction(): Response {
        /* @var EntityManager $em */
        $em = $this->container->get('wicrew.core.utils')->getEntityManager();

        /* @var Extra $entity */
        $entity = $em->getRepository(Extra::Class)->find($this->getURLParameter('id'));
        /* @var OrderItemHasExtra[] $orders */
        $orders = $em->getRepository(OrderItemHasExtra::Class)->findBy(['extra' => $entity]);

        return $this->render('@WicrewAddon/Admin/IntegrityViewers/view_associated_orders.html.twig', [
            'addon' => $entity,
            'orders' => $orders
        ]);
    }
}
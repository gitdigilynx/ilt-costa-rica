<?php


namespace App\Wicrew\AddonBundle\Controller\Admin;


use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\SaleBundle\Entity\OrderItemHasAddon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;

class AddonController extends BaseAdminController {
    /**
     * @param Addon $addon
     *
     * @return OrderItemHasAddon[]
     */
    private function getOrdersUsingAddon(Addon $addon): array {
        $em = $this->container->get('wicrew.core.utils')->getEntityManager();
        return $em->getRepository(OrderItemHasAddon::Class)->findBy(['addon' => $addon]);
    }

    /**
     * {@inheritDoc}
     */
    protected function onValidNewOrEditSubmit(BaseEntity $entity, string &$flashMessage, bool &$warning): bool {
        assert($entity instanceof Addon);

        if ($entity->getType() === Addon::TYPE_CHECKBOX && count($entity->getOptions()) > 0) {
            $entity->setOptions(new ArrayCollection());
            $flashMessage = $this->translator()->trans('addon.option.remove');
            $warning = true;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function onValidDeleteSubmit(BaseEntity $entity, string &$flashMessage): bool {
        assert($entity instanceof Addon);

        if (count($entity->getProducts()) > 0) {
            $urlParameters = [
                'entity' => 'Addon',
                'action' => 'view_products',
                'id' => $entity->getId()
            ];
            $path = $this->generateUrl('easyadmin', $urlParameters);
            $flashMessage = $this->translator()->trans('addon.delete.products');
            $flashMessage .= " <a href='$path'>" . $this->translator()->trans('addon.delete.products_link') . '</a>';

            return false;
        }

        if (count($this->getOrdersUsingAddon($entity)) > 0) {
            $urlParameters = [
                'entity' => 'Addon',
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

        /* @var Addon $entity */
        $entity = $em->getRepository(Addon::Class)->find($this->getURLParameter('id'));

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

        /* @var Addon $entity */
        $entity = $em->getRepository(Addon::Class)->find($this->getURLParameter('id'));
        /* @var OrderItemHasAddon[] $orders */
        $orders = $em->getRepository(OrderItemHasAddon::Class)->findBy(['addon' => $entity]);

        return $this->render('@WicrewAddon/Admin/IntegrityViewers/view_associated_orders.html.twig', [
            'addon' => $entity,
            'orders' => $orders
        ]);
    }
}
<?php

namespace App\Wicrew\PartnerBundle\Controller\Admin;

use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use App\Wicrew\CoreBundle\Entity\BaseEntity;
use App\Wicrew\PartnerBundle\Entity\Partner;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * PartnerController
 */
class PartnerController extends BaseAdminController {
    /**
     * @param Partner $entity
     *
     * @return Addon[]
     */
    private function getAddons(Partner $entity): array {
        /* @var EntityManager $em */
        $em = $this->container->get('wicrew.core.utils')->getEntityManager();
        return $em->getRepository(Addon::class)->createQueryBuilder('a')
            ->leftJoin('a.options', 'o')
            ->where('a.supplier = :partner')
            ->orWhere('o.supplier = :partner')
            ->setParameter('partner', $entity)
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritDoc}
     */
    protected function onValidDeleteSubmit(BaseEntity $entity, string &$flashMessage): bool {
        assert($entity instanceof Partner);

        $addons = $this->getAddons($entity);
        if (count($addons) > 0) {
            $urlParameters = [
                'entity' => 'Partner',
                'action' => 'view_addons',
                'id' => $entity->getId()
            ];
            $path = $this->generateUrl('easyadmin', $urlParameters);
            $flashMessage = $this->translator()->trans('partner.delete.addons');
            $flashMessage .= " <a href='$path'>" . $this->translator()->trans('partner.delete.addons_link') . '</a>';

            return false;
        }

        return true;
    }

    /**
     * @return Response
     */
    public function view_AddonsAction(): Response {
        /* @var EntityManager $em */
        $em = $this->container->get('wicrew.core.utils')->getEntityManager();

        /* @var Partner $entity */
        $entity = $em->getRepository(Partner::Class)->find($this->getRequestDataImplicit('id', false, null));

        $addons = $this->getAddons($entity);
        return $this->render('@WicrewPartner/Admin/view_addons.html.twig', [
            'partner' => $entity,
            'addons' => $addons
        ]);
    }
}

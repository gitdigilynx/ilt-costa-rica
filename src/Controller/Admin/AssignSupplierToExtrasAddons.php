<?php


namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use App\Wicrew\SaleBundle\Entity\OrderItemHasAddon;
use App\Wicrew\PartnerBundle\Entity\Partner;
use App\Wicrew\SaleBundle\Entity\OrderItemHasExtra;
use App\Wicrew\SaleBundle\Entity\TaxConfig;
use Symfony\Component\HttpFoundation\Request;
use DateTime;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use Throwable;

    
/**
 * Add Supplier to Extras/Addons
 */
class AssignSupplierToExtrasAddons extends BaseAdminController
{
    /**
     * @Route("admin/assign/supplier/addon", name="assignSupplierToAddon")
     * 
     * @param Request $request
     */
    public function assignSupplierToAddon(Request $request): JsonResponse
    {
        try {
            $em = $this->getEM();
            $em->beginTransaction();
            $item                       = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('id')]);
            $selected_addon             = $request->request->get('addon_id') ? $request->request->get('addon_id') : null;
            $selected_addon_supplier    = $request->request->get('addon_supplier') ? $request->request->get('addon_supplier') : null;
            $orderItem_addons           = $item->getAddons();

            if( $selected_addon != null && $selected_addon_supplier != null ){
                $selected_supplier       = $em->getRepository(Partner::class)->findOneBy([ 'id' => $selected_addon_supplier ]);

                foreach($orderItem_addons as $orderItem_addon){
                    $orderItem_addon_id = $orderItem_addon->getId();
                    
                    if( $orderItem_addon_id == $selected_addon ){
                        $orderItem_addon->setSupplier($selected_supplier);
                        $em->persist($orderItem_addon);
                        $em->persist($item);
                    }
                }
            }
            $em->flush();
            $em->commit();

            return new JsonResponse( json_encode([
                'status'   => "success",
                'message'  => "Supplier has been assigned to addon!",
            ]));

        } catch (Throwable $e) {
       
            $em->rollback();
            return new JsonResponse( json_encode([
                'status'   => "failed",
                'message'  => $e->getMessage(),
            ]));
        }

    }

     /**
     * @Route("admin/assign/supplier/extra", name="assignSupplierToExtra")
     * 
     * @param Request $request
     */
    public function assignSupplierExtra(Request $request): JsonResponse
    {
        try {
            $em = $this->getEM();
            $em->beginTransaction();
            $item                       = $em->getRepository(OrderItem::class)->findOneBy(['id' => $request->request->get('id')]);
            $selected_extra             = $request->request->get('extra_id') ? $request->request->get('extra_id') : null;
            $selected_extra_supplier    = $request->request->get('extra_supplier') ? $request->request->get('extra_supplier') : null;
            $orderItem_extras           = $item->getExtras();

            if( $selected_extra != null && $selected_extra_supplier != null ){
                $selected_supplier       = $em->getRepository(Partner::class)->findOneBy([ 'id' => $selected_extra_supplier ]);

                foreach( $orderItem_extras as $orderItem_extra ){
                    $orderItem_extra_id = $orderItem_extra->getId();
                    
                    if( $orderItem_extra_id == $selected_extra ){
                        $orderItem_extra->setSupplier($selected_supplier);
                        $em->persist( $orderItem_extra );
                        $em->persist( $item );
                    }
                }
            }
            $em->flush();
            $em->commit();

            return new JsonResponse( json_encode([
                'status'   => "success",
                'message'  => "Supplier has been assigned to extra!",
            ]));

        } catch (Throwable $e) {
       
            $em->rollback();
            return new JsonResponse( json_encode([
                'status'   => "failed",
                'message'  => $e->getMessage(),
            ]));
        }

    }
}

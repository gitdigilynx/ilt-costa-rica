<?php


namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use Throwable;

    
/**
 * Get Entities Through Ajax
 */
class GetEntitiesThroughAjax extends BaseAdminController
{
    /**
     * @Route("admin/get/entities", name="get_entities_through_ajax")
     */
    public function index(): JsonResponse
    {
    
        $em             = $this->getEM();
        $addons_loop    = $em->getRepository(Addon::Class)->findBy( array(), array('sortOrder' => 'ASC') );
        $extras_loop    = $em->getRepository(Extra::Class)->findBy( array(), array('sortOrder' => 'ASC') );   
        
        $addons = [];
        foreach($addons_loop as $addon){
            $addons[$addon->getId()] = $addon->getLabel();
        }

        $extras = [];
        foreach($extras_loop as $extra){
            $extras[$extra->getId()] = $extra->getLabel();
        }
        
        return new JsonResponse( json_encode([
            'addons'        => $addons,
            'extras'        => $extras,
        ]));

    }
}

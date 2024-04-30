<?php


namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Wicrew\AddonBundle\Entity\Addon;
use App\Wicrew\AddonBundle\Entity\Extra;
use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\ActivityBundle\Entity\ActivityLocation;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use Throwable;

    
/**
 * Positioning Controller
 */
class PositioningController extends BaseAdminController
{
    /**
     * @Route("admin/positioning", name="positioning")
     */
    public function index(): Response
    {
    
        $em                     = $this->getEM();
        $addons                 = $em->getRepository(Addon::Class)->findBy( array(), array('sortOrder' => 'ASC') );
        $extras                 = $em->getRepository(Extra::Class)->findBy( array(), array('sortOrder' => 'ASC') );   
        $activityLocations      = $em->getRepository(ActivityLocation::Class)->findAll( );
        $activity_locations     = [];
        $activities             = [];
        
        foreach($activityLocations as $activityLocation){
            $activity_locations[$activityLocation->getId()] = $activityLocation->getName();

            $_activities = $em->getRepository(Activity::Class)->findBy( array( 'location'   => $activityLocation->getId(), 'visibility' => 1, ), array('sortOrder' => 'ASC') );   

            $activities[$activityLocation->getName()] = $_activities; 
        }
       
        return $this->render('positioning/index.html.twig', [
            'addons'        => $addons,
            'extras'        => $extras,
            'activities'    => $activities 
        ]);
    }

    /**
     * @Route("admin/positioning/save", name="save_positioning")
     */
    public function saveAction(): JsonResponse
    {
        try {

            // UPDATING EXTRAS POSITION 
            if ( isset( $_POST['extras'] ) ) {
                $sorted_extras = $_POST['extras'];
            } else {
                $response = array( "status"    => "failed", 'message' => 'Extras data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            $em = $this->getEM();
            $em->beginTransaction();
            
            $extras         = $em->getRepository(Extra::Class)->findAll();
            $_extras_custom = [];
            foreach($extras as $index => $extra){
                $_extras_custom[$sorted_extras[$index]] = $extra->getId(); 
            }
            
            foreach($extras as $index => $extra){

                $extra->setSortOrder($_extras_custom[$extra->getLabel()]);
                $em->persist($extra);
                  
            }
            $em->flush();
            $em->commit();



            // UPDATING ADDONS POSITION 
            if ( isset( $_POST['addons'] ) ) {
                $sorted_addons = $_POST['addons'];
            } else {
                $response = array( "status"    => "failed", 'message' => 'Addons data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            $em = $this->getEM();
            $em->beginTransaction();
            
            $addons         = $em->getRepository(Addon::Class)->findAll();
            $_addons_custom = [];
            foreach($addons as $index => $addon){
                $_addons_custom[$sorted_addons[$index]] = $addon->getId(); 
            }

            foreach($addons as $index => $addon){

                $addon->setSortOrder($_addons_custom[$addon->getLabel()]);
                $em->persist($addon);
                  
            }
            $em->flush();
            $em->commit();


            // UPDATING ACTIVITIES POSITION 
            if ( isset( $_POST['activities'] ) ) {
                $sorted_activities = $_POST['activities'];
            } else {
                $response = array( "status"    => "failed", 'message' => 'Activities data missing!' );
                return new JsonResponse( json_encode($response) );
                exit;
            }

            $em = $this->getEM();
            $em->beginTransaction();
            
            $activities = $em->getRepository(Activity::Class)->findBy( array( 'visibility' => 1, ), array() );   

            $_activities_custom = [];
            foreach($activities as $index => $activity){
                $_activities_custom[$sorted_activities[$index]] = $activity->getId(); 
            }

            foreach($activities as $index => $activity){

                $activity->setSortOrder($_activities_custom[$activity->getName()]);
                $em->persist($activity);
                  
            }
            $em->flush();
            $em->commit();


            $this->addFlash('success', "Items has been re-positioned!");
            $response = array( "status" => "success", "message" => "Items has been re-positioned!" );
            return new JsonResponse( json_encode($response) );

        } catch (Throwable $e) {
            $em->rollback();
            $response = array( "status"    => "failed", 'message' => $e->getMessage() );
            return new JsonResponse( json_encode($response) );
        }
    }
}

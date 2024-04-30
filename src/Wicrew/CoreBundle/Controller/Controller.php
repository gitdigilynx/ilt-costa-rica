<?php


namespace App\Wicrew\CoreBundle\Controller;

use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use App\Wicrew\PageBundle\Controller\PageContentControllerInterface;
use App\Wicrew\PageBundle\Entity\PageContent;
use App\Wicrew\CoreBundle\ControllerTrait\ControllerTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class Controller extends BaseController {
    use ControllerTrait;

    /* @var EntityManager $em */
    private $em = null;

    public function getEM(): EntityManager {
        if ($this->em === null) {
            $this->em = $this->getDoctrine()->getManager();
        }
        return $this->em;
    }

    //    public static function getSubscribedServices() {
    //        $services = parent::getSubscribedServices();
    //
    //        $services['translator'] = '?' . TranslatorInterface::class;
    //        $services['kernel'] = '?' . KernelInterface::class;
    //        $services['wicrew.core.utils'] = '?' . Utils::class;
    //
    //        return $services;
    //    }

    protected function translator(): TranslatorInterface {
        return $this->get('translator');
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

    /**
     * @param Request $request
     * @param string $key
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getRequestData(Request $request, string $key) {
        $result = $this->requestParameterBag($request, $key);
        if ($result === null) {
            throw new Exception("Key '$key' not found in request parameters.");
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
        file_put_contents($this->get('kernel')->getProjectDir() . '/var/log/error.log', $str, FILE_APPEND);
    }

    /**
     * Page
     *
     * @param Request $request
     *
     * @return Response
     */
    public function pageNotFoundAction(Request $request) {
        $utils = $this->get('wicrew.core.utils');
        $em = $utils->getEntityManager();
        $slug = trim($request->getPathInfo(), '/');
        $page = $em->getRepository(PageContent::class)->findOneBy(['slug' => $slug]);
        $response = null;

        if ($page) {
            $serviceController = $this->get('wicrew.' . $page->getType() . '.controller.page_content');
            if ($serviceController instanceof PageContentControllerInterface) {
                $response = $serviceController->viewPageDetailAction($request, $slug);
            }
        }
        if( $slug == "private-transportation" ){
            return new RedirectResponse($request->getSchemeAndHttpHost().'/product/transportation/private-shuttles');
        }
        if( $slug == "copy-of-contact-us" ){
            return new RedirectResponse($request->getSchemeAndHttpHost().'/sale/contact');
        }
        if( $slug == "copy-of-transportation-1" ){
            return new RedirectResponse($request->getSchemeAndHttpHost().'/product/transportation/private-shuttles');
        }
        if( $slug == "book" ){
            return new RedirectResponse($request->getSchemeAndHttpHost().'/');
        }
        if( $slug == "copy-of-jeep-boat-jeep-montezuma-to" ){
            return new RedirectResponse($request->getSchemeAndHttpHost().'/product/transportation/jeep-boat-jeep');
        }
        if( $slug == "copy-of-contact-us-1" ){
            return new RedirectResponse($request->getSchemeAndHttpHost().'/sale/contact');
        }
        if( $slug == "contact-us" ){
            return new RedirectResponse($request->getSchemeAndHttpHost().'/sale/contact');
        }
        // if( $slug == "tours" ){
        //     return new RedirectResponse($request->getSchemeAndHttpHost().'/tours');
        // }


        if (!$response) {
            return $this->get404Response();
        }

        return $response;
    }

    /**
     * @Route("/faq", name="faq")
     */
    public function faqAction(Request $request)
    {
        return $this->render('faq/index.html.twig');
    }

    /**
     * @Route("/faq/private-transportation", name="faq_private_transportation")
     */
    public function faqPrivateTransportationAction(Request $request)
    {
        return $this->render('faq/private_transportation.html.twig');
    }

    /**
     * @Route("/faq/jeep-boat-jeep-service", name="faq_jeep_boat_jeep_service")
     */
    public function faqJeepBoatJeepServiceAction(Request $request)
    {
        return $this->render('faq/jeep_boat_jeep_service.html.twig');
    }

    /**
     * @Route("/faq/water-taxi", name="faq_water_taxi")
     */
    public function faqWaterTaxiAction(Request $request)
    {
        return $this->render('faq/water_taxi.html.twig');
    }

    /**
     * @Route("/faq/private-domestic-flights", name="faq_private_domestic_flights")
     */
    public function faqPrivateDomesticFlightsAction(Request $request)
    {
        return $this->render('faq/private_domestic_flights.html.twig');
    }

    /**
     * @Route("/faq/activities", name="faq_activities")
     */
    public function faqActivitiesAction(Request $request)
    {
        return $this->render('faq/activities.html.twig');
    }
}
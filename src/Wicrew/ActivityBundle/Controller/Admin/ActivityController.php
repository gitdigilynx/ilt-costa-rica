<?php

namespace App\Wicrew\ActivityBundle\Controller\Admin;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\CoreBundle\Controller\Admin\AdminController as BaseAdminController;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * ActivityController
 */
class ActivityController extends BaseAdminController {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array {
        return parent::getSubscribedServices() + [
            'vich_uploader.templating.helper.uploader_helper' => UploaderHelper::class
            ];
    }

    /**
     * Duplicate a pre-existing Activity into a new database row.
     *
     * @return Response
     */
    public function duplicateAction() {
        $this->dispatch(self::PRE_DUPLICATE);

        /* @var Activity $entity */
        $entity = $this->em->getRepository(Activity::class)->findOneBy(['id' => $this->request->query->get('id')]);

        if ($this->request->isMethod('POST')) {
            $newSlug = $this->request->request->get('slug', null);
            $newName = $this->request->request->get('name', null);

            $existingSlugs = $this->getEM()->getRepository(Activity::class)->findBy([ 'slug' => $newSlug]);
            if (count($existingSlugs) > 0 || $newSlug === null) {
                $this->addFlash('error', $this->translator()->trans('activity.duplicate.in_use'));
                return $this->render('@WicrewActivity/Admin/duplicate.html.twig', [
                    'inputtedSlug' => $newSlug,
                    'inputtedName' => $newName,
                    'activity' => $entity
                ]);
            }

            $regex = "^[a-zA-Z0-9\-\_]+$";
            if (!preg_match("/$regex/", $newSlug)) {
                $this->addFlash('error', $this->translator()->trans('activity.duplicate.bad_name'));
                return $this->render('@WicrewActivity/Admin/duplicate.html.twig', [
                    'inputtedSlug' => $newSlug,
                    'inputtedName' => $newName,
                    'activity' => $entity
                ]);
            }

            // Valid.
            $newActivity = clone $entity;
            $newActivity->setSlug($newSlug);
            $newActivity->setName($newName);

            // Duplicate the image file.
            $vichHelper = $this->container->get('vich_uploader.templating.helper.uploader_helper');
            $coreUtils = $this->get('wicrew.core.utils');
            $projectPath = $this->container->get('kernel')->getProjectDir() . DIRECTORY_SEPARATOR . 'public';

            foreach ($newActivity->getSlides() as $slide) {
                $originalFilePath = $projectPath . $vichHelper->asset($slide, 'imageFile');
                $slide->setImage($coreUtils->duplicateVichFile($originalFilePath));
            }

            $this->getEM()->persist($newActivity);
            $this->getEM()->flush();

            $this->addFlash('success', $this->translator()->trans('activity.duplicate.successful'));
            return $this->redirectToRoute('easyadmin', [
                'entity' => 'Activity',
                'action' => 'list'
            ]);
        }

        return $this->render('@WicrewActivity/Admin/duplicate.html.twig', [
            'activity' => $entity
        ]);
    }

    /**
     * archive an activity
     *
     * @return RedirectResponse
     */
    public function archiveAction() {
        $activity = $this->em->getRepository(Activity::class)->findOneBy(['id' => $this->request->query->get('id')]);
        $activity->setArchived(true);
        $this->em->persist($activity);
        $this->em->flush();

        $flashMessage = 'Activity #'. $activity->getId() . ' archived successfully';

        $this->addFlash('success', $flashMessage);

        return $this->redirectToReferrer();
    }

    /**
     * unarchive an activity
     *
     * @return RedirectResponse
     */
    public function unarchiveAction() {
        $activity = $this->em->getRepository(Activity::class)->findOneBy(['id' => $this->request->query->get('id')]);
        $activity->setArchived(false);
        $this->em->persist($activity);
        $this->em->flush();

        $flashMessage = 'Activity #'. $activity->getId() . ' unarchived successfully';

        $this->addFlash('success', $flashMessage);

        return $this->redirectToReferrer();
    }
}

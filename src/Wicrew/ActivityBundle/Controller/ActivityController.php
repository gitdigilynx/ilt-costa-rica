<?php

namespace App\Wicrew\ActivityBundle\Controller;

use App\Wicrew\ActivityBundle\Entity\Activity;
use App\Wicrew\ActivityBundle\Entity\ActivityLocation;
use App\Wicrew\PageBundle\Controller\PageContentControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Wicrew\CoreBundle\ControllerTrait\ControllerTrait;

/**
 * ActivityController
 */
class ActivityController extends Controller implements PageContentControllerInterface {
    use ControllerTrait;

    /**
     * List locations
     *
     * @Route(path = "tours", name = "wicrew_locations")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function locationsAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $locations = $em->getRepository(ActivityLocation::class)->findBy([], ['customOrder' => 'ASC']);

        $utils = $this->get('wicrew.core.utils');
        $utils->checkForOrderEditSession($request);

        return $this->render('WicrewActivityBundle::locations.html.twig', [
            'locations' => $locations
        ]);
    }

    /**
     * List activities
     *
     * @Route(path = "tours/{location}", name = "wicrew_activities")
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param string $location
     *
     * @return Response
     */
    public function activitiesAction(Request $request, TranslatorInterface $translator, $location) {
        $utils = $this->get('wicrew.core.utils');
        $utils->checkForOrderEditSession($request);

        $itemsPerPage = 12;

        $page = $request->query->getInt('page', 1);

        $queryParams = $request->query->all();

        $em = $this->getDoctrine()->getManager();

        $activityLocation = $em->getRepository(ActivityLocation::class)->findOneBy(['slug' => $location]);
        if (!$activityLocation) {
            return $this->get404Response('Page not found');
        }

        $qb = $em->getRepository(Activity::class)->getActiveActivities(
            [
                'location' => $activityLocation,
                'types' => isset($queryParams['types']) ? $queryParams['types'] : '',
                'catagories' => isset($queryParams['catagories']) ? $queryParams['catagories'] : '',
                'durations' => isset($queryParams['durations']) ? $queryParams['durations'] : '',
                'difficulties' => isset($queryParams['difficulties']) ? $queryParams['difficulties'] : ''
            ],
            [ "sort_order" => "ASC" ]
        );

        // Filter options count
        $activities = $qb->getQuery()->getResult();
        $filterCount = [
            'catagories' => [
                Activity::CATAGORY_ADVENTURE => 0,
                Activity::CATAGORY_NATURE => 0,
                Activity::CATAGORY_CULTURAL => 0,
                Activity::CATAGORY_WATER => 0,

            ], 
            'types' => [
                Activity::TYPE_GROUP => 0,
                Activity::TYPE_PRIVATE => 0,
            ],
            'durations' => [
                Activity::DURATION_1_2_HOURS => 0,
                Activity::DURATION_HALF_DAY => 0,
                Activity::DURATION_FULL_DAY => 0
            ],
            'difficulties' => [
                Activity::DIFFICULTY_EASY => 0,
                Activity::DIFFICULTY_MODERATE => 0,
                Activity::DIFFICULTY_DIFFICULT => 0
            ]
        ];

        foreach ($activities as $activity) {
            $filterCount['catagories'][Activity::CATAGORY_ADVENTURE] += (in_array(Activity::CATAGORY_ADVENTURE, $activity->getCatagories()) ? 1 : 0);
            $filterCount['catagories'][Activity::CATAGORY_NATURE] += (in_array(Activity::CATAGORY_NATURE, $activity->getCatagories()) ? 1 : 0);
            $filterCount['catagories'][Activity::CATAGORY_CULTURAL] += (in_array(Activity::CATAGORY_CULTURAL, $activity->getCatagories()) ? 1 : 0);
            $filterCount['catagories'][Activity::CATAGORY_WATER] += (in_array(Activity::CATAGORY_WATER, $activity->getCatagories()) ? 1 : 0);
            $filterCount['types'][Activity::TYPE_GROUP] += (in_array(Activity::TYPE_GROUP, $activity->getTypes() ) ? 1 : 0);
            $filterCount['types'][Activity::TYPE_PRIVATE] += (in_array(Activity::TYPE_PRIVATE, $activity->getTypes() ) ? 1 : 0);

            if (isset($filterCount['durations'][$activity->getDuration()])) {
                $filterCount['durations'][$activity->getDuration()]++;
            }

            $filterCount['difficulties'][Activity::DIFFICULTY_EASY] += (in_array(Activity::DIFFICULTY_EASY, $activity->getDifficultyLevels()) ? 1 : 0);
            $filterCount['difficulties'][Activity::DIFFICULTY_MODERATE] += (in_array(Activity::DIFFICULTY_MODERATE, $activity->getDifficultyLevels()) ? 1 : 0);
            $filterCount['difficulties'][Activity::DIFFICULTY_DIFFICULT] += (in_array(Activity::DIFFICULTY_DIFFICULT, $activity->getDifficultyLevels()) ? 1 : 0);
        }

        $filters = [];
        $filters['types'] = [
            'label' => $translator->trans('activity.types'),
            'options' => $this->initFilter($request, $location, 'types', [
                Activity::TYPE_GROUP => $translator->trans('activity.type.option.group'),
                Activity::TYPE_PRIVATE => $translator->trans('activity.type.option.private')
            ], $filterCount)
        ];
        $filters['catagories'] = [
            'label' => "Activity Catagory",
            'options' => $this->initFilter($request, $location, 'catagories', [
                Activity::CATAGORY_ADVENTURE => $translator->trans('activity.type.option.adventure'),
                Activity::CATAGORY_NATURE => $translator->trans('activity.type.option.nature'),
                Activity::CATAGORY_CULTURAL => $translator->trans('activity.type.option.cultural'),
                Activity::CATAGORY_WATER => $translator->trans('activity.type.option.water')
            ], $filterCount)
        ];
        $filters['durations'] = [
            'label' => $translator->trans('activity.duration'),
            'options' => $this->initFilter($request, $location, 'durations', [
                Activity::DURATION_1_2_HOURS => $translator->trans('activity.duration.option.1_2_hours'),
                Activity::DURATION_HALF_DAY => $translator->trans('activity.duration.option.half_day'),
                Activity::DURATION_FULL_DAY => $translator->trans('activity.duration.option.full_day')
            ], $filterCount)
        ];
        $filters['difficulties'] = [
            'label' => $translator->trans('activity.difficulty_levels'),
            'options' => $this->initFilter($request, $location, 'difficulties', [
                Activity::DIFFICULTY_EASY => $translator->trans('activity.difficulty_level.option.easy'),
                Activity::DIFFICULTY_MODERATE => $translator->trans('activity.difficulty_level.option.morerate'),
                Activity::DIFFICULTY_DIFFICULT => $translator->trans('activity.difficulty_level.option.difficult')
            ], $filterCount)
        ];

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $qb, // QueryBuilder
            $page, // Page number
            $itemsPerPage, // Limit per page
            [
                'defaultSortFieldName'      => 'a.sortOrder',
                'defaultSortDirection' => 'asc'
            ]
        );

        return $this->render('WicrewActivityBundle::activities.html.twig', [
            'location_slug' => $location,
            'location' => $activityLocation,
            'filters' => $filters,
            'is_filters' => isset($queryParams['types']) || isset($queryParams['durations']) || isset($queryParams['catagories']) || isset($queryParams['difficulties']),
            'pagination' => $pagination
        ]);
    }

    /**
     * Initialize filter options
     *
     * @param Request $request
     * @param string $location
     * @param string $filterName
     * @param array $options
     * @param array $optionCount
     *
     * @return array
     */
    protected function initFilter(Request $request, $location, $filterName, array $options, array $optionCount = []) {
        $filter = [];

        $queryParams = $request->query->all();
        unset($queryParams['page']);

        $selectedValues = isset($queryParams[$filterName]) && $queryParams[$filterName] ? explode(',', $queryParams[$filterName]) : [];

        $filterIdx = 0;
        foreach ($options as $val => $label) {
            $filter[$filterIdx]['label'] = $label;
            $filter[$filterIdx]['value'] = $val;
            $filter[$filterIdx]['isSelected'] = in_array($val, $selectedValues);
            $filter[$filterIdx]['count'] = isset($optionCount[$filterName][$val]) ? $optionCount[$filterName][$val] : 0;

            $tmp = $selectedValues;

            if ($filter[$filterIdx]['isSelected']) {
                unset($tmp[array_search($val, $tmp)]);
                $queryParams[$filterName] = implode(',', $tmp);
            } else {
                $queryParams[$filterName] = implode(',', array_merge($selectedValues, [$val]));
            }

            if (!$queryParams[$filterName]) {
                unset($queryParams[$filterName]);
            }
            $filter[$filterIdx]['link'] = $this->generateUrl('wicrew_activities', ['location' => $location] + $queryParams);

            $filterIdx++;
        }

        return $filter;
    }

    /**
     * {@inheritDoc}
     */
    public function viewPageDetailAction(Request $request, $slug): Response {
        $utils = $this->get('wicrew.core.utils');
        $utils->checkForOrderEditSession($request);

        new \Exception('bruh');

        $em = $this->getDoctrine()->getManager();
        $activity = $em->getRepository(Activity::class)->getActiveActivityBySlug($slug);

        if (!$activity) {
            return $this->get404Response('This page does not exist');
        }

        $otherActivities = $em->getRepository(Activity::class)->getActiveActivitiesByLocation($activity->getLocation(), $activity->getId());
        $locations = $em->getRepository(ActivityLocation::class)->getAllLocations($activity->getLocation()->getId());

        return $this->render('WicrewActivityBundle::view.html.twig', [
            'activity' => $activity,
            'otherActivities' => $otherActivities,
            'locations' => $locations
        ]);
    }
}

<?php

namespace App\Wicrew\ReportBundle\Controller\Admin;

use App\Wicrew\CoreBundle\Controller\Admin\AdminController;
use App\Wicrew\SaleBundle\Entity\OrderItem;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * ReportController
 */
class ReportController extends AdminController {

    /**
     * {@inhertiDoc}
     */
    public static function getSubscribedServices(): array {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    /**
     * {@inheritDoc}
     */
    public function listAction() {
        if (
            !($filters = $this->request->query->get('filters'))
            || !isset($filters['pickDate'])
        ) {
            return $this->redirectToRoute('easyadmin', [
                'action' => 'list',
                'entity' => $this->entity['name'],
                'filters' => array_merge($filters ?: [], [
                    'pickDate' => [
                        'comparison' => 'between',
                        'value' => date('Y-m-') . '01',
                        'value2' => date('Y-m-') . date('t')
                    ]
                ])
            ]);
        } else {
            return parent::listAction();
        }
    }

    /**
     * Export the report
     */
    public function exportAction() {
        $translator = $this->container->get('translator');
        $twig = $this->container->get('twig');
        $twigExtension = $this->container->get('twig')->getExtension(\EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension::class);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($translator, $twig, $twigExtension) {
            $delimiter = ',';
            $encloser = '"';

            $handle = fopen('php://output', 'w+');

            // Add the header of the CSV file
            $fields = $this->entity['list']['fields'];
            $headers = [];
            foreach ($fields as $field) {
                $headers[] = $translator->trans($field['label']);
            }
            fputcsv($handle, $headers, $delimiter, $encloser);

            $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->entity['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);
            $summaryData = [
                "subTotalNet" => 0,
                "subTotalRack" => 0,
                "commission" => 0,
                "tax" => 0,
                "profit" => 0
            ];
            $summaryFields = array_keys($summaryData);
            /* @var OrderItem $item */
            foreach ($paginator->getCurrentPageResults() as $item) {
                $data = [];
                foreach ($fields as $field => $metaData) {
                    if (in_array($field, $summaryFields)) {
                        $displayValue = '';
                        if ($field == 'subTotalNet') {
                            $value = $item->getSubtotalNet();
                            $displayValue = $value;
                        } else if ($field == 'subTotalRack') {
                            $value = $item->getSubtotalRack();
                            $displayValue = $value;
                        } else if ($field == 'commission') {
                            if ($item->getOrder()->getSupplier()) {
                                $value = $item->getOrder()->getSupplier()->getCommission();
                                $displayValue = $value . '%';
                            } else {
                                $value = 0;
                                $displayValue = 'Null';
                            }
                        } else if ($field == 'tax') {
                            $methodName = 'get' . ucfirst($field);
                            $value = $item->{$methodName}();
                            $displayValue = $value;
                        } else if ($field == 'profit') {
                            $rackPrice = $item->getSubtotalRack();
                            $netPrice = $item->getSubtotalNet();

                            if ($item->getOrder()->getSupplier()) {
                                $commission = ($rackPrice * ($item->getOrder()->getSupplier()->getCommission() / 100));
                            } else {
                                $commission = 0;
                            }

                            $value = ($rackPrice - $netPrice - $commission);
                            $displayValue = $value;
                        }

                        $data[] = trim($displayValue);

                        $summaryData[$field] += $value;
                    } else {
                        $data[] = trim(
                            strip_tags($twigExtension->renderEntityField($twig, 'list', $this->entity['name'], $item, $metaData))
                        );
                    }
                }
                fputcsv($handle, $data, $delimiter, $encloser);
            }

            fputcsv($handle, [], $delimiter, $encloser);

            $summaryRow = [];
            $idx = 0;
            foreach ($fields as $field => $metaData) {
                $value = '';
                if ($idx == 0) {
                    $value = $translator->trans('report.total');
                } else if (in_array($field, $summaryFields)) {
                    $value = $summaryData[$field];
                }

                $summaryRow[] = $value;

                $idx++;
            }
            fputcsv($handle, $summaryRow, $delimiter, $encloser);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');

        return $response;
    }

    /**
     * Batch export
     *
     * @param array $ids
     */
    public function exportBatchAction(array $ids) {
        echo('<pre>');
        print_r($ids);
        echo('</pre>');
        die('Yo dude!');
    }

}

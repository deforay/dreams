<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'application' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/application',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
            'login' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/login[/:action]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Login',
                        'action' => 'index'
                    ),
                ),
            ),'log-out' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/log-out',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Login',
                        'action' => 'log-out'
                    ),
                ),
            ),'role' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/role[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Role',
                        'action' => 'index'
                    ),
                ),
            ),'user' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/user[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\User',
                        'action' => 'index'
                    ),
                ),
            ),'add-user' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/user/add[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\User',
                        'action' => 'add'
                    ),
                ),
            ),'edit-user' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/user/edit[/:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\User',
                        'action' => 'edit'
                    ),
                ),
            ),'common' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/common[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Common',
                        'action' => 'index'
                    ),
                ),
            ),'facility' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/facility[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Facility',
                        'action' => 'index'
                    ),
                ),
            ),'add-facility' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/facility/add[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Facility',
                        'action' => 'add'
                    ),
                ),
            ),'edit-facility' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/facility/edit[/:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Facility',
                        'action' => 'edit'
                    ),
                ),
            ),'country' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/country[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Country',
                        'action' => 'index'
                    ),
                ),
            ),'anc-site' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/anc-site[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\AncSite',
                        'action' => 'index'
                    ),
                ),
            ),'add-anc-site' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/anc-site/add[/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\AncSite',
                        'action' => 'add'
                    ),
                ),
            ),'edit-anc-site' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/anc-site/edit[/:id][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\AncSite',
                        'action' => 'edit'
                    ),
                ),
            ),'data-collection' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/data-collection[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'index'
                    ),
                ),
            ),'add-data-collection' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/data-collection/add',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'add'
                    ),
                ),
            ),'edit-data-collection' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/data-collection/edit[/:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'edit'
                    ),
                ),
            ),'lock-data-collection' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/data-collection/lock',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'lock'
                    ),
                ),
            ),'unlock-data-collection' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/data-collection/unlock',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'unlock'
                    ),
                ),
            ),'view-data-collection' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/data-collection/view[/][:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'view'
                    ),
                ),
            ),'data-extraction' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/data-extraction[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataExtraction',
                        'action' => 'index'
                    ),
                ),
            ),'export-data-collection' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/data-extraction/export-excel',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataExtraction',
                        'action' => 'export-excel'
                    ),
                ),
            ),'result-email' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/result-email[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\ResultEmail',
                        'action' => 'index'
                    ),
                ),
            ),'result-email-send' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/result-email/send',
                    'defaults' => array(
                        'controller' => 'Application\Controller\ResultEmail',
                        'action' => 'send'
                    ),
                ),
            ),'result-email-pdf' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/result-email/generate-pdf',
                    'defaults' => array(
                        'controller' => 'Application\Controller\ResultEmail',
                        'action' => 'generate-pdf'
                    ),
                ),
            ),'result-sms' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/result-sms[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\ResultSms',
                        'action' => 'index'
                    ),
                ),
            ),'get-data-collection' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/get-data-collection',
                    'defaults' => array(
                        'controller' => 'Application\Controller\ResultEmail',
                        'action' => 'get-data-collection'
                    ),
                ),
            ),'get-countries-lab-anc' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/get-countries-lab-anc',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'get-countries-lab-anc'
                    ),
                ),
            ),'check-patient-record' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/check-patient-record',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'check-patient-record'
                    ),
                ),
            ),'print-lab-logbook' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/print-lab-logbook[/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataExtraction',
                        'action' => 'print-lab-logbook'
                    ),
                ),
            ),'generate-logbook-pdf' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/generate-logbook-pdf',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataExtraction',
                        'action' => 'generate-logbook-pdf'
                    ),
                ),
            ),'change-password' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/change-password',
                    'defaults' => array(
                        'controller' => 'Application\Controller\User',
                        'action' => 'change-password'
                    ),
                ),
            ),'check-account-password' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/check-account-password',
                    'defaults' => array(
                        'controller' => 'Application\Controller\User',
                        'action' => 'check-password'
                    ),
                ),
            ),'clinic-data-collection' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/data-collection[/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'data-collection'
                    ),
                ),
            ),'clinic-data-collection-add' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic-data-collection-add',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'data-collection-add'
                    ),
                ),
            ),'clinic-data-collection-edit' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/data-collection/edit[/:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'data-collection-edit'
                    ),
                ),
            ),'clinic-data-extraction' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/data-extraction[/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'data-extraction'
                    ),
                ),
            ),'clinic-enrollment' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/enrollment-report[/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'enrollment-report'
                    ),
                ),
            ),'export-clinic-data-collection' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/clinic-data-extraction/export-excel',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'data-collection-export-excel'
                    ),
                ),
            ),'clinic-lab-report' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/lab-report[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'lab-report'
                    ),
                ),
            ),'export-clinic-lab-report' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/export-clinic-lab-report',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'export-lab-report'
                    ),
                ),
            ),'check-duplicate-data-report' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/check-duplicate-data-report',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'check-duplicate-data-report'
                    ),
                ),
            ),'rot47' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/rot47',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'rot47'
                    ),
                ),
            ),'clinic-risk-assessment' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/risk-assessment[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'index'
                    ),
                ),
            ),'add-clinic-risk-assessment' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/risk-assessment/add[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'add'
                    ),
                ),
            ),'edit-clinic-risk-assessment' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/risk-assessment/edit[/:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'edit'
                    ),
                ),
            ),'view-clinic-risk-assessment' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/risk-assessment/view[/:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'view'
                    ),
                ),
            ),'country-dashboard' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/dashboard[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Country',
                        'action' => 'dashboard'
                    ),
                ),
            ),'study-overview-report' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/study-overview[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\StudyOverviewReport',
                        'action' => 'index'
                    ),
                ),
            ),'export-study-overview' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/export-study-overview',
                    'defaults' => array(
                        'controller' => 'Application\Controller\StudyOverviewReport',
                        'action' => 'export-study-overview'
                    ),
                ),
            ),'export-risk-assessment-excel' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/export-risk-assessment-excel',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'export-excel'
                    ),
                ),
            ),'generate-risk-assessment-pdf' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/generate-risk-assessment-pdf',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'generate-pdf'
                    ),
                ),
            ),'lock-clinic-data-collection' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/clinic-data-collection/lock',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'lock'
                    ),
                ),
            ),'unlock-clinic-data-collection' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/clinc-data-collection/unlock',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'unlock'
                    ),
                ),
            ),'lock-risk-assessment-data' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/risk-assessment/lock',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'lock'
                    ),
                ),
            ),'unlock-risk-assessment-data' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/risk-assessment/unlock',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'unlock'
                    ),
                ),
            ),'anc-asante-result' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/anc-asante-result[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'anc-asante-result'
                    ),
                ),
            ),'export-asante-result' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/export-asante-result',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'export-anc-asante-result'
                    ),
                ),
            ),'study-files' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/study-files[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\StudyFiles',
                        'action' => 'index',
                    ),
                ),
            ),'upload-study-files' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/study-files/upload[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\StudyFiles',
                        'action' => 'upload'
                    ),
                ),
            ),'export-country-dashboard-data' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/export-country-dashboard-data',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Country',
                        'action' => 'export-dashboard-data'
                    ),
                ),
            ),'get-dashboard-details' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/get-dashboard-details',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action' => 'get-dashboard-details'
                    ),
                ),
            ),'export-dashboard-data' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/export-dashboard-data',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action' => 'export-dashboard-data'
                    ),
                ),
            ),'summary' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/summary[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Summary',
                        'action' => 'index'
                    ),
                ),
            ),'get-data-reporting-weekly-bar-chart' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/summary/get-data-reporting-weekly-bar-chart',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Summary',
                        'action' => 'get-data-reporting-weekly-bar-chart'
                    ),
                ),
            ),'get-behaviour-data-reporting-weekly-bar-chart' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/summary/get-behaviour-data-reporting-weekly-bar-chart',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Summary',
                        'action' => 'get-behaviour-data-reporting-weekly-bar-chart'
                    ),
                ),
            ),'export-ipv-report' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/export-ipv-report',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessment',
                        'action' => 'export-ipv-report'
                    ),
                ),
            ),'clinic-risk-assessment-v2' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/risk-assessment/v2[/][/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessmentV2',
                        'action' => 'index'
                    ),
                ),
            ),'add-clinic-risk-assessment-v2' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/risk-assessment/v2/add[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessmentV2',
                        'action' => 'add'
                    ),
                ),
            ),'edit-clinic-risk-assessment-v2' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/risk-assessment/v2/edit[/:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessmentV2',
                        'action' => 'edit'
                    ),
                ),
            ),'view-clinic-risk-assessment-v2' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/clinic/risk-assessment/v2/view[/:id][/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\RiskAssessmentV2',
                        'action' => 'view'
                    ),
                ),
            ),'not-enrolled' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/not-enrolled[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ussd',
                        'action' => 'not-enrolled',
                    ),
                ),
            ),'export-not-enrolled' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/export-not-enrolled',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ussd',
                        'action' => 'export-not-enrolled',
                    ),
                ),
            ),'get-not-enrolled-pie-chart' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/get-not-enrolled-pie-chart',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ussd',
                        'action' => 'get-not-enrolled-pie-chart',
                    ),
                ),
            ),'odk-supervisory-audit' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/odk-supervisory-audit[/][:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\OdkSupervisoryAudit',
                        'action' => 'index',
                    ),
                ),
            ),'export-odk-supervisory-audit' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/export-odk-supervisory-audit',
                    'defaults' => array(
                        'controller' => 'Application\Controller\OdkSupervisoryAudit',
                        'action' => 'export-odk-supervisory-audit',
                    ),
                ),
            ),'get-reason-for-refused-pie-chart' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/get-reason-for-refused-pie-chart',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Ussd',
                        'action' => 'get-reason-for-refused-pie-chart',
                    ),
                ),
            ),'export-rsot-excel' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/export-rsot-excel',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'export-rsot-excel',
                    ),
                ),
            ),'export-rsot-pdf' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/export-rsot-pdf',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'export-rsot-pdf',
                    ),
                ),
            ),'get-enrollment-report-details' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/get-enrollment-report-details',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'get-enrollment-report-details'
                    ),
                ),
            ),'export-enrollment-report' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/export-enrollment-report',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Clinic',
                        'action' => 'export-enrollment-report',
                    ),
                ),
            )
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Application\Controller\Index' => 'Application\Controller\IndexController',
            'Application\Controller\Login' => 'Application\Controller\LoginController',
            'Application\Controller\Role' => 'Application\Controller\RoleController',
            'Application\Controller\User' => 'Application\Controller\UserController',
            'Application\Controller\Common' => 'Application\Controller\CommonController',
            'Application\Controller\Facility' => 'Application\Controller\FacilityController',
            'Application\Controller\Country' => 'Application\Controller\CountryController',
            'Application\Controller\AncSite' => 'Application\Controller\AncSiteController',
            'Application\Controller\DataCollection' => 'Application\Controller\DataCollectionController',
            'Application\Controller\DataExtraction' => 'Application\Controller\DataExtractionController',
            'Application\Controller\ResultEmail' => 'Application\Controller\ResultEmailController',
            'Application\Controller\ResultSms' => 'Application\Controller\ResultSmsController',
            'Application\Controller\Clinic' => 'Application\Controller\ClinicController',
            'Application\Controller\RiskAssessment' => 'Application\Controller\RiskAssessmentController',
            'Application\Controller\StudyOverviewReport' => 'Application\Controller\StudyOverviewReportController',
            'Application\Controller\StudyFiles' => 'Application\Controller\StudyFilesController',
            'Application\Controller\Summary' => 'Application\Controller\SummaryController',
            'Application\Controller\RiskAssessmentV2' => 'Application\Controller\RiskAssessmentV2Controller',
            'Application\Controller\Ussd' => 'Application\Controller\UssdController',
            'Application\Controller\OdkSupervisoryAudit' => 'Application\Controller\OdkSupervisoryAuditController',
            'Application\Controller\Cron' => 'Application\Controller\CronController'
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            
        )
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
                'import-ussd' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route' => 'import-ussd',
                        'defaults' => array(
                            'controller' => 'Application\Controller\Cron',
                            'action' => 'importussd',
                        ),
                    ),
                ),'data-management' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route' => 'data-management',
                        'defaults' => array(
                            'controller' => 'Application\Controller\Cron',
                            'action' => 'data-management',
                        ),
                    ),
                ),
            ),
        ),
    ),
);

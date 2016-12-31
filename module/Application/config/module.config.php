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
                    'route' => '/user[/:countryId]',
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
                    'route' => '/facility[/:countryId]',
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
                    'route' => '/anc-site[/:countryId]',
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
                    'route' => '/data-collection[/:countryId]',
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
                    'route' => '/data-extraction[/:countryId]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataExtraction',
                        'action' => 'index'
                    ),
                ),
            ),'export-data-extraction' => array(
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
                    'route' => '/result-email[/:countryId]',
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
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
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
            'Application\Controller\Clinic' => 'Application\Controller\ClinicController'
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
                
            ),
        ),
    ),
);

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
                    'route' => '/data-collection[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\DataCollection',
                        'action' => 'index'
                    ),
                ),
            ),
            
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
            'Application\Controller\DataCollection' => 'Application\Controller\DataCollectionController'
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
                'automatic-lock' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route' => 'automatic-lock',
                        'defaults' => array(
                            'controller' => 'Application\Controller\Index',
                            'action' => 'automatic-lock'
                        ),
                    ),
                )
            ),
        ),
    ),
);

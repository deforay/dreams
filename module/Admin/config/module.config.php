<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
            'dashboard' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/admin[/]',
                    'defaults' => array(
                        'controller' => 'Admin\Controller\Index',
                        'action' => 'index',
                    ),
                ),
            ),'role' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/role[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Admin\Controller\Role',
                        'action' => 'index',
                    ),
                ),
            ),'employee' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/employee[/:action][/][:id]',
                    'defaults' => array(
                        'controller' => 'Admin\Controller\Employee',
                        'action' => 'index',
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
            'Admin\Controller\Index' => 'Admin\Controller\IndexController',
            'Admin\Controller\Role' => 'Admin\Controller\RoleController',
            'Admin\Controller\Employee' => 'Admin\Controller\EmployeeController'
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
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

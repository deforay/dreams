<?php

return array(
    'router' => array(
        'routes' => array(
            'api-login' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/login[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\Login',
                    ),
                ),
            ),
            'api-giveask' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/giveask[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\Giveask',
                    ),
                ),
            ),
            'api-green-guide' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/green-guide[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\GreenGuide',
                    ),
                ),
            ),
            'api-forgot-password' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/forgot-password[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\ForgotPassword',
                    ),
                ),
            ),
            'api-common' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/common[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\Common',
                    ),
                ),
            ),
            'api-change-password' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/change-password[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\ChangePassword',
                    ),
                ),
            ),
            'api-update-token' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/update-token[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\UpdateToken',
                    ),
                ),
            ),
            'api-industry' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/industry[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\Industry',
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Api\Controller\Login' => 'Api\Controller\LoginController',
            'Api\Controller\Giveask' => 'Api\Controller\GiveaskController',
            'Api\Controller\GreenGuide' => 'Api\Controller\GreenGuideController',
            'Api\Controller\ForgotPassword' => 'Api\Controller\ForgotPasswordController',
            'Api\Controller\Common' => 'Api\Controller\CommonController',
            'Api\Controller\ChangePassword' => 'Api\Controller\ChangePasswordController',
            'Api\Controller\UpdateToken' => 'Api\Controller\UpdateTokenController',
            'Api\Controller\Industry' => 'Api\Controller\IndustryController',
        ),
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
);

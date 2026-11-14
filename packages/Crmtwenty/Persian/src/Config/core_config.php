<?php

/**
 * crmtwenty: extra sections under Configuration → Email.
 *
 * Merged into the CRM's `core_config` by PersianServiceProvider.
 */
return [
    [
        'key' => 'email.smtp',
        'name' => 'persian::app.configuration.smtp.title',
        'info' => 'persian::app.configuration.smtp.info',
        'icon' => 'icon-mail',
        'sort' => 0,
    ], [
        'key' => 'email.smtp.account',
        'name' => 'persian::app.configuration.smtp.account.title',
        'info' => 'persian::app.configuration.smtp.account.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'host',
                'title' => 'persian::app.configuration.smtp.account.host',
                'info' => 'persian::app.configuration.smtp.account.host-info',
                'type' => 'text',
            ], [
                'name' => 'port',
                'title' => 'persian::app.configuration.smtp.account.port',
                'info' => 'persian::app.configuration.smtp.account.port-info',
                'type' => 'number',
                'default' => '587',
                'validation' => 'numeric|between:1,65535',
            ], [
                'name' => 'encryption',
                'title' => 'persian::app.configuration.smtp.account.encryption',
                'type' => 'select',
                'default' => 'auto',
                'options' => [
                    [
                        'title' => 'persian::app.configuration.smtp.account.encryption-auto',
                        'value' => 'auto',
                    ], [
                        'title' => 'persian::app.configuration.smtp.account.encryption-tls',
                        'value' => 'tls',
                    ], [
                        'title' => 'persian::app.configuration.smtp.account.encryption-ssl',
                        'value' => 'ssl',
                    ], [
                        'title' => 'persian::app.configuration.smtp.account.encryption-none',
                        'value' => 'none',
                    ],
                ],
            ], [
                'name' => 'username',
                'title' => 'persian::app.configuration.smtp.account.username',
                'type' => 'text',
            ], [
                'name' => 'password',
                'title' => 'persian::app.configuration.smtp.account.password',
                'type' => 'password',
            ], [
                'name' => 'verify_peer',
                'title' => 'persian::app.configuration.smtp.account.verify-peer',
                'info' => 'persian::app.configuration.smtp.account.verify-peer-info',
                'type' => 'boolean',
                'default' => '1',
            ], [
                'name' => 'from_address',
                'title' => 'persian::app.configuration.smtp.account.from-address',
                'info' => 'persian::app.configuration.smtp.account.from-address-info',
                'type' => 'text',
                'validation' => 'email',
            ], [
                'name' => 'from_name',
                'title' => 'persian::app.configuration.smtp.account.from-name',
                'type' => 'text',
            ],
        ],
    ], [
        'key' => 'email.smtp.test',
        'name' => 'persian::app.configuration.smtp.test.title',
        'info' => 'persian::app.configuration.smtp.test.info',
        'sort' => 2,
        'fields' => [
            [
                'name' => 'send_test',
                'title' => 'persian::app.configuration.smtp.test.title',
                'type' => 'blade',
                'path' => 'persian::configuration.smtp-test',
            ],
        ],
    ], [
        'key' => 'email.signup',
        'name' => 'persian::app.configuration.signup.title',
        'info' => 'persian::app.configuration.signup.info',
        'icon' => 'icon-user',
        'sort' => 3,
    ], [
        'key' => 'email.signup.settings',
        'name' => 'persian::app.configuration.signup.settings.title',
        'info' => 'persian::app.configuration.signup.settings.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'enabled',
                'title' => 'persian::app.configuration.signup.settings.enabled',
                'info' => 'persian::app.configuration.signup.settings.enabled-info',
                'type' => 'boolean',
                'default' => '0',
            ], [
                'name' => 'require_verification',
                'title' => 'persian::app.configuration.signup.settings.require-verification',
                'info' => 'persian::app.configuration.signup.settings.require-verification-info',
                'type' => 'boolean',
                'default' => '1',
            ], [
                'name' => 'require_approval',
                'title' => 'persian::app.configuration.signup.settings.require-approval',
                'info' => 'persian::app.configuration.signup.settings.require-approval-info',
                'type' => 'boolean',
                'default' => '0',
            ], [
                'name' => 'default_role',
                'title' => 'persian::app.configuration.signup.settings.default-role',
                'info' => 'persian::app.configuration.signup.settings.default-role-info',
                'type' => 'select',
                'options' => 'Crmtwenty\Persian\Support\Registration@roles',
            ],
        ],
    ],
];

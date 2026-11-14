<?php

return [
    'configuration' => [
        'smtp' => [
            'title' => 'Company e-mail (SMTP)',
            'info' => 'The account the CRM sends e-mail with: password resets, verification, notifications and quotes.',

            'account' => [
                'title' => 'SMTP account',
                'info' => 'Outgoing mail server of your company. Leave "Host" empty to use the MAIL_* settings from .env.',
                'host' => 'Host',
                'host-info' => 'e.g. mail.example.com',
                'port' => 'Port',
                'port-info' => '587 for STARTTLS, 465 for SSL',
                'encryption' => 'Encryption',
                'encryption-auto' => 'Automatic (recommended)',
                'encryption-tls' => 'STARTTLS (port 587)',
                'encryption-ssl' => 'SSL/TLS (port 465)',
                'encryption-none' => 'None',
                'username' => 'Username',
                'password' => 'Password',
                'verify-peer' => 'Verify the server certificate',
                'verify-peer-info' => 'Turn off when connecting to localhost or a server with a self-signed certificate.',
                'from-address' => 'From address',
                'from-address-info' => 'Defaults to the username when it is an e-mail address.',
                'from-name' => 'From name',
            ],

            'test' => [
                'title' => 'Send a test e-mail',
                'info' => 'Check the settings with a test message before saving them.',
                'to' => 'Send to',
                'button' => 'Send test e-mail',
                'sending' => 'Sending…',
                'hint' => 'The test uses the values currently in the form above, even if they are not saved yet.',
                'success' => 'A test e-mail was sent to :email. Check the inbox (and the spam folder).',
                'failed' => 'Sending failed: :error',
                'no-host' => 'Enter the SMTP host first.',
                'unknown-error' => 'Unknown error, please try again.',
            ],
        ],

        'signup' => [
            'title' => 'Sign-up & e-mail verification',
            'info' => 'Public sign-up, e-mail address verification and administrator approval.',

            'settings' => [
                'title' => 'User sign-up',
                'info' => 'When enabled, a "Create an account" link is shown under the login form. Configure and test the company e-mail first.',
                'enabled' => 'Allow public sign-up',
                'enabled-info' => 'Visitors can create their own account from the login page.',
                'require-verification' => 'Require e-mail verification',
                'require-verification-info' => 'Users cannot sign in until they click the link sent to their address.',
                'require-approval' => 'Require administrator approval',
                'require-approval-info' => 'New accounts stay inactive until an administrator activates them under Settings → Users.',
                'default-role' => 'Role of new users',
                'default-role-info' => 'When empty, a limited "Sales" role is created and used (never the administrator role).',
            ],
        ],
    ],

    'registration' => [
        'title' => 'Create an account',
        'name' => 'Full name',
        'email' => 'E-mail',
        'password' => 'Password',
        'password-hint' => 'At least 8 characters, letters and numbers',
        'password-confirmation' => 'Confirm password',
        'submit' => 'Sign up',
        'have-account' => 'Already have an account? Sign in',
        'back-to-login' => 'Back to sign in',
        'no-account' => 'No account yet?',
        'create-account' => 'Sign up',
        'check-inbox' => 'Your account was created. A verification link was sent to your e-mail; please check your inbox (and spam folder).',
        'awaiting-approval' => 'Your account was created. You can sign in once an administrator activates it.',
        'created' => 'Your account was created. You can sign in now.',
        'mail-failed' => 'The account was created but the verification e-mail could not be sent. Please use "Resend verification e-mail" later or contact an administrator.',
        'role-description' => 'Default role of self-registered users (editable under Settings → Roles).',
    ],

    'verification' => [
        'not-verified' => 'Your e-mail address is not verified yet. Click the link in the verification e-mail or request a new one.',
        'resend-link' => 'Did not get the verification e-mail? Resend it',
        'resend-title' => 'Resend verification e-mail',
        'resend-info' => 'Enter the address you signed up with and we will send a new verification link.',
        'resend-submit' => 'Send link',
        'resent' => 'If an unverified account exists for this address, a new verification link was sent.',
        'verified' => 'Your e-mail address is verified. You can sign in now.',
        'verified-awaiting-approval' => 'Your e-mail address is verified. You can sign in once an administrator activates your account.',
        'already-verified' => 'This e-mail address is already verified.',
    ],

    'emails' => [
        'test' => [
            'subject' => ':app test e-mail',
            'heading' => 'E-mail settings work',
            'body' => 'This is a test message from :app. If you can read it, the company SMTP account is configured correctly.',
            'sent-at' => 'Sent at: :date',
        ],

        'verify' => [
            'subject' => 'Verify your e-mail for :app',
            'greeting' => 'Hello :name,',
            'body' => 'Please confirm your e-mail address to activate your :app account.',
            'button' => 'Verify e-mail',
            'expires' => 'This link is valid for :hours hours. If the button does not work, open this address in your browser:',
            'ignore' => 'If you did not sign up, you can ignore this e-mail.',
        ],

        'pending' => [
            'subject' => 'New account awaiting activation — :app',
            'body' => ':name (:email) signed up and is waiting for the account to be activated.',
            'button' => 'Manage users',
        ],
    ],
];

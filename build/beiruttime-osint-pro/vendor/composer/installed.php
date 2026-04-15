<?php return array(
    'root' => array(
        'name' => 'beiruttime/osint-pro',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => 'a2eaca288ca970939e873bec0b64d37dc2a563bd',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'beiruttime/osint-pro' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => 'a2eaca288ca970939e873bec0b64d37dc2a563bd',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);

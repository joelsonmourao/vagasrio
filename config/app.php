<?php

$adsEnabledEnv = getenv('ADS_ENABLED');
$adsEnabled = $adsEnabledEnv === false ? true : in_array(strtolower((string) $adsEnabledEnv), ['1', 'true', 'yes'], true);

return [
    'env' => getenv('APP_ENV') ?: 'development',
    'site' => [
        'name' => getenv('SITE_NAME') ?: 'Vagas RJ',
        'subtitle' => 'Empregos no Rio de Janeiro',
        'base_url' => rtrim((string) (getenv('SITE_BASE_URL') ?: 'http://localhost:8000'), '/'),
        'main_uf' => 'RJ',
        'main_state_name' => 'Rio de Janeiro',
        'default_city' => 'Rio de Janeiro',
        'allowed_cities' => [
            'Rio de Janeiro',
            'Niterói',
            'São Gonçalo',
            'Duque de Caxias',
            'Nova Iguaçu',
            'Petrópolis',
            'Volta Redonda',
            'Campos dos Goytacazes',
            'Cabo Frio',
            'Macaé',
            'Itaboraí',
            'Belford Roxo',
        ],
        'timezone' => 'America/Sao_Paulo',
    ],
    'jobs' => [
        'default_valid_days' => 30,
        'per_page' => 12,
        'import_max_mb' => 10,
    ],
    'admin' => [
        'username' => getenv('ADMIN_USERNAME') ?: 'admin',
        'password' => getenv('ADMIN_PASSWORD') ?: 'admin123',
        'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: '',
    ],
    'ads' => [
        // Ative/desative todos os anúncios: ADS_ENABLED=true|false ou 'enabled' abaixo
        'enabled' => $adsEnabled,
        // ID do publisher (ca-pub): ADSENSE_CLIENT_ID no .env ou valor abaixo
        'adsense_client_id' => getenv('ADSENSE_CLIENT_ID') ?: 'ca-pub-4279201625870524',
        // Placeholders discretos em dev quando slots ainda não estão configurados
        'show_placeholders_in_dev' => in_array(strtolower((string) (getenv('SHOW_AD_PLACEHOLDERS_IN_DEV') ?: '0')), ['1', 'true', 'yes'], true),
        'enabled_pages' => [
            'home' => true,
            'jobs' => true,
            'job_detail' => true,
            'blog' => true,
            'article' => true,
            'city' => false,
            'category' => false,
            'company' => false,
            'institutional' => false,
            'admin' => false,
            'error' => false,
        ],
        // Slots centralizados — altere aqui ou via ADSENSE_SLOT_* no .env
        'slots' => [
            'home_after_hero' => getenv('ADSENSE_SLOT_HOME') ?: '1111111111',
            'home_between_sections' => getenv('ADSENSE_SLOT_HOME_SECOND') ?: '1111111112',
            'listing_inline' => getenv('ADSENSE_SLOT_LISTING') ?: '1111111113',
            'listing_sidebar' => getenv('ADSENSE_SLOT_LISTING_SIDEBAR') ?: '1111111114',
            'job_after_main' => getenv('ADSENSE_SLOT_JOB_DETAIL') ?: '1111111115',
            'job_before_related' => getenv('ADSENSE_SLOT_JOB_DETAIL_SECOND') ?: '1111111116',
            'blog_after_intro' => getenv('ADSENSE_SLOT_BLOG') ?: '1111111117',
            'blog_middle' => getenv('ADSENSE_SLOT_BLOG_MIDDLE') ?: '1111111118',
            'blog_after_content' => getenv('ADSENSE_SLOT_BLOG_END') ?: '1111111119',
        ],
    ],
    'security' => [
        'session_name' => 'portal_vagas_session',
    ],
];

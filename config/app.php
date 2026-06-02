<?php

$adsEnabledEnv = getenv('ADS_ENABLED');
$adsEnabled = $adsEnabledEnv === false ? false : in_array(strtolower((string) $adsEnabledEnv), ['1', 'true', 'yes'], true);

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
        // CEP genérico (centro) por cidade — usado só no JobPosting quando não há endereço completo
        'city_postal_codes' => [
            'Rio de Janeiro' => '20040-020',
            'Niterói' => '24020-041',
            'São Gonçalo' => '24440-000',
            'Duque de Caxias' => '25020-010',
            'Nova Iguaçu' => '26220-010',
            'Petrópolis' => '25610-010',
            'Volta Redonda' => '27253-000',
            'Campos dos Goytacazes' => '28010-010',
            'Cabo Frio' => '28905-000',
            'Macaé' => '27910-010',
            'Itaboraí' => '24800-000',
            'Belford Roxo' => '26113-010',
        ],
        'timezone' => 'America/Sao_Paulo',
        'contact_email' => getenv('SITE_CONTACT_EMAIL') ?: 'contato@vagasrj.rio.br',
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
            'city' => true,
            'category' => true,
            'company' => true,
            'institutional' => false,
            'admin' => false,
            'error' => false,
        ],
        // Slots centralizados — altere aqui ou via ADSENSE_SLOT_* no .env
        'slots' => [
            'home_after_hero' => getenv('ADSENSE_SLOT_HOME') ?: '',
            'home_between_sections' => getenv('ADSENSE_SLOT_HOME_SECOND') ?: '',
            'listing_inline' => getenv('ADSENSE_SLOT_LISTING') ?: '',
            'listing_sidebar' => getenv('ADSENSE_SLOT_LISTING_SIDEBAR') ?: '',
            'job_after_main' => getenv('ADSENSE_SLOT_JOB_DETAIL') ?: '',
            'job_sidebar' => getenv('ADSENSE_SLOT_JOB_SIDEBAR') ?: '',
            'job_before_related' => getenv('ADSENSE_SLOT_JOB_DETAIL_SECOND') ?: '',
            'blog_after_intro' => getenv('ADSENSE_SLOT_BLOG') ?: '',
            'blog_listing_inline' => getenv('ADSENSE_SLOT_BLOG_LISTING') ?: '',
            'blog_middle' => getenv('ADSENSE_SLOT_BLOG_MIDDLE') ?: '',
            'blog_after_content' => getenv('ADSENSE_SLOT_BLOG_END') ?: '',
            'blog_sidebar' => getenv('ADSENSE_SLOT_BLOG_SIDEBAR') ?: '',
            'article_sidebar' => getenv('ADSENSE_SLOT_ARTICLE_SIDEBAR') ?: '',
            'city_inline' => getenv('ADSENSE_SLOT_CITY') ?: '',
            'category_inline' => getenv('ADSENSE_SLOT_CATEGORY') ?: '',
            'company_inline' => getenv('ADSENSE_SLOT_COMPANY') ?: '',
        ],
    ],
    'security' => [
        'session_name' => 'portal_vagas_session',
    ],
];

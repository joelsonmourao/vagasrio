<?php

declare(strict_types=1);

namespace App\Services;

final class BlogArticleBuilder
{
    private const MIN_WORDS = 700;

    /** @var list<string> */
    private const H2_HEADERS = [
        'Por que este tema importa na sua busca por emprego',
        'Contexto do mercado de trabalho no Rio de Janeiro',
        'Passo a passo prático para candidatos',
        'Erros que vale a pena evitar',
        'Como organizar sua rotina de candidatura',
        'O que empresas costumam observar no processo',
        'Cuidados com segurança e informações confiáveis',
        'Como usar portais regionais de forma inteligente',
        'Planejamento para entrevistas e retornos',
        'Checklist antes de avançar na candidatura',
        'Próximos passos depois de aplicar as dicas',
    ];

    /** @var list<string> */
    private const H3_HEADERS = [
        'Organização e clareza',
        'Comunicação com recrutadores',
        'Documentação e comprovantes',
        'Pesquisa sobre a oportunidade',
        'Adaptação ao perfil da vaga',
        'Persistência sem desgaste',
    ];

    /** @var list<string> */
    private const PARAGRAPH_TEMPLATES = [
        'Quem busca oportunidades no {estado} precisa tratar cada candidatura com atenção. O tema <strong>{titulo}</strong> ajuda a estruturar essa etapa sem promessas irreais: o mercado varia por cidade, setor e momento, e o candidato ganha quando combina preparo, pesquisa e consistência.',
        'No {portal}, as vagas divulgadas são oportunidades encontradas ou cadastradas por terceiros. Isso significa que você deve sempre conferir dados no link ou canal oficial da empresa antes de enviar documentos ou aceitar propostas. Essa postura reduz riscos e melhora a qualidade das suas escolhas.',
        'Na categoria <strong>{categoria}</strong>, o foco é oferecer orientação prática. Não existe fórmula mágica de contratação imediata; existe processo. Registrar onde se candidatou, quais versões de currículo usou e quais retornos recebeu facilita aprender com cada tentativa.',
        'Para quem mora ou pretende trabalhar no {estado}, vale considerar deslocamento, horários de transporte e custo de vida. Uma vaga atrativa no papel pode exigir planejamento logístico. Comparar opções por região evita surpresas depois da entrevista.',
        'Empresas do {estado} costumam valorizar objetividade: informações verdadeiras, histórico coerente e respostas diretas. Exagerar experiência ou omitir períodos sem trabalho tende a ser identificado em entrevistas ou checagens. Transparência constrói confiança.',
        'Antes de clicar em qualquer botão de candidatura, leia a descrição completa da vaga, confira se a cidade pertence ao {uf} e verifique se o contato é compatível com a empresa. Links estranhos, pedidos de pagamento antecipado ou pressa excessiva são sinais de alerta.',
        'Se você está começando agora, priorize vagas de entrada compatíveis com seu perfil e invista em cursos gratuitos ou certificações reconhecidas. Isso não substitui experiência, mas mostra iniciativa — especialmente em processos com muitos candidatos.',
        'Para candidatos com histórico anterior, reorganizar conquistas por resultado (números, prazos, metas) costuma funcionar melhor do que listar apenas tarefas. Mesmo em áreas operacionais ou de atendimento, exemplos concretos ajudam o recrutador a entender seu impacto.',
        'Entrevistas presenciais e online exigem testes diferentes: no presencial, pontualidade e postura pesam; no online, ambiente silencioso, conexão estável e enquadramento profissional fazem diferença. Ensaiar respostas curtas sobre o tema <strong>{titulo}</strong> deixa você mais seguro.',
        'Sobre pretensão salarial, pesquise faixas realistas para a função e região. Informar uma faixa compatível com o mercado evita descarte automático, mas também evita expectativas incompatíveis com a vaga divulgada.',
        'Em processos de Jovem Aprendiz e estágio, as regras de contratação são específicas. Confirme modalidade, carga horária, supervisão e documentação exigida. Vagas que misturam promessas vagas com cobrança de taxa devem ser descartadas.',
        'A rotina diária de busca funciona melhor com metas realistas: revisar novas oportunidades, adaptar materiais, enviar candidaturas qualificadas e registrar retornos. Volume sem critério gera desgaste; critério sem constância reduz chances.',
        'Grupos de mensagens e redes sociais podem divulgar oportunidades, mas também propagar anúncios falsos. Cruce informações com site da empresa, CNPJ quando disponível e descrições consistentes. Desconfie de mensagens genéricas pedindo dados bancários.',
        'Ao usar e-mail ou WhatsApp, mantenha mensagem curta, cordial e com arquivo legível (PDF nomeado com seu nome e área). Evite áudios longos em primeiro contato e não compartilhe documentos além do necessário nesta fase.',
        'Depois de enviar candidatura, guarde comprovante: print, protocolo, data e canal. Se houver entrevista, anote perguntas feitas e próximos passos combinados. Esse histórico ajuda em follow-up educado, sem insistência excessiva.',
        'Benefícios, horários e local de trabalho devem constar na proposta ou ser confirmados por escrito antes da decisão final. Promessas verbais sem detalhe geram conflito depois. Em caso de dúvida, busque orientação em fontes oficiais de direitos do trabalho.',
        'O mercado fluminense tem demanda recorrente em comércio, serviços, logística e atendimento, além de áreas administrativas e tecnologia em hubs específicos. Acompanhar categorias e cidades separadamente amplia visão sem dispersar esforço.',
        'Crescer na carreira é acumular aprendizado de processos seletivos: o que pediram, o que faltou no seu perfil, o que você pode estudar nas próximas semanas. Feedback, quando disponível, é insumo — não julgamento definitivo sobre sua capacidade.',
        'Por fim, lembre que divulgação de vaga não garante contratação. O {portal} apoia sua busca com conteúdo e listagens, mas a decisão é da empresa contratante. Persistência organizada, respeito às regras e cautela com golpes são a base sustentável.',
    ];

    /** @var list<string> */
    private const LIST_ITEM_TEMPLATES = [
        'Leia o anúncio completo e confirme cidade, função e requisitos.',
        'Pesquise a empresa em canais oficiais antes de enviar documentos.',
        'Adapte currículo e mensagem ao perfil da vaga, sem inventar experiências.',
        'Use link ou e-mail de candidatura indicado na própria vaga.',
        'Registre data e canal de cada candidatura enviada.',
        'Desconfie de pedidos de pagamento para participar de seleção.',
        'Mantenha dados de contato atualizados e profissionais.',
        'Prepare exemplos curtos de situações reais vividas no trabalho ou estudo.',
        'Revise ortografia e formatação antes de anexar arquivos.',
        'Defina meta diária realista de candidaturas qualificadas.',
        'Guarde comprovantes e retornos para acompanhar o processo.',
        'Em entrevista, leve anotações sobre a vaga e perguntas pertinentes.',
        'Após a entrevista, agradeça e confirme próximos passos com educação.',
        'Compare deslocamento e horário com sua rotina atual.',
        'Consulte conteúdos da categoria {categoria} para aprofundar o tema.',
    ];

    /** @var list<string> */
    private const CITY_PARAGRAPHS = [
        'Em <strong>{cidade}</strong>, a oferta de vagas costuma refletir o perfil econômico local: comércio, serviços, indústria ou turismo, conforme a região. Filtrar oportunidades por essa cidade evita candidaturas incompatíveis com seu deslocamento.',
        'Ao buscar emprego em <strong>{cidade}</strong>, combine o portal com pesquisa em bairros ou polos próximos. Muitas empresas divulgam a mesma função em mais de um ponto de atendimento; verifique endereço e horário na descrição.',
        'Candidatos de <strong>{cidade}</strong> podem ampliar opções avaliando vagas em municípios vizinhos da região metropolitana ou interior, desde que o trajeto seja viável. Transparência sobre disponibilidade de horário é essencial na entrevista.',
        'Para o tema <strong>{titulo}</strong>, priorize anúncios que mencionem explicitamente {cidade} ou região compatível. Isso melhora o alinhamento com recrutadores e reduz ruído em processos que exigem presença local.',
    ];

    /**
     * @param array<string, mixed> $options
     * @return array{content: string, excerpt: string, seo_title: string, seo_description: string}
     */
    public static function build(string $title, string $categoryName, string $categorySlug, array $options = []): array
    {
        $city = isset($options['city']) && is_string($options['city']) && $options['city'] !== ''
            ? $options['city']
            : self::extractCityFromTitle($title, $categorySlug);

        $vars = [
            'titulo' => $title,
            'categoria' => $categoryName,
            'cidade' => $city ?? 'sua cidade no RJ',
            'estado' => (string) config('site.main_state_name', 'Rio de Janeiro'),
            'uf' => (string) config('site.main_uf', 'RJ'),
            'portal' => (string) config('site.name', 'Vagas RJ'),
        ];

        $content = self::assembleContent($title, $categorySlug, $vars, $city);
        $plain = self::htmlToPlainText($content);

        while (self::wordCount($plain) < self::MIN_WORDS) {
            $content .= self::extraSection($title, $vars, (int) crc32($title . '|extra|' . strlen($content)));
            $plain = self::htmlToPlainText($content);
        }

        $excerpt = self::buildExcerpt($plain);
        $seoTitle = self::buildSeoTitle($title, $vars['portal']);
        $seoDescription = self::buildSeoDescription($plain, $title, $vars);

        return [
            'content' => $content,
            'excerpt' => $excerpt,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
        ];
    }

    /**
     * @param array<string, string> $vars
     */
    private static function assembleContent(string $title, string $categorySlug, array $vars, ?string $city): string
    {
        $hash = crc32($title);
        $parts = [];

        $parts[] = '<h2>' . self::e(self::fillTemplate(self::H2_HEADERS[$hash % count(self::H2_HEADERS)], $vars)) . '</h2>';
        $parts[] = '<p>' . self::fillTemplate(self::pickParagraph($title, 0), $vars) . '</p>';
        $parts[] = '<p>' . self::fillTemplate(self::pickParagraph($title, 1), $vars) . '</p>';

        if ($city !== null && $categorySlug === 'vagas-por-cidade') {
            $parts[] = '<h2>Oportunidades em ' . self::e($city) . '</h2>';
            foreach ([0, 1, 2] as $offset) {
                $parts[] = '<p>' . self::fillTemplate(self::CITY_PARAGRAPHS[(($hash >> 4) + $offset) % count(self::CITY_PARAGRAPHS)], $vars) . '</p>';
            }
        }

        $sectionCount = 5;
        for ($i = 0; $i < $sectionCount; $i++) {
            $sectionHash = crc32($title . '|section|' . $i);
            $parts[] = '<h2>' . self::e(self::fillTemplate(self::H2_HEADERS[($sectionHash >> 3) % count(self::H2_HEADERS)], $vars)) . '</h2>';
            $parts[] = '<h3>' . self::e(self::fillTemplate(self::H3_HEADERS[($sectionHash >> 5) % count(self::H3_HEADERS)], $vars)) . '</h3>';
            $parts[] = '<p>' . self::fillTemplate(self::pickParagraph($title, $i + 2), $vars) . '</p>';
            $parts[] = '<p>' . self::fillTemplate(self::pickParagraph($title, $i + 7), $vars) . '</p>';
            $parts[] = '<ul>';
            $listSize = 5 + ($sectionHash % 3);
            for ($j = 0; $j < $listSize; $j++) {
                $item = self::LIST_ITEM_TEMPLATES[($sectionHash + $j) % count(self::LIST_ITEM_TEMPLATES)];
                $parts[] = '<li>' . self::fillTemplate($item, $vars) . '</li>';
            }
            $parts[] = '</ul>';
        }

        $parts[] = '<h2>Considerações finais sobre ' . self::e($title) . '</h2>';
        $parts[] = '<p>' . self::fillTemplate(self::pickParagraph($title, 20), $vars) . '</p>';
        $parts[] = '<p>' . self::fillTemplate(self::pickParagraph($title, 21), $vars) . '</p>';
        $parts[] = '<p><strong>Importante:</strong> o ' . self::e($vars['portal']) . ' divulga oportunidades e não garante contratação, não cobra taxa de candidatura e não substitui canais oficiais das empresas. Use este conteúdo como apoio na sua busca no ' . self::e($vars['estado']) . '.</p>';

        return implode("\n", $parts);
    }

    /**
     * @param array<string, string> $vars
     */
    private static function extraSection(string $title, array $vars, int $seed): string
    {
        $h2 = self::H2_HEADERS[$seed % count(self::H2_HEADERS)];
        $p1 = self::pickParagraph($title, $seed);
        $p2 = self::pickParagraph($title, $seed + 11);

        return '<h2>' . self::e(self::fillTemplate($h2, $vars)) . '</h2>'
            . '<p>' . self::fillTemplate($p1, $vars) . '</p>'
            . '<p>' . self::fillTemplate($p2, $vars) . '</p>';
    }

    private static function pickParagraph(string $title, int $offset): string
    {
        $index = (int) (crc32($title . '|p|' . $offset) % count(self::PARAGRAPH_TEMPLATES));

        return self::PARAGRAPH_TEMPLATES[$index];
    }

    /**
     * @param array<string, string> $vars
     */
    private static function fillTemplate(string $template, array $vars): string
    {
        $out = $template;
        foreach ($vars as $key => $value) {
            $out = str_replace('{' . $key . '}', $value, $out);
        }

        return $out;
    }

    private static function extractCityFromTitle(string $title, string $categorySlug): ?string
    {
        if ($categorySlug !== 'vagas-por-cidade') {
            return null;
        }

        $cities = config('site.allowed_cities', []);
        if (!is_array($cities)) {
            return null;
        }

        foreach ($cities as $city) {
            if (!is_string($city)) {
                continue;
            }
            if (stripos($title, $city) !== false) {
                return $city;
            }
        }

        return null;
    }

    private static function htmlToPlainText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private static function wordCount(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return $words === false ? 0 : count($words);
    }

    private static function buildExcerpt(string $plain): string
    {
        $max = 160;
        if (mb_strlen($plain) <= $max) {
            return $plain;
        }

        $cut = mb_substr($plain, 0, $max - 3);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > 80) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut) . '...';
    }

    private static function buildSeoTitle(string $title, string $portal): string
    {
        $suffix = ' | ' . $portal;
        $max = 60;
        if (mb_strlen($title . $suffix) <= $max) {
            return $title . $suffix;
        }

        $available = $max - mb_strlen($suffix) - 3;

        return mb_substr($title, 0, max(20, $available)) . '...' . $suffix;
    }

    /**
     * @param array<string, string> $vars
     */
    private static function buildSeoDescription(string $plain, string $title, array $vars): string
    {
        $intro = 'Guia sobre ' . $title . ' para candidatos no ' . $vars['estado'] . '. ';
        $max = 160;
        $body = $plain;
        if (mb_strlen($intro . $body) > $max) {
            $remaining = $max - mb_strlen($intro) - 3;
            if ($remaining > 40) {
                $body = mb_substr($body, 0, $remaining) . '...';
            } else {
                $body = '';
            }
        }

        return trim($intro . $body);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

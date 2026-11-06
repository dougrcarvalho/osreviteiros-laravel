<?php

return [
    // Links de checkout por plano (Hotmart: pagos, Eduzz: grátis)
    'checkout' => [
        'gratis' => env('CHECKOUT_GRATIS_URL'),
        'basic' => env('CHECKOUT_BASIC_URL'),
        'plus' => env('CHECKOUT_PLUS_URL'),
        'premium' => env('CHECKOUT_PREMIUM_URL'),
    ],

    // Arquivos (.zip/.rfa) enviados pelo painel
    'arquivos' => [
        'disk' => env('REVITEIROS_ARQUIVOS_DISK', 'local'), // local (VPS, privado) | s3
        'max_mb' => (int) env('REVITEIROS_ARQUIVOS_MAX_MB', 500),
    ],

    // Imagens de capa / logos enviadas pelo painel (precisam ser públicas)
    'imagens' => [
        'disk' => env('REVITEIROS_IMAGENS_DISK', 'public'),
        'max_mb' => 10,
    ],

    // Site WordPress de origem (importação; mídia de rascunhos exige senha de aplicativo)
    'wp' => [
        'url' => env('WP_URL', 'https://osreviteiros.com.br'),
        'usuario' => env('WP_API_USER'),
        'senha_app' => env('WP_API_APP_PASSWORD'),
    ],

    // Página inicial (textos aprovados em reviteiros-design/design/home.html).
    // Valores entre colchetes são dados que ainda faltam.
    'home' => [
        // Número exibido no hero, no meta description e no link "Ver as … famílias"
        'total_familias' => 5422,

        'plugin_url' => env('REVITEIROS_PLUGIN_URL', '#'),
        'whatsapp_url' => env('REVITEIROS_WHATSAPP_URL', '#'),
        'cnpj' => '[Nº]',
        'og_image' => null, // URL absoluta 1200×630; sem valor usa o logo

        // Vídeo do plugin: mp4/webm (+ poster) OU id do YouTube (carregado só no clique)
        'video' => [
            'mp4' => null,
            'webm' => null,
            'poster' => null,
            'youtube' => env('REVITEIROS_PLUGIN_YOUTUBE'),
            'titulo' => 'O plugin Os Reviteiros inserindo uma família no Revit',
        ],

        // Atalhos do hero: termo buscado e biblioteca (TipoItem)
        'buscas' => [
            ['termo' => 'Bacia sanitária', 'biblioteca' => 'familia'],
            ['termo' => 'Cuba de apoio', 'biblioteca' => 'familia'],
            ['termo' => 'Geladeira', 'biblioteca' => 'familia'],
            ['termo' => 'Porcelanato', 'biblioteca' => 'textura'],
            ['termo' => 'Projeto pronto 2 quartos', 'busca' => '2 quartos', 'biblioteca' => 'projeto'],
        ],

        // Faixa de marcas: o link só é gerado se o slug existir em `fabricantes`.
        // `logo` = caminho em public/ (sem logo, mostra o nome)
        'fabricantes' => [
            ['nome' => 'Deca', 'slug' => 'deca', 'logo' => 'images/logos/16_Deca.png'],
            ['nome' => 'Docol', 'slug' => 'docol', 'logo' => 'images/logos/11_apple-4.png'],
            ['nome' => 'Electrolux', 'slug' => 'electrolux', 'logo' => 'images/logos/14_electrolux-60.svg'],
            ['nome' => 'LG', 'slug' => 'lg', 'logo' => 'images/logos/13_lg-84.svg'],
            ['nome' => 'Amanco Wavin', 'slug' => 'amanco-wavin', 'logo' => 'images/logos/02_Amanco-Wavin.png'],
            ['nome' => 'Brasilit', 'slug' => 'brasilit', 'logo' => 'images/logos/05_brasilit.png'],
            ['nome' => 'Natura', 'slug' => 'natura', 'logo' => 'images/logos/12_natura-108.svg'],
            ['nome' => 'Aurora', 'slug' => 'aurora', 'logo' => 'images/logos/04_aurora.png'],
            ['nome' => 'Fritz Hansen', 'slug' => 'fritz-hansen', 'logo' => 'images/logos/19_Fritz-Hansen.png'],
        ],

        // "Navegue por ambiente": slug = termo da taxonomia `cat` de Famílias.
        // `imagem` = caminho em public/ (png, jpg ou webp; ex.: images/ambientes/banheiro.jpg)
        'categorias' => [
            ['nome' => 'Banheiro', 'slug' => 'banheiro', 'destaque_mobile' => true, 'imagem' => 'images/ambientes/webp/banheiro.webp'],
            ['nome' => 'Cozinha', 'slug' => 'cozinha', 'destaque_mobile' => true, 'imagem' => 'images/ambientes/webp/cozinha.webp'],
            ['nome' => 'Sala de estar', 'slug' => 'sala-de-estar', 'destaque_mobile' => true, 'imagem' => 'images/ambientes/webp/sala-de-estar.webp'],
            ['nome' => 'Dormitório', 'slug' => 'dormitorio', 'destaque_mobile' => true, 'imagem' => 'images/ambientes/webp/dormitorio.webp'],
            ['nome' => 'Escritório', 'slug' => 'escritorio', 'imagem' => 'images/ambientes/webp/escritorio.webp'],
            ['nome' => 'Área externa', 'slug' => 'area-externa', 'imagem' => 'images/ambientes/webp/area-externa.webp'],
            ['nome' => 'Hidrossanitário', 'slug' => 'hidrossanitario', 'destaque_mobile' => true, 'imagem' => 'images/ambientes/webp/hidrossanitario.webp'],
            ['nome' => 'Elétrica', 'slug' => 'eletrica', 'destaque_mobile' => true, 'imagem' => 'images/ambientes/webp/eletrica.webp'],
            ['nome' => 'Iluminação', 'slug' => 'iluminacao', 'imagem' => 'images/ambientes/webp/iluminacao.webp'],
            ['nome' => 'Acessibilidade', 'slug' => 'acessibilidade', 'imagem' => 'images/ambientes/webp/acessibilidade.webp'],
            ['nome' => 'Portas e janelas', 'slug' => 'portas-e-janelas', 'imagem' => 'images/ambientes/webp/portas-e-janelas.webp'],
            ['nome' => 'Eletrodomésticos', 'slug' => 'eletrodomesticos', 'imagem' => 'images/ambientes/webp/eletrodomesticos.webp'],
        ],

        // Planos exibidos na home (o checkout vem de reviteiros.checkout.{slug})
        'planos' => [
            [
                'slug' => 'gratis',
                'nome' => 'Grátis',
                'descricao' => 'Para conhecer o plugin',
                'resumo' => 'Famílias da categoria gratuita, para conhecer o plugin.',
                'preco' => 0,
                'beneficios' => ['Famílias da categoria gratuita', 'Acesso pelo site e pelo plugin'],
                'cta' => 'Criar conta grátis',
                'destaque' => false,
                'ordem_mobile' => 3,
            ],
            [
                'slug' => 'basic',
                'nome' => 'Basic',
                'descricao' => 'Biblioteca completa de modelagem',
                'resumo' => 'Famílias e texturas ilimitadas, no site e no plugin.',
                'preco' => 19.97,
                'beneficios' => ['Famílias ilimitadas', 'Texturas ilimitadas', 'Site e plugin'],
                'cta' => 'Assinar Basic',
                'destaque' => false,
                'ordem_mobile' => 2,
            ],
            [
                'slug' => 'premium',
                'nome' => 'Premium',
                'descricao' => 'Biblioteca, projetos e formação',
                'preco' => 59.97,
                'beneficios' => [
                    'Tudo do Basic',
                    'Projetos prontos para prefeitura e financiamento',
                    'Templates Revit',
                    'Dimensionamento hidrossanitário',
                    'Pedidos de famílias novas',
                    '+37 cursos e +900 aulas',
                ],
                'cta' => 'Assinar Premium',
                'destaque' => true,
                'resumo' => null,
                'ordem_mobile' => 1,
            ],
        ],

        // Respostas entre colchetes são placeholders: aparecem na página, mas ficam fora do JSON-LD
        'faq' => [
            ['pergunta' => 'Como acesso as famílias depois de assinar?', 'resposta' => 'Você recebe dois e-mails com os dados de acesso. É só entrar no plugin dentro do Revit ou no site com o mesmo login.'],
            ['pergunta' => 'Funciona no Revit LT?', 'resposta' => '[Confirmar a resposta atual do site sobre compatibilidade e versões suportadas.]'],
            ['pergunta' => 'Posso pagar com Pix?', 'resposta' => 'Sim. O Pix recorrente é feito pelo suporte via WhatsApp; no cartão a assinatura é automática.'],
            ['pergunta' => 'Posso cancelar quando quiser?', 'resposta' => 'Sim, a qualquer momento. O acesso é encerrado no cancelamento. Na primeira compra há reembolso em até 7 dias.'],
            ['pergunta' => 'E se eu não encontrar a família que preciso?', 'resposta' => 'No plano Premium você pede a família e a equipe modela. Empresas podem contratar prioridade nos pedidos.'],
            ['pergunta' => 'Os cursos estão inclusos?', 'resposta' => 'Sim, no Premium: mais de 37 cursos e 900 aulas, do Revit básico ao MEP, estrutural e renderização.'],
        ],

        // Depoimentos: só ligar quando houver depoimentos reais. Itens `ilustrativo`
        // nunca são publicados (aparecem só em ambiente local, com aviso).
        'mostrar_depoimentos' => false,
        'depoimentos' => [
            ['texto' => 'Eu perdia uma tarde procurando louças e metais em site gringo. Hoje busco no plugin e a bacia já entra no projeto com o material certo.', 'nome' => 'Camila Ferraz', 'cargo' => 'Arquiteta de interiores · Curitiba/PR', 'ilustrativo' => true],
            ['texto' => 'Os projetos prontos mudaram minha rotina com prefeitura. Adapto a planta, ajusto as pranchas e entrego muito mais rápido.', 'nome' => 'Rodrigo Almeida', 'cargo' => 'Projetista · Uberlândia/MG', 'ilustrativo' => true],
            ['texto' => 'Pedi uma família que eu não encontrava em lugar nenhum e a equipe modelou. Só isso já vale o Premium.', 'nome' => 'Juliana Prado', 'cargo' => 'Engenheira civil · Recife/PE', 'ilustrativo' => true],
            ['texto' => 'Comecei no plano grátis só para testar o plugin. Acabei assinando o Premium por causa dos cursos de MEP.', 'nome' => 'Thiago Nunes', 'cargo' => 'Estudante de arquitetura · Goiânia/GO', 'ilustrativo' => true],
        ],
    ],
];

/*
'faq' => [
    [
        'pergunta' => 'Como vou receber o acesso?',
        'resposta' => 'As informações de acesso são enviadas por e-mail logo após a confirmação de pagamento. Se você já tem acesso ao plano gratuito, após a confirmação do pagamento, o upgrade será feito automaticamente.'
    ],
    [
        'pergunta' => 'Assinei o plano gratuito, em algum momento serei cobrado?',
        'resposta' => 'Não, o acesso gratuito ao plugin não tem nenhuma cobrança, você só paga se optar por algum dos planos pagos'
    ],
    [
        'pergunta' => 'Esse Plugin é só para engenheiros e arquitetos?',
        'resposta' => 'Este plugin é essencial para você estudante arquiteto(a), engenheiro(a) ou técnico(a) e desenhista do setor da Construção Civil, mesmo que você tenha pouco ou nenhum conhecimento sobre o tema.'
    ],
    [
        'pergunta' => 'Não tenho o Revit, e agora?',
        'resposta' => 'Temos parcerias que permitem o acesso a uma aula com o passo a passo para baixar o Revit diretamente do site oficial da Autodesk, com uma licença válida por um ano.'
    ],
    [
        'pergunta' => 'Tenho o Revit LT, vou conseguir baixar as famílias?',
        'resposta' => 'Se você tem o Revit LT, você pode baixar as famílias diretamente do nosso site.'
    ],
    [
        'pergunta' => 'Vou ter suporte para tirar dúvidas?',
        'resposta' => 'Sim, além de ter suporte quanto ao acesso à plataforma, você terá suporte quanto aos conteúdos das aulas enquanto você for assinante.'
    ],
    [
        'pergunta' => 'Posso pagar por pix?',
        'resposta' => 'Sim, caso não tenha cartão de crédito, você pode pagar pelo pix recorrente que é uma opção da assinatura e todo mês será debitado esse valor até você cancelar. Entre em contato com o suporte do WhatsApp para solicitar essa forma de pagamento.'
    ],
    [
        'pergunta' => 'Por quanto tempo tenho acesso ao plano que adquiri?',
        'resposta' => 'Essa é uma assinatura mensal, você terá acesso enquanto for assinante de acordo com o plano escolhido.'
    ],
    [
        'pergunta' => 'Quais famílias posso solicitar?',
        'resposta' => 'As solicitações de famílias é exclusivo para o plano Premium do plugin, e são destinadas a itens que possam ser desenvolvidos e adicionados ao catálogo do nosso plugin, beneficiando também outros assinantes.

Desenvolvemos principalmente famílias voltadas para arquitetura, interiores e especificação de produtos, como: portas, janelas, mobiliários, marcenaria, decoração e acessórios, iluminação, eletrodomésticos, eletrônicos, louças e metais sanitários, chuveiros, torneiras, cubas, bacias sanitárias, ralos, acessórios para banheiro e cozinha, equipamentos e produtos de fabricantes, entre outros itens semelhantes.

Também podemos desenvolver famílias baseadas em produtos e marcas existentes no mercado, desde que sejam adequadas ao padrão e à proposta do nosso catálogo.

Não desenvolvemos famílias exclusivas para um projeto específico, elementos com dimensões ou características muito particulares que não tenham aplicação geral no catálogo, nem famílias de caráter predominantemente estrutural ou de engenharia, como grandes treliças, estruturas metálicas especiais, estruturas calculadas especificamente para uma obra, entre outros.

Também não desenvolvemos esculturas, estátuas, personagens ou objetos com modelagem artística/orgânica de alta complexidade e Logos especificas para a empresa.

Todas as solicitações passam por uma análise antes de serem aprovadas, considerando a complexidade da modelagem, utilidade para os demais usuários e compatibilidade com o padrão do plugin. Por isso, o envio de uma solicitação não garante obrigatoriamente o desenvolvimento da família.

A solicitação de família é uma sugestão para inclusão no catálogo e não um serviço de modelagem personalizada ou sob medida.'
    ],
    [
        'pergunta' => 'Quais os treinamentos vou ter acesso ao assinar o Premium',
        'resposta' => 'Arquitetônico (Revit)
Interiores (Revit)
Hidrossanitário - MEP (Revit)
Elétrico - MEP (Revit)
Modelagem Estrutural Prédio 4 Pavimentos (Revit)
Modelagem de Prédio de 6 Pavimentos (Revit)
Telhado Avançado (Revit)
Arquitetônico (Autocad)
Renderização e animação (Twinmotion)
Realidade Virtual Aumenta (Augin)
Móveis Planejados (Promob)
Cálculo Estrutural de residência (Eberick)
Cálculo Estrutural de sobrado (Eberick)
Cálculo Estrutural de prédio de 6 pavimentos (Eberick)
Cálculo Estrutural de Muro de Arrimo (Eberick)
Escadas Especiais (Eberick)
Radier (Eberick)
Inglês básico
Inglês intermediário
Inglês avançado
+ 20 Cursos complementares'
    ],
    [
        'pergunta' => 'Por que devo assinar esse plugin?',
        'resposta' => 'A assinatura proporciona mais agilidade e segurança no momento de projetar, colocando você à frente dos seus concorrentes. Com acesso a cursos completos, do nível básico ao avançado, você desenvolve conhecimento sólido e atualizado para trabalhar com mais eficiência. Além do conteúdo educacional, a plataforma oferece famílias, texturas e projetos prontos, todos desenvolvidos de acordo com as normas da ABNT e padrões de financiamento. Os projetos são totalmente editáveis, permitindo que você adapte tudo conforme a necessidade do seu projeto. Atualmente, a plataforma conta com mais de 37 cursos disponíveis, e os assinantes Premium também recebem acesso aos novos cursos e atualizações, garantindo sempre conteúdos atualizados e relevantes.'
    ],
    [
        'pergunta' => 'Posso cancelar a qualquer momento?',
        'resposta' => 'Sim. Você pode cancelar sua assinatura a qualquer momento. Ao realizar o cancelamento, não haverá novas renovações nem cobranças referentes aos períodos seguintes.

Atualmente, o cancelamento encerra o acesso à plataforma de forma imediata. Por isso, caso queira utilizar todo o período já contratado e pago, recomendamos que o cancelamento seja realizado próximo à data da próxima renovação.

Você terá acesso aos conteúdos da assinatura enquanto for assinante, sendo esta recorrente de acordo com o plano adquirido. A possibilidade de reembolso só se aplica à transação inicial dentro do prazo de 7 dias contados a partir da data da compra.'
    ],
    [
        'pergunta' => 'Sobre o pagamento',
        'resposta' => 'Os planos de assinatura são renovados automaticamente ao final de cada período contratado, seja mensal, semestral, anual ou outro período que o cliente tenha contratado, até que o cancelamento seja solicitado.

Na data de renovação, será realizada uma nova cobrança no meio de pagamento cadastrado. Para evitar a próxima cobrança, o cancelamento deve ser realizado antes da data de renovação.'
    ],
    [
        'pergunta' => 'REVIT - ARQUITETÔNICO PARA PREFEITURA',
        'resposta' => 'MÓDULO 01
Comece por aqui

MÓDULO 02
Requisitos mínimos e recomendações para instalar o Revit
Link para instalar o Windows em um Mac
Cadastro na Autodesk para estudantes e para não estudantes
Instalando e corrigindo erro de instalação do Revit
Corrindo problemas no Windows que atrapalham instalação do Revit
Novidades do Revit
Novidades Revit – Criação de paredes elípticas
Novidades Revit- Janela de Materiais

MÓDULO 03
Interface do Revit
Unidades, propriedades e navegador de projeto

MÓDULO 04
Barra de Navegação no 2D, Barra de Comandos, Mouse e Alterar linguagem Revit
Barra de Navegação no 3D
ViewCube
Modos de uso básico (informações sobre paredes, portas, janelas, etc.)
Atalhos do teclado
Modos de seleção
Famílias, tipos e categorias
Gerenciando janelas e vistas
Controle de visibilidade

MÓDULO 05
Comando VG, Ocultar e Isolar famílias e categoria
Filtro e Backup
Criando seu próprio template e ferramenta Purge

MÓDULO 06
Introdução hidrossanitário + Templates + Cátalogos
Introdução – Arquivo de Modelo (Template)
Introdução- Arquivos RTE, RVT, RFA

MÓDULO 07
Conhecendo o projeto arquitetônico
Carregando o arquivo arquitetônico
Copiando elementos da arquitetura
Faixa de vista

MÓDULO 08
Rede bacia sanitária – Lavabo
Caixa sifonada – Lavabo
Caixa de inspeção – Lavabo

MÓDULO 09
Esgoto Lavanderia
Esgoto máquina de lavar parte 1
Esgoto máquina de lavar parte 2
Esgoto Tanque
Esgoto Tanque Parte 2
Esgoto Pia da cozinha
Visão geral catálogo esgoto série normal
Inserindo adaptador do vaso sanitário

MÓDULO 10
Esgoto pav superior parte 1
Instalações esgoto pav superior parte 2
Inserindo caixas sinfonadas
Inserindo ralo linear
Conexão entre ralo e caixa sinfonada
WC Suite
Finalizando conexão

MÓDULO 11
Sistema de ventilação
Sistema de ventilação parte 2
Sistema de ventilação parte 3
Sistema de ventilação parte 4

MÓDULO 12
Instalação de água quente e água fria
Instalações lavanderia
Instalações – cátalogo de água fria
Instalações banheiro e cozinha
Instalações banheiro e cozinha 2
Instalações catálogo água quente
Instalações banheiro água fria e quente
Banheiro social água quente e fria pav sup
Banheiro social parte 2
Misturador banheiro social
Misturador monocomando banheiro social
Banheiro suíte
Ajuste da tubulação
Alimentação bacia santinária
Ponto de alimentação jardim
Fossa, Filtro e Sumidouro
Traçado do Barrilete
Inserção dos Registros
Inserção tubulação água quente
Inserção registro de água quente
Ponto de atendimento água fria
Ponto de atendimento água fria Banheiro
Ponto de atendimento água fria térreo
Ponto de água fria térreo Parte 2
Ponto de atendimento água fria cozinha
Ponto de atendimento água quente Banheiro
Atendimenro água quente banheiro superior
Inserção caixa dágua, boiler e placa solar
Conexão placa solar e boiler
Conexão caixa dágua com pontos de alimentação

MÓDULO 13
Águas pluviais parte 1
Águas pluvais parte 2
Águas pluviais parte 3
Águas pluviais parte 4
Águas pluviais parte 5
Águas pluviais parte 6
Águas pluviais parte 7
Águas pluviais parte 8

MÓDULO 14
Planta de detalhamento
Planta de detalhamento água pluvial
Planta de detalhamento água pluvial parte 2
Planta de detalhamento água pluvial parte 3
Planta de detalhamento água pluvial parte 4
Planta de detalhamento anotação água pluvial
Aplicando filtro de detalhamento
Anotações recarregando vínculo
Anotações, macete para execução
Ocultando água quente e água fria
Corrigindo erro na anotação
Detalhamento esg sanitário pav térreo
Detalhamento Vista 3D realista parte 1
Detalhamento Vista 3D realista parte 2
Detalhamento Vista 3D realista parte 3
Detalhamento Vista 3D realista parte 4
Detalhaento de vista 3D parte 5
Detalhamento de vista 3D parte 6
Detalhamento de vista 3D Piso parte 7
Detalhamento de vista 3D parte 8
Aplicando tipo de sistema
Detalhamento colunas de água fria
Detalhamento colunas água quente
Aplicando filtro no reservatório
Revisando pressurizador
Instalando pressurizador água fria
Instalação do pressurizador parte 2

MÓDULO 15
Gerando Pranchas / Folhas parte 1
Montagem de Prancha parte 2
Montagem da Prancha parte 3
Montagem da Prancha parte 4
Montagem de Prancha parte 5
Montagem de Prancha parte 6
Prancha de Compatibilização
Pracha – Quantitativo de Materiais
Gerando pranchas em PDF
Gerando Prancha em DWG

MÓDULO 16
Cálculo Dimensionamento com Planilha Parte 1
Cálculo Dimensionamento com Planilha Parte 2
Baixando e Instalando o Software de Dimensionamento
imensionamento Software Pt'
    ],
    [
        'pergunta' => 'REVIT MEP - HIDROSSANITÁRIO COM DIMENSIONAMENTO',
        'resposta' => 'MÓDULO 01
Introdução ao curso e Como baixar os arquivos e anexos das aulas

MÓDULO 02
Links para baixar as famílias

MÓDULO 03
Requisitos mínimos e recomendados para instalar o Revit 2020
Cadastro na Autodesk para estudantes e para não estudantes
Instalando e corrigindo erro de instalação do Revit 2020
Novidades do Revit 2019
Novidades Revit 2020 parte 1
Novidades Revit 2020 parte 2

MÓDULO 04
Interface parte 1
Interface parte 2 e Configurações iniciais
Interface parte 3
Interface parte 4

MÓDULO 05
Visualização 2D, Barra de comandos e mouse
Orbitar, Zoom All e ZP
Círculo de navegação completa 2D e 3D
Cube view
Modos de uso básico
Atalhos do teclado
Modos de seleção
Famílias, tipos e categorias
Gerenciando janelas e vistas
Controle da Visibilidade de Categorias e elementos

MÓDULO 06
Área gráfica e configuração de atalhos
Comando VG (Visibilidade de gráficos)
Vista 3D, Filtro seleção e backup
Configurações básicas parte 1
Configurações básicas parte 2
Configurações básicas parte 3

MÓDULO 07
Apresentação do Template OS REVITEIROS
Instalação do template para projetos arquitetônicos
Transferindo informações de um projeto/template para outro

MÓDULO 08
Importando arquivos do AutoCad
Revelando elementos ocultos no projeto
Diferença entre vínculo e importação

MÓDULO 09
Inserindo níveis e Restrições
Ajustar os níveis, Unidades e Clarear desenho
Apagar vista e Criação de linhas de eixo

MÓDULO 10
Como transferir famílias novas de paredes para seu projeto e Revelar elementos ocultos
Paredes – Conceitos iniciais e Configurações parte 1
Paredes – configurações parte 2 e Mostrando níveis que sumiram
Paredes térreo com DWG – Como inserir paredes com base na planta do autoCAD
Observações importantes sobre paredes
Parede empilhada
Paredes térreo sem DWG parte 1 – linha de detalhe
Utilizando Filtro nas paredes
Paredes térreo sem DWG parte 2 – Como saber a orientação correta dos revestimentos
Paredes térreo sem DWG parte 3 – Dividir elementos e Ajustar medidas
Paredes térreo sem dwg parte 4 – Editando outra parede e pilar da garagem
Pavimento superior parte 1
Pavimento superior parte 2

MÓDULO 11
Criação de churrasqueira de alvenaria – parte 1
Churrasqueira de alvenaria – parte 2
Churrasqueira de alvenaria parte 3 – inserindo granito amarelo

MÓDULO 12
Adicionando portas e alterando espessura de linhas
Editando portas e indicação de aberturas – parte 1
Editando portas – parte 2
Parâmetros globais – bonecas, peitoris de janelas e forros

MÓDULO 13
Criação de Materiais de Portas e Janelas
Portas de correr e porta pivotante

MÓDULO 14
Adicionando janelas de correr
Janelas altas (visualização) e FAIXAS DE VISTAS

MÓDULO 15
Limpeza de arquivo (purge)

MÓDULO 16
Lajes, lajes de fundação e pisos (diferenças)
Criação da Laje do primeiro pavimento e alvenaria de fechamento da sacada

MÓDULO 17
Adicionando piso de madeira – parte 1
Adicionando piso de madeira parte 2
Paginação e alinhamento de pisos
Criando e editando hachuras – parte 1 (AULA NOVA)
Criando e editando hachuras – parte 2 (AULA NOVA)

MÓDULO 18
Adicionando peitoris e soleiras em granito
Criação de rodapés
Ajustando o rodapé de pisos em bonecas
Criação de guarda-corpos

MÓDULO 19
Baixando famílias
Carregando famílias
Adicionando bancadas de cozinha
Layout
Importação de blocos do sketchup
Como transformar blocos do Sketchup em famílias

MÓDULO 20
Criação de forro automático, forro inclinado e por croqui
Molduras de gesso
Alterando alturas de forro e molduras através do filtro

MÓDULO 21
Criando escadas – parte 1
Criando escadas – parte 2
Guarda-corpos de vidro parte 1
Guarda-corpos de vidro parte 2
Guarda-corpos de vidro parte 3
Guarda-corpos de vidro parte 4
Escada croqui com patamar em leque
Corrimão fixo na parede e guarda corpos de vidro parte 1
Guarda corpos de vidro parte 2 e transição dos corrimãos
Inserindo números dos degraus e caminho da escada

MÓDULO 22
Criando telhado parte 1
Criando telhado parte 2
Criando telhado por extrusão e Platibanda da área gourmet
Criando telhado contornando a saída da churrasqueira
Alterando o plano de trabalho
Editando e criando linhas e Projeção do telhado

MÓDULO 23
Identificação dos ambientes e adicionando áreas
Níveis e cotas
Cotas segunda parte
Anotações e chamadas de detalhes
Inserindo grama
Adicionando plantas de áreas (área permeável e área construída)
Cortes
Editando linha e legenda do símbolo dos cortes
Editando a legenda do símbolo dos cortes parte 2
Detalhando os cortes – Elevação de ponto e cotas
Fachadas e Modelos de Vistas
Terrenos e Topografia DWG
Plantas de locação e cobertura – parte 1
Plantas de locação e cobertura – parte 2
Planta de Situação
Tabelas e Quantitativos
Levantamento de materiais
Inserindo Fossa Séptica e Sumidouro

MÓDULO 24
Câmeras e renderização
Renderizando na nuvem

MÓDULO 25
Editando Folhas padrão ABNT
Editando Folhas padrão ABNT – Inserindo vistas nas folhas
Folha Parametrizada e Introdução aos Parâmetros do Carimbo
Criando Parâmetros do Carimbo – parte 1
Criando Parâmetros do Carimbo – parte 2
Inserindo Família Parametrizada de Índices Urbanísticos
Configurando Expessura de Linhas para Plotagem
Imprimindo o projeto (plotagem)
Salvando o projeto final como template (AULA NOVA)

MÓDULO 26
Minhas coordenadas (Elevações) sumiram. O que eu faço?
Como adicionar templates e famílias que não foram instalados – modo 2
Adicionando templates e famílias não baixados na instalação – modo 1
Dica de como recuperar plantas que sumiram
Criando elevações que foram deletadas
Resolvendo problema de hachura do corte com Modelo de vista
Girando Familia de Treliça Modelada no Local

MÓDULO 27
Criando Piscina na Superfície
Marquise
Frisos
Corte isométrico
Corte Isométrico Explodido
Planta humanizada Renderizada
Planta Humanizada Sem Renderizar
Planta Isométrica
Pilares, vergas e contravergas
Configurando Textura de Porcelanato de Forma Simples
Criando Superfície com Desnível e Criando Platos para Inserção da Construção
Inserindo Identificadores de Porta e Janela
Posição geográfica, norte verdadeiro e Estudo solar correto
Instalando Plugin do Lumion para exportar Projeto dentro do Revit
Descomplicando a Criação de Vigas no Projeto Arquitetônico
Criando Minha Estrutura para Receber a Minha Caixa D’água

MÓDULO 28
Como fazer Render 360º no Revit
Biblioteca de blocos
Como cobrar por projetos arquitetônicos
Modelo de contrato de projeto arquitetônico
Renderização na nuvem com iluminação artificial
PDF Atalhos Revit

MÓDULO 29
DÚVIDAS – Opção salvar projeto sumiu'
    ],
    [
        'pergunta' => 'REVIT MEP - ELÉTRICO COM DIMENSIONAMENTO',
        'resposta' => 'MÓDULO 01
Conhecendo o professor e o roteiro do curso.
Qual a importância do projeto de instalações elétricas e como convencer um cliente dessa necessidade
Resumo BIM e Qual a vantagem de aprender esta disciplina na plataforma – parte 1
Resumo BIM e Qual a vantagem de aprender esta disciplina na plataforma – parte 2
Noções básicas da norma NBR 5410/2004

MÓDULO 02
Apresentação da interface do programa
Apresentação do briefing e informações relevantes
Primeiros passos no Revit MEP
Configurações de projeto

MÓDULO 03
Lançamento de iluminação
Lançamento de iluminação 02
Lançamento de TUG’s superior
Lançamento de TUG’s Terreo
Lançamento de TUE’s e quadro de distribuição

MÓDULO 04
Criação de circuitos elétricos parte 01
Criação de circuitos elétricos parte 02
Ajustes de TAG’s e TUE’s parte 01
Ajustes de TAG’s e TUE’s parte 02

MÓDULO 05
Tabela de cargas e dimensionamento
Modelagem conduíte
Identificação de interruptores
Detalhamento elétrico – Planta baixa 01
Detalhamento elétrico – Planta baixa 02
Adicionar interruptores ao circuito
Ligação entre quadros
Escolha de medidores
Detalhamento isométrico
Diagrama unifilar

MÓDULO 06
Quantificação de fiação
Plotagem

MÓDULO 07
DÚVIDA 01 – perda de referência da arquitetura
DÚVIDA 02 – Criando elevação para configurar níveis
DÚVIDA 03 – Aumentar numero de circuitos QD'
    ],
    [
        'pergunta' => 'REVIT ESTRUTURAL 2.0',
        'resposta' => 'MÓDULO 01
Aula 1 – Apresentação Estrutural
Aula 2 – Como baixar os arquivos das aulas

MÓDULO 02
Aula 3 – Instalando o template do curso de Estrutural
Aula 4 – Criando níveis e plantas estruturais no Revit
Aula 5 – Importando projeto estrutural do AutoCAD
Aula 6 – Inserindo eixos de acordo com NBR 6492
Aula 7 – Diferença entre pilares que nascem, morrem e continuam
Aula 8 – Criando blocos de fundação e estacas
Aula 9 – Criando pilares que nascem na fundação
Aula 10 – Editando as lajes do primeiro pavimento
Aula 11 – Adicionando plantas estruturais e Configuração especial do Revit

MÓDULO 03
Aula 12 – Vigas baldrames
Aula 13 – Vigas e pilares da primeira laje
Aula 14 – Vigas e pilares dos demais pavimentos
Aula 15 – Criando a estrutura da laje de cobertura
Aula 16 – Criando a estrutura da caixa – d’água
Aula 17 – União dos elementos estruturais
Aula 18 – Gerando pranchas para impressão
Aula 19 – Locação Das Vigas Baldrames
Aula 20 – Redimensionamento da Viga Baldrame e Corrigindo Erro na Instalação das Famílias de Vergalhões
Aula 21 – Colocação das Armaduras nas vigas
Aula 22 – Detalhamento da armadura das vigas parte 1
Aula 23 – Detalhamento da armadura das vigas parte 2
Aula 24 – Colocação das armaduras nos pilares
Aula 25 – Colocação das armaduras nas lajes
Aula 26 – Colocação das armaduras nos blocos de fundação
Aula 27 – Colocação das armaduras nas estacas
Aula 28 – Colocação de treliças estruturais
Aula 29 – Criação de Sistemas de Vigas
Aula 30 – Tabelas de Quantitativos
Aula 31 – Como adicionar templates e famílias que não foram instalados
Aula 32 – Locação De Sapatas E Pilares

MÓDULO 04
Aula 33 – Corrigindo espessura de vigas de algumas treliças'
    ],
    [
        'pergunta' => 'RENDERIZAÇÃO COM LUMION',
        'resposta' => 'MÓDULO 01
Boas vindas e apresentação do curso

MÓDULO 02
Introdução a movimentação do Lumion

MÓDULO 03
Importando arquivo do Revit e configurações iniciais

MÓDULO 04
Paisagismo e inserção de blocos da fachada

MÓDULO 05
Configurando cenas e enquadramento

MÓDULO 06
Configurando texturas fachada

MÓDULO 07
Configurando texturas área da churrasco

MÓDULO 08
Paisagismo e Inserção de blocos da área de churrasco

MÓDULO 09
Configurando estilos para renderização

MÓDULO 10
Gerando animação do projeto

MÓDULO 11
Editando animação e finalização do curso'
    ],
    [
        'pergunta' => 'TREINAMENTO AUTOCAD PARA PREFEITURA',
        'resposta' => 'MÓDULO 01
Apresentação do curso de AutoCAD
Requisitos mínimos e instalação do AutoCAD
Novidades AutoCAD
Grupo do Telegram

MÓDULO 02
Interface parte 1 AutoCAD
Interface parte 2 AutoCAD
Interface parte 3 AutoCAD
Interface parte 4 AutoCAD
Interface parte 5 e Área de desenho

MÓDULO 03
Configuração básica da área de trabalho
Templates e Escalas
Layers
Tipos de zoom, Seleção e Como limpar seu template
Apresentação do Projeto Modelo

MÓDULO 04
Paredes externas parte 1 (poli linha, explodir, deslocamento,…)
Paredes externas parte 2 (ferramentas Chanfro e Concord)
Paredes internas parte 1 e Limpando o projeto (Eliminar ou purge)
Paredes internas parte 2
Comandos de precisão (Osnap)
Criando vãos de portas
Criando vãos de janelas
Janelas (deslocamento, copiar, mover, rotacionar)
Portas (retângulo, círculo, grupo, espelhar)
Bancadas e eixos hidrossanitários
Adicionando blocos para banheiros e ferramenta alinhar
Adicionando blocos para cozinha, área gourmet, quartos, etc.
Adicionando textos
Adicionando cotas de nível
Adicionando medidas de portas
Adicionando medidas de janelas
Adicionando cotas parte 1
Adicionando cotas parte 2
Adicionando hachuras e visibilidade das layers
Adicionando hachura de pisos e fazendo planta de cobertura parte 1
Fazendo planta de cobertura parte 2 e posicionando os cortes
Projetando Cortes Longitudinais (Corte AA) parte 1
Projetando cortes longitudinais (Corte AA) parte 2
Projetando cortes longitudinais (Corte AA) parte 2
Projetando cortes longitudinais (corte CC) parte 1
Projetando cortes longitudinais (corte CC) parte 2
Projetando corte transversal (corte BB) parte 1
Projetando corte transversal (corte BB) parte 2
Projetando corte transversal (corte BB) parte 3
Projetando a fachada parte 1
Projetando a fachada parte 2
Planta de situação parte 2, Modelo e Folha de desenho (Layout 1, 2 …)
Formatando a folha de desenho
Revisão antes da plotagem
Configuração de penas parte 1
Configuração de penas parte 2
Alterando textos do selo e salvando a folha como bloco
AULA EXTRA 1 – ESCADAS PLANTA BAIXA
AULA EXTRA 2 – CORTE DA ESCADA
AULA EXTRA 3 – DETALHE DA ESCADA

MÓDULO 05
Como cobrar por projetos arquitetônicos.'
    ],
    [
        'pergunta' => 'CRIAÇÃO DE FAMÍLIAS PARAMÉTRICAS NO REVIT',
        'resposta' => 'Apresentação das ferramentas para Criação de Famílias em Revit
Criando
Garrafa 100% em Revit
Espremedor de Laranja parte 01
Espremedor de Laranja parte 02
Espremedor de Laranja parte 03
Espremedor de Laranja parte 04
Espremedor de Laranja parte 05
Espremedor de Laranja parte 06
Espremedor de Laranja parte 07
Aparador Paramétrico parte 01'
    ],
    [
        'pergunta' => 'COMO COBRAR POR PROJETOS',
        'resposta' => 'Masterclass com o passo a passo de como cobrar por projetos.'
    ],
    [
        'pergunta' => 'TREINAMENTO CÁLCULO ESTRUTURAL RESIDENCIAL COM EBERICK',
        'resposta' => 'MÓDULO 01
Aula 1- Boas-vindas + Grupo de suporte
Aula 2- Baixando o Eberick

MÓDULO 02
Aula 3- Interpretando o projeto arquitetônico
Aula 4- Inserindo níveis parte 1
Aula 5-Inserindo níveis parte 2
Aula 6- Importando projeto arquitetônico

MÓDULO 03
Aula 7- Lançamento de pilares parte 1
Aula 8- Lançamento de pilares parte 2
Aula 9- Lançamento de pilares parte 2
Aula 10- Lançamento de vigas parte 1
Aula 11- Lançamento de vigas parte
Aula 12- Lançamento de vigas parte 3

MÓDULO 04
Aula 13- Copiando elementos para outros pavimentos

MÓDULO 05
Aula 14- Ajustando modelagem estrutural parte 1
Aula 15- Ajustando modelagem estrutural parte 2
Aula 16- Ajustando modelagem estrutural parte 3

MÓDULO 06
Aula 17- lançamento de lajes

MÓDULO 07
Aula 18- Lançamento de fundação e sapatas

MÓDULO 08
Aula 19- Estudo do solo a partir do laudo de sondagem – sapatas

MÓDULO 09
Aula 20- Lançamento topo caixa d’água

MÓDULO 10
Aula 21- Inserindo elevações – vigas e pilares

MÓDULO 11
Aula 22- Inserindo cargas de parede

MÓDULO 12
Aula 23- Lançamento de cargas da caixa d’água

MÓDULO 13
Aula 24- Renumerando elementos e 1° processamento

MÓDULO 14
Aula 25- Análise e dimensionamento parte 1
Aula 26- Análise e dimensionamento parte 2
Aula 27- Análise e dimensionamento parte 3
Aula 28- Análise e dimensionamento parte 4
Aula 29- Análise e dimensionamento parte 5
Aula 30- Finalizando a concepção estrutural

MÓDULO 15
Aula 31- Otimizando pilares parte 1
Aula 32- Otimizando pilares parte 2
Aula 33- Otimizando pilares parte 3

MÓDULO 16
Aula 34- Dimensionamento de vigas – fundação
Aula 35- Dimensionamento de vigas – Laje inferior
Aula 36- Dimensionamento de vigas – Laje superior e topo caixa d’água
Aula 37- Dimensionamento de lajes – Laje inferior
Aula 38- Dimensionamento de laje – Laje superior
Aula 39- Dimensionamento de sapatas
Aula 40- Ajuste nos limites das sapatas

MÓDULO 17
Aula 41- Gerando pranchas – parte 1
Aula 42- Gerando pranchas – parte 2

MÓDULO 18
Aula 43- Resumo de materiais'
    ],
    [
        'pergunta' => 'TREINAMENTO CÁLCULO ESTRUTURAL PREDIAL 6 PAVIMENTOS COM EBERICK',
        'resposta' => 'MÓDULO 01
Aula 1.0 – Boas-vindas + Grupo de suporte
Aula 1.1 – Como vai funcionar o curso
Aula 1.2 – Download de todos os arquivos do curso
Aula 1.3 – Baixando o Eberick

MÓDULO 02
Aula 2 – Inserindo níveis do projeto arquitetônico
Aula 3 – Importando projeto Arquitetônico

MÓDULO 03
Aula 4 – Converter projeto arquitetônico para escala e posicionar ponto de origem

MÓDULO 04
Aula 5 – Lançamento de Vigas e Pilares – Parte 1
Aula 6 – Lançamento de vigas e pilares – Parte 2
Aula 7 – Lançamento de vigas e pilares – Parte 3
Aula 8 – Lançamento de vigas e pilares – Parte 4

MÓDULO 05
Aula 9 – Lançamento de lajes
Aula 10 – Lançamento de cargas de parede – Parte 1
Aula 11 – Lançamento de cargas de parede – Parte 2
Aula 12 – Cargas de parede – inserindo vão de esquadrias
Aula 13 – Finalizando lançamento de cargas
Aula 14 – Finalizando Lançamento dos pavimentos tipo
Aula 15 – Lançamento de casa de máquina
Aula 16 – Lançamento de cargas de parede na casa de máquina

MÓDULO 06
Aula 17 – Lançamento de escada – Parte 1
Aula 18 – Lançamento de escada – Parte 2
Aula 19 – Lançamento de escada – Parte 3
Aula 20 – Lançamento de escada – Parte 4

MÓDULO 07
Aula 21 – Lançamento de fundação
Aula 22 – Lançando cargas de Caixa D’água

MÓDULO 08
Aula 23 – 1º processamento – Dando rigidez à estrutura – Parte 1
Aula 24 – 1º Processamento – Dando rigidez à estrutura – Parte 2

MÓDULO 09
Aula 25 – Análise estrutural e dimensionamento de pilares – Parte 1
Aula 26 – Análise estrutural e dimensionamento de pilares – parte 2
Aula 27 – Análise estrutural e dimensionamento de pilares – Parte 3
Aula 28 – Análise estrutural e dimensionamento de pilares – Parte 4
Aula 29 – Renumerando Elementos
Aula 30 – Análise estrutural e dimensionamento de pilares – Parte 5
Aula 31 – Análise estrutural e dimensionamento de pilares – Parte 6
Aula 32 – Análise estrutural e dimensionamento de pilares – Parte 7
Aula 33 – Análise estrutural e dimensionamento de pilares – Parte 8
Aula 34 – Análise estrutural e dimensionamento de pilares – Parte 9

MÓDULO 10
Aula 35 – Otimizando pilares – parte 1
Aula 36 – Otimizando pilares – parte 2
Aula 37 – Otimizando pilares – parte 3
Aula 38 – Otimizando pilares – parte 4
Aula 39 – Otimizando pilares – Parte 5
Aula 40 – Otimizando pilares – Parte 6
Aula 41 – Otimizando pilares – parte 7
Aula 42 – Otimizando pilares – parte 8
Aula 43 – otimizando pilares – parte 9
Aula 44 – Otimizando pilares – parte 10

MÓDULO 11
Aula 45 – Análise estrutural – vigas da fundação
Aula 46 – Análise estrutural – vigas do 1º pavimento
Aula 47 – Análise estrutural – vigas do 2º pavimento
Aula 48 – Analise estrutural – vigas do 3º pavimento
Aula 49 – Análise estrutural – pavimentos 4º, 5º, telhado e casa de máquinas

MÓDULO 12
Aula 50 – Análise de lajes – 1º pavimento
Aula 51 – Analise de lajes – 2º e 3º pavimentos
Aula 52 – Análise de lajes – Pavimentos 4, 5, telhado e casa de máquina

MÓDULO 13
Aula 53 – Análise e detalhamento de escadas
Aula 54 – Ajustes de lançamento na fundação – parte 1
Aula 55 – Ajuste de lançamento na fundação – parte 2
Aula 56 – ajuste de lançamento na fundação – parte 3
Aula 57 – Cadastrando estacas
Aula 58 – Dimensionando blocos de fundação – parte 1
Aula 59 – Dimensionando blocos de fundação – parte 2
Aula 60 – Dimensionamento de blocos de fundação – parte 3
Aula 61 – Dimensionamento de blocos de fundação – parte 4
Aula 62 – Dimensionamento de blocos de fundação – parte 5
Aula 63 – Dimensionamento de blocos de fundação – parte 6
Aula 64 – Armação dos blocos – leitura e interpretação de projeto

MÓDULO 14
Aula 65 – Gerando pranchas – parte 1
Aula 66 – gerando pranchas – parte 2
Aula 67 – gerando pranchas – parte 3

MÓDULO 15
Aula 68 – resumo de materiais
Aula 69 – Detalhes de estacas

MÓDULO 16
Aula 01 – Configurações – Ações e Análise
Aula 02 – Configurações – Detalhamento
Aula 03 – Configurações – Dimensionamento
Aula 04 – Configurações – Materiais e durabilidade
Aula 05 – Configurações – Sistema e CAD
Aula 06 – Vinculações
Aula 07 – vinculações – redistribuição maior que 25%
Aula 08 – Bloco de divisa e sapata de divisa
Aula 09 – Projetos econômicos – resumo de materiais
Aula 10 – Viga inclinada e rampa
Aula 11 – Piscina enterrada
Aula 12 – Analisando efeitos de vento na estrutura
Aula 13 – Configurando cargas típicas

MÓDULO 17
Aula 1 – Montagem de pranchas – Armação de lajes – parte 1
Aula 2 – Montagem de pranchas – Armação de lajes – parte 2
Aula 3 – Utilizando um pouco do QI editor de armadura'
    ],
    [
        'pergunta' => 'TREINAMENTO CÁLCULO ESTRUTURAL DE SOBRADO COM EBERICK',
        'resposta' => 'MÓDULO 01
Aula 00- Boas vindas + Grupo de suporte
Aula 01- Baixando o Eberick

MÓDULO 02
Aula 1: Apresentação do projeto arquitetônico
Aula 2: Inserindo níveis
Aula 03 – Convertendo projeto para escala e posicionando origem

MÓDULO 03
Aula 04 – Lançamento de pilares
Aula 05 – Lançamento de vigas da fundação

MÓDULO 04
Aula 06 – Copiando vigas e pilares para outros pavimentos

MÓDULO 05
Aula 07 – Ajustando concepção estrutural – parte 1
Aula 08 – Ajustando concepção estrutural – parte 2
Aula 09 – Ajustando concepção estrutural – parte 3
Aula 10 – Ajustando concepção estrutural – parte 4

MÓDULO 06
Aula 11 – Lançamento de lajes
Aula 12 – Lançamento de escada – parte 1
Aula 13 – Lançamento de Escada – parte 2

MÓDULO 07
Aula 14 – Lançamento de fundação

MÓDULO 08
Aula 15 – Aplicando elevações em vigas

MÓDULO 09
Aula 16 – Lançamento de cargas de parede

MÓDULO 10
Aula 17- Lançamento de cargas de caixa d’água

MÓDULO 11
Aula 18 – Primeiro processamento
Aula 19 – Renumerando elementos

MÓDULO 12
Aula 20 – Análise estrutural – parte 1
Aula 21 – Análise estrutural – parte 2
Aula 22 – Análise estrutural – parte 3

MÓDULO 13
Aula 23 – Dimensionamento de pilares – parte 1
Aula 24 – Dimensionamento de pilares – parte 2
Aula 25 – Dimensionamento de pilares – parte 3
Aula 26 – Dimensionamento dos pilares – parte 4
Aula 27 – Dimensionamento dos pilares – parte 5

MÓDULO 14
Aula 28 – Otimização de pilares
Aula 29 – Dimensionamento de vigas
Aula 30 – Dimensionamento de lajes – 1º pavimento
Aula 31 – Dimensionamento de lajes – Telhado – parte 1
Aula 32 – Dimensionamento de lajes – parte 2
Aula 33 – Dimensionamento de fundação
Aula 34 – Bloco de divisa
Aula 35 – Detalhamento de escada
Aula 36 – Utilizando escada plissada

MÓDULO 15
Aula 37 – Resumo de materiais – parte 1
Aula 38 – Resumo de materiais – parte 2

MÓDULO 16
Aula 39 – Reduzindo custo de projeto'
    ],
],
*/

<?php

/**
 * @file
 * Comprehensive seed script for Gen Zero platform.
 * Creates users, projetos, products, taxonomy terms, orders, donations,
 * and subscriptions — all in Portuguese context.
 *
 * Run with: ddev drush scr scripts/seed_comprehensive.php
 */

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\physical\Weight;

$etm = \Drupal::entityTypeManager();
$store = $etm->getStorage('commerce_store')->loadDefault();
$store_id = $store ? $store->id() : 1;

// ====================================================================
// HELPER: get or create taxonomy term
// ====================================================================
function seed_get_or_create_term(string $name, string $vid): int {
  $existing = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
    ->loadByProperties(['name' => $name, 'vid' => $vid]);
  if ($existing) {
    return (int) reset($existing)->id();
  }
  $term = Term::create(['vid' => $vid, 'name' => $name, 'status' => 1]);
  $term->save();
  return (int) $term->id();
}

// HELPER: get or create user
function seed_get_or_create_user(string $name, string $mail): int {
  $existing = \Drupal::entityTypeManager()->getStorage('user')
    ->loadByProperties(['name' => $name]);
  if ($existing) {
    return (int) reset($existing)->id();
  }
  $user = User::create([
    'name' => $name,
    'mail' => $mail,
    'status' => 1,
    'pass' => 'testpass123',
  ]);
  $user->save();
  return (int) $user->id();
}

echo "╔══════════════════════════════════════════════╗\n";
echo "║   Gen Zero — Seed Completo (PT)             ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

// ====================================================================
// 1. USERS — 25 Portuguese-named users
// ====================================================================
echo "=== 1. UTILIZADORES ===\n";

$user_data = [
  ['name' => 'maria_silva', 'mail' => 'maria.silva@exemplo.com'],
  ['name' => 'joao_costa', 'mail' => 'joao.costa@exemplo.com'],
  ['name' => 'ana_santos', 'mail' => 'ana.santos@exemplo.com'],
  ['name' => 'pedro_oliveira', 'mail' => 'pedro.oliveira@exemplo.com'],
  ['name' => 'sofia_pereira', 'mail' => 'sofia.pereira@exemplo.com'],
  ['name' => 'carlos_ferreira', 'mail' => 'carlos.ferreira@exemplo.com'],
  ['name' => 'lucia_rodrigues', 'mail' => 'lucia.rodrigues@exemplo.com'],
  ['name' => 'miguel_almeida', 'mail' => 'miguel.almeida@exemplo.com'],
  ['name' => 'beatriz_martins', 'mail' => 'beatriz.martins@exemplo.com'],
  ['name' => 'tiago_sousa', 'mail' => 'tiago.sousa@exemplo.com'],
  ['name' => 'ines_gomes', 'mail' => 'ines.gomes@exemplo.com'],
  ['name' => 'rui_lopes', 'mail' => 'rui.lopes@exemplo.com'],
  ['name' => 'catarina_dias', 'mail' => 'catarina.dias@exemplo.com'],
  ['name' => 'andre_ribeiro', 'mail' => 'andre.ribeiro@exemplo.com'],
  ['name' => 'mariana_carvalho', 'mail' => 'mariana.carvalho@exemplo.com'],
  ['name' => 'diogo_mendes', 'mail' => 'diogo.mendes@exemplo.com'],
  ['name' => 'carolina_teixeira', 'mail' => 'carolina.teixeira@exemplo.com'],
  ['name' => 'rafael_correia', 'mail' => 'rafael.correia@exemplo.com'],
  ['name' => 'laura_nunes', 'mail' => 'laura.nunes@exemplo.com'],
  ['name' => 'henrique_moreira', 'mail' => 'henrique.moreira@exemplo.com'],
  ['name' => 'marta_pinto', 'mail' => 'marta.pinto@exemplo.com'],
  ['name' => 'francisco_vieira', 'mail' => 'francisco.vieira@exemplo.com'],
  ['name' => 'leonor_azevedo', 'mail' => 'leonor.azevedo@exemplo.com'],
  ['name' => 'gustavo_fonseca', 'mail' => 'gustavo.fonseca@exemplo.com'],
  ['name' => 'helena_campos', 'mail' => 'helena.campos@exemplo.com'],
];

$user_ids = [];
foreach ($user_data as $ud) {
  $uid = seed_get_or_create_user($ud['name'], $ud['mail']);
  $user_ids[] = $uid;
}

// Also include existing real users (admin, leandro, etc.)
$all_users = $etm->getStorage('user')->loadMultiple();
foreach ($all_users as $u) {
  if ((int) $u->id() > 0) {
    $user_ids[] = (int) $u->id();
  }
}
$user_ids = array_unique(array_values($user_ids));
echo "  Total utilizadores disponíveis: " . count($user_ids) . "\n\n";

// ====================================================================
// 2. TAXONOMY TERMS — Portuguese product & project categories
// ====================================================================
echo "=== 2. TAXONOMIA ===\n";

// Product Tags (eco/sustentável focus)
$product_tag_names = [
  'Reciclado', 'Sustentável', 'Oceano', 'Plástico Reciclado',
  'Feito à Mão', 'Eco-Friendly', 'Biodegradável', 'Comércio Justo',
  'Orgânico', 'Zero Desperdício', 'Reutilizável', 'Local',
];
$product_tag_ids = [];
foreach ($product_tag_names as $name) {
  $product_tag_ids[] = seed_get_or_create_term($name, 'product_tags');
}
echo "  Product Tags: " . count($product_tag_ids) . "\n";

// Product Collections — add Portuguese eco collections
$eco_collections = [
  'Limpeza Costeira', 'Vida Marinha', 'Casa Sustentável',
  'Moda Ética', 'Jardim Verde', 'Aventura Eco',
];
$eco_collection_ids = [];
foreach ($eco_collections as $name) {
  $eco_collection_ids[] = seed_get_or_create_term($name, 'product_collections');
}
echo "  Eco Collections: " . count($eco_collection_ids) . "\n";

// Origem — add more plastic origin sources
$origem_names = ['Praias', 'Rios', 'Urbano', 'Oceano Profundo', 'Reciclagem Comunitária'];
$origem_ids = [];
foreach ($origem_names as $name) {
  $origem_ids[] = seed_get_or_create_term($name, 'origem');
}
// Also load existing ones
$existing_origem = $etm->getStorage('taxonomy_term')->loadByProperties(['vid' => 'origem']);
foreach ($existing_origem as $t) {
  $origem_ids[] = (int) $t->id();
}
$origem_ids = array_unique($origem_ids);
echo "  Origem terms: " . count($origem_ids) . "\n";

// Product Brands — add Portuguese/eco brands
$brand_names = ['Mar Limpo', 'Terra Verde', 'Oceano Azul', 'EcoAçores', 'Angra Sustentável'];
$brand_ids = [];
foreach ($brand_names as $name) {
  $brand_ids[] = seed_get_or_create_term($name, 'product_brands');
}
$existing_brands = $etm->getStorage('taxonomy_term')->loadByProperties(['vid' => 'product_brands']);
foreach ($existing_brands as $t) {
  $brand_ids[] = (int) $t->id();
}
$brand_ids = array_unique($brand_ids);
echo "  Brands: " . count($brand_ids) . "\n\n";

// ====================================================================
// 3. PROJETOS — 8 realistic environmental projects, varied states
// ====================================================================
echo "=== 3. PROJETOS ===\n";

$projetos_def = [
  [
    'title' => 'Limpeza da Praia da Vitória',
    'short' => 'Remoção de resíduos plásticos na Praia da Vitória, Terceira.',
    'desc' => '<p>Este projeto visa a limpeza profunda da Praia da Vitória, uma das praias mais emblemáticas da ilha Terceira. A acumulação de plásticos e microplásticos ameaça a biodiversidade marinha local, incluindo espécies protegidas de tartarugas marinhas e aves.</p><p>Com a participação de voluntários locais e equipas profissionais de mergulho, pretendemos remover mais de 2 toneladas de resíduos plásticos da zona costeira e dos fundos marinhos adjacentes.</p>',
    'state' => 'ativo',
    'goal' => '15000.00',
    'progress' => '8750.50',
    'start' => '2025-09-01T00:00:00',
    'end' => '2026-06-30T00:00:00',
    'objectives' => [
      ['desc' => 'Remover 2 toneladas de plástico da praia e fundos marinhos', 'done' => FALSE],
      ['desc' => 'Instalar 10 pontos de recolha seletiva na zona costeira', 'done' => FALSE],
      ['desc' => 'Organizar 4 ações de sensibilização nas escolas locais', 'done' => TRUE],
      ['desc' => 'Criar mapa interativo de pontos de poluição', 'done' => TRUE],
    ],
  ],
  [
    'title' => 'Reflorestação do Monte Brasil',
    'short' => 'Plantação de espécies autóctones no Monte Brasil, Angra do Heroísmo.',
    'desc' => '<p>O Monte Brasil, reserva natural classificada, tem sofrido com a proliferação de espécies invasoras e a erosão dos solos. Este projeto propõe a reflorestação de 5 hectares com espécies endémicas dos Açores.</p><p>Trabalharemos com o viveiro florestal regional para produzir 10.000 mudas de Loureiro-dos-Açores, Cedro-do-Mato e Urze. Cada árvore plantada será georreferenciada e os doadores poderão acompanhar o crescimento da sua árvore.</p>',
    'state' => 'ativo',
    'goal' => '25000.00',
    'progress' => '18200.00',
    'start' => '2025-06-15T00:00:00',
    'end' => '2026-12-31T00:00:00',
    'objectives' => [
      ['desc' => 'Produzir 10.000 mudas de espécies autóctones no viveiro', 'done' => TRUE],
      ['desc' => 'Plantar 5 hectares de floresta nativa', 'done' => FALSE],
      ['desc' => 'Remover espécies invasoras de 8 hectares', 'done' => TRUE],
      ['desc' => 'Instalar sistema de rega sustentável com captação de água pluvial', 'done' => FALSE],
      ['desc' => 'Criar trilho educativo sobre a flora endémica', 'done' => FALSE],
    ],
  ],
  [
    'title' => 'Reciclagem Criativa Açores',
    'short' => 'Transformação de plástico oceânico em produtos artesanais.',
    'desc' => '<p>Este projeto inovador transforma plástico recolhido do oceano Atlântico em produtos artesanais de design único. Trabalhamos com artesãos locais dos Açores para criar uma economia circular que converte poluição marinha em arte funcional.</p><p>Desde bijuteria a mobiliário de exterior, cada peça conta a história da sua origem — do oceano à mesa. Todo o lucro reverte para novas ações de limpeza costeira.</p>',
    'state' => 'ativo',
    'goal' => '10000.00',
    'progress' => '6300.00',
    'start' => '2025-11-01T00:00:00',
    'end' => '2026-08-31T00:00:00',
    'objectives' => [
      ['desc' => 'Formar 20 artesãos locais em técnicas de reciclagem criativa', 'done' => TRUE],
      ['desc' => 'Transformar 500 kg de plástico oceânico em produtos', 'done' => FALSE],
      ['desc' => 'Abrir loja online de produtos reciclados', 'done' => TRUE],
      ['desc' => 'Participar em 3 feiras de artesanato sustentável', 'done' => FALSE],
    ],
  ],
  [
    'title' => 'Educação Ambiental nas Escolas',
    'short' => 'Programa educativo sobre sustentabilidade para crianças dos Açores.',
    'desc' => '<p>A mudança começa nos mais jovens. Este programa leva a educação ambiental a 30 escolas do arquipélago dos Açores, atingindo mais de 3.000 alunos do ensino básico.</p><p>Através de workshops interativos, visitas de estudo a centros de reciclagem e projetos práticos de hortas escolares, queremos formar a próxima geração de cidadãos ambientalmente conscientes.</p>',
    'state' => 'ativo',
    'goal' => '8000.00',
    'progress' => '8000.00',
    'start' => '2025-03-01T00:00:00',
    'end' => '2026-07-15T00:00:00',
    'objectives' => [
      ['desc' => 'Visitar 30 escolas do arquipélago', 'done' => TRUE],
      ['desc' => 'Realizar 60 workshops interativos', 'done' => TRUE],
      ['desc' => 'Criar 15 hortas escolares', 'done' => TRUE],
      ['desc' => 'Produzir kit pedagógico digital gratuito', 'done' => FALSE],
    ],
  ],
  [
    'title' => 'Proteção das Tartarugas Marinhas',
    'short' => 'Monitorização e proteção de ninhos de tartarugas nos Açores.',
    'desc' => '<p>Os Açores são um corredor migratório vital para tartarugas-cabeçudas (Caretta caretta) e tartarugas-verdes (Chelonia mydas). Este projeto implementa um sistema de monitorização costeira com câmaras e sensores para detetar e proteger ninhos.</p><p>Voluntários treinados patrulham as praias durante a época de nidificação (junho a outubro) e uma equipa veterinária está disponível 24 horas para resgatar tartarugas feridas ou encalhadas.</p>',
    'state' => 'em_breve',
    'goal' => '20000.00',
    'progress' => '3500.00',
    'start' => '2026-06-01T00:00:00',
    'end' => '2027-03-31T00:00:00',
    'objectives' => [
      ['desc' => 'Instalar 20 câmaras de monitorização em praias prioritárias', 'done' => FALSE],
      ['desc' => 'Formar 50 voluntários em identificação e proteção de ninhos', 'done' => FALSE],
      ['desc' => 'Criar base de dados de avistamentos e ninhos', 'done' => FALSE],
      ['desc' => 'Reabilitar centro de resgate de tartarugas', 'done' => FALSE],
    ],
  ],
  [
    'title' => 'Hortas Comunitárias Urbanas',
    'short' => 'Criação de espaços verdes comunitários em zonas urbanas dos Açores.',
    'desc' => '<p>Transformar terrenos abandonados em zonas urbanas dos Açores em hortas comunitárias produtivas. Este projeto promove a segurança alimentar, a coesão social e a redução da pegada ecológica através da agricultura urbana de proximidade.</p><p>Cada horta é gerida por uma associação de moradores e conta com formação técnica em agricultura biológica, compostagem doméstica e permacultura.</p>',
    'state' => 'concluido',
    'goal' => '12000.00',
    'progress' => '12000.00',
    'start' => '2024-09-01T00:00:00',
    'end' => '2025-12-31T00:00:00',
    'objectives' => [
      ['desc' => 'Criar 8 hortas comunitárias em terrenos urbanos', 'done' => TRUE],
      ['desc' => 'Formar 200 moradores em agricultura biológica', 'done' => TRUE],
      ['desc' => 'Produzir 5 toneladas de alimentos biológicos no primeiro ano', 'done' => TRUE],
      ['desc' => 'Instalar sistema de compostagem em cada horta', 'done' => TRUE],
    ],
  ],
  [
    'title' => 'Energia Solar para Comunidades Rurais',
    'short' => 'Instalação de painéis solares em comunidades isoladas dos Açores.',
    'desc' => '<p>Muitas comunidades rurais dos Açores ainda dependem excessivamente de geradores a diesel. Este projeto pretende instalar sistemas fotovoltaicos com armazenamento em bateria em 15 comunidades, reduzindo as emissões de CO₂ e os custos energéticos.</p><p>Cada instalação inclui formação dos residentes para manutenção básica e um fundo comunitário alimentado pela poupança energética para futuras melhorias.</p>',
    'state' => 'em_breve',
    'goal' => '50000.00',
    'progress' => '12500.00',
    'start' => '2026-09-01T00:00:00',
    'end' => '2027-12-31T00:00:00',
    'objectives' => [
      ['desc' => 'Auditar consumo energético de 15 comunidades', 'done' => FALSE],
      ['desc' => 'Instalar painéis solares em 15 comunidades rurais', 'done' => FALSE],
      ['desc' => 'Reduzir emissões de CO₂ em 40% nestas comunidades', 'done' => FALSE],
      ['desc' => 'Formar técnicos locais de manutenção solar', 'done' => FALSE],
    ],
  ],
  [
    'title' => 'Limpeza dos Rios Açorianos',
    'short' => 'Remoção de resíduos e restauração dos ecossistemas fluviais.',
    'desc' => '<p>Os rios dos Açores, apesar da sua aparente pureza, acumulam resíduos agrícolas e plásticos que acabam por chegar ao oceano. Este projeto atua na origem do problema — limpando as ribeiras, restaurando as margens com vegetação ripícola e instalando barreiras de retenção de resíduos.</p><p>Em parceria com as juntas de freguesia e associações de pescadores, vamos intervir nos 10 principais cursos de água da ilha Terceira.</p>',
    'state' => 'concluido',
    'goal' => '18000.00',
    'progress' => '18000.00',
    'start' => '2024-04-01T00:00:00',
    'end' => '2025-09-30T00:00:00',
    'objectives' => [
      ['desc' => 'Limpar 10 cursos de água principais', 'done' => TRUE],
      ['desc' => 'Instalar 25 barreiras de retenção de resíduos', 'done' => TRUE],
      ['desc' => 'Replantar 3 km de vegetação ripícola', 'done' => TRUE],
      ['desc' => 'Monitorizar qualidade da água durante 12 meses', 'done' => TRUE],
      ['desc' => 'Publicar relatório de impacto ambiental', 'done' => TRUE],
    ],
  ],
];

$projeto_ids = [];

foreach ($projetos_def as $pdef) {
  // Check if projeto with same title already exists.
  $existing = $etm->getStorage('node')->loadByProperties([
    'type' => 'projeto',
    'title' => $pdef['title'],
  ]);
  if ($existing) {
    $node = reset($existing);
    $projeto_ids[] = (int) $node->id();
    echo "  Projeto já existe: {$pdef['title']} (nid: {$node->id()})\n";
    continue;
  }

  // Create objective paragraphs.
  $objective_refs = [];
  foreach ($pdef['objectives'] as $obj) {
    $paragraph = Paragraph::create([
      'type' => 'objective',
      'descricao' => $obj['desc'],
      'alcancado' => $obj['done'] ? 1 : 0,
    ]);
    $paragraph->save();
    $objective_refs[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

  $node = Node::create([
    'type' => 'projeto',
    'title' => $pdef['title'],
    'status' => 1,
    'uid' => 1,
    'project_short_sentence' => $pdef['short'],
    'project_description' => [
      'value' => $pdef['desc'],
      'format' => 'basic_html',
    ],
    'project_state' => $pdef['state'],
    'project_goal' => $pdef['goal'],
    'project_current_progress' => $pdef['progress'],
    'project_inicial_date' => $pdef['start'],
    'project_final_date' => $pdef['end'],
    'project_objectives' => $objective_refs,
  ]);
  $node->save();
  $projeto_ids[] = (int) $node->id();
  echo "  Criado projeto: {$pdef['title']} (nid: {$node->id()}) [{$pdef['state']}]\n";
}

// Also include any existing projetos.
$all_projetos = $etm->getStorage('node')->loadByProperties(['type' => 'projeto', 'status' => 1]);
foreach ($all_projetos as $p) {
  $projeto_ids[] = (int) $p->id();
}
$projeto_ids = array_unique($projeto_ids);
echo "  Total projetos: " . count($projeto_ids) . "\n\n";

// ====================================================================
// 4. PRODUCTS — Portuguese eco products
// ====================================================================
echo "=== 4. PRODUTOS ===\n";

$products_def = [
  [
    'title' => 'Garrafa Reutilizável Mar Limpo',
    'body' => '<p>Garrafa em aço inoxidável de 750ml com design inspirado no oceano Atlântico. Livre de BPA, mantém bebidas quentes durante 12 horas e frias durante 24 horas.</p>',
    'price' => '18.99',
    'sku_prefix' => 'GRF',
    'weight' => 350,
    'plastic_grams' => 0,
    'tags' => ['Reutilizável', 'Oceano', 'Zero Desperdício'],
    'collections' => ['Limpeza Costeira', 'Vida Marinha'],
  ],
  [
    'title' => 'Saco de Compras em Rede de Pesca Reciclada',
    'body' => '<p>Saco de compras resistente feito a partir de redes de pesca recicladas recolhidas nos portos açorianos. Cada saco remove 200g de plástico do oceano.</p>',
    'price' => '12.50',
    'sku_prefix' => 'SCR',
    'weight' => 180,
    'plastic_grams' => 200,
    'tags' => ['Reciclado', 'Oceano', 'Plástico Reciclado', 'Feito à Mão'],
    'collections' => ['Limpeza Costeira', 'Moda Ética'],
  ],
  [
    'title' => 'Caderno em Papel Semente',
    'body' => '<p>Caderno A5 com capa em papel semente — quando terminar, plante a capa e veja nascer flores silvestres dos Açores. 80 páginas de papel 100% reciclado.</p>',
    'price' => '8.99',
    'sku_prefix' => 'CPS',
    'weight' => 220,
    'plastic_grams' => 0,
    'tags' => ['Sustentável', 'Biodegradável', 'Eco-Friendly'],
    'collections' => ['Casa Sustentável'],
  ],
  [
    'title' => 'Kit Talheres Bambu Viajante',
    'body' => '<p>Kit portátil com garfo, faca, colher, pauzinhos e escova de limpeza em bambu certificado. Inclui bolsa em algodão orgânico. Diga adeus ao plástico descartável!</p>',
    'price' => '14.99',
    'sku_prefix' => 'KTB',
    'weight' => 150,
    'plastic_grams' => 0,
    'tags' => ['Sustentável', 'Zero Desperdício', 'Reutilizável', 'Orgânico'],
    'collections' => ['Aventura Eco'],
  ],
  [
    'title' => 'Vela Artesanal Cera de Abelha Açoriana',
    'body' => '<p>Vela artesanal feita com cera de abelha pura dos Açores e pavio em algodão. Aroma natural de mel e lavanda. Duração aproximada de 45 horas.</p>',
    'price' => '22.00',
    'sku_prefix' => 'VCA',
    'weight' => 400,
    'plastic_grams' => 0,
    'tags' => ['Feito à Mão', 'Orgânico', 'Local'],
    'collections' => ['Casa Sustentável', 'Jardim Verde'],
  ],
  [
    'title' => 'T-shirt Algodão Orgânico "Protege o Oceano"',
    'body' => '<p>T-shirt unisexo em algodão orgânico certificado GOTS com estampagem à base de água. Design exclusivo inspirado na fauna marinha dos Açores.</p>',
    'price' => '29.99',
    'sku_prefix' => 'TSO',
    'weight' => 200,
    'plastic_grams' => 0,
    'tags' => ['Orgânico', 'Comércio Justo', 'Sustentável'],
    'collections' => ['Moda Ética', 'Vida Marinha'],
  ],
  [
    'title' => 'Conjunto Embrulhos Reutilizáveis Cera de Abelha',
    'body' => '<p>Pack de 3 embrulhos alimentares reutilizáveis em tecido de algodão com cera de abelha, resina de pinheiro e óleo de jojoba. Substitui o filme plástico na cozinha.</p>',
    'price' => '16.50',
    'sku_prefix' => 'ERC',
    'weight' => 120,
    'plastic_grams' => 0,
    'tags' => ['Reutilizável', 'Zero Desperdício', 'Eco-Friendly'],
    'collections' => ['Casa Sustentável'],
  ],
  [
    'title' => 'Pulseira Plástico Oceânico Reciclado',
    'body' => '<p>Pulseira artesanal feita a partir de plástico recolhido do oceano Atlântico, próximo dos Açores. Cada pulseira contém aproximadamente 5g de plástico oceânico transformado. Design único — não existem duas iguais.</p>',
    'price' => '9.99',
    'sku_prefix' => 'PPO',
    'weight' => 15,
    'plastic_grams' => 5,
    'tags' => ['Reciclado', 'Plástico Reciclado', 'Oceano', 'Feito à Mão'],
    'collections' => ['Limpeza Costeira', 'Moda Ética'],
  ],
  [
    'title' => 'Sabonete Artesanal Algas dos Açores',
    'body' => '<p>Sabonete natural feito com algas marinhas colhidas sustentavelmente nos Açores, azeite biológico e óleos essenciais. Embalagem em papel reciclado, zero plástico.</p>',
    'price' => '6.99',
    'sku_prefix' => 'SAA',
    'weight' => 100,
    'plastic_grams' => 0,
    'tags' => ['Feito à Mão', 'Local', 'Orgânico', 'Zero Desperdício'],
    'collections' => ['Vida Marinha', 'Casa Sustentável'],
  ],
  [
    'title' => 'Mochila Lona Reciclada Aventureiro',
    'body' => '<p>Mochila resistente feita com lona de vela reciclada de barcos dos Açores. Capacidade 25L, forro impermeável, alças acolchoadas. Cada mochila tem marcas e cores únicas da sua vida anterior no mar.</p>',
    'price' => '54.99',
    'sku_prefix' => 'MLR',
    'weight' => 800,
    'plastic_grams' => 50,
    'tags' => ['Reciclado', 'Feito à Mão', 'Oceano'],
    'collections' => ['Aventura Eco', 'Moda Ética'],
  ],
  [
    'title' => 'Caneca Cerâmica Artesanal Açoriana',
    'body' => '<p>Caneca em cerâmica artesanal produzida por oleiros tradicionais dos Açores. Cada peça é única, pintada à mão com motivos da fauna e flora endémica. Capacidade 300ml.</p>',
    'price' => '15.00',
    'sku_prefix' => 'CCA',
    'weight' => 350,
    'plastic_grams' => 0,
    'tags' => ['Feito à Mão', 'Local'],
    'collections' => ['Casa Sustentável'],
  ],
  [
    'title' => 'Pack Sementes Flores Nativas dos Açores',
    'body' => '<p>Coleção de 8 variedades de sementes de flores e plantas endémicas dos Açores. Inclui guia de cultivo ilustrado. Ideal para criar um jardim que apoie os polinizadores locais.</p>',
    'price' => '11.50',
    'sku_prefix' => 'PSF',
    'weight' => 50,
    'plastic_grams' => 0,
    'tags' => ['Local', 'Biodegradável', 'Sustentável'],
    'collections' => ['Jardim Verde'],
  ],
];

$product_ids = [];
$variation_ids = [];

foreach ($products_def as $idx => $pdef) {
  // Check if product already exists.
  $existing = $etm->getStorage('commerce_product')->loadByProperties(['title' => $pdef['title']]);
  if ($existing) {
    $prod = reset($existing);
    $product_ids[] = (int) $prod->id();
    echo "  Produto já existe: {$pdef['title']}\n";
    continue;
  }

  // Resolve tag term IDs.
  $tag_refs = [];
  foreach ($pdef['tags'] as $tag_name) {
    $tag_refs[] = ['target_id' => seed_get_or_create_term($tag_name, 'product_tags')];
  }

  // Resolve collection term IDs.
  $coll_refs = [];
  foreach ($pdef['collections'] as $coll_name) {
    $coll_refs[] = ['target_id' => seed_get_or_create_term($coll_name, 'product_collections')];
  }

  // Pick a brand and origem.
  $brand_id = $brand_ids[array_rand($brand_ids)];
  $origem_id = $origem_ids[array_rand($origem_ids)];

  // Create variation.
  $sku = $pdef['sku_prefix'] . '-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
  $variation = ProductVariation::create([
    'type' => 'physical',
    'sku' => $sku,
    'price' => new Price($pdef['price'], 'EUR'),
    'status' => 1,
    'weight' => [
      'number' => $pdef['weight'],
      'unit' => 'g',
    ],
    'quantidade_em_gramas_de_plastico' => $pdef['plastic_grams'],
  ]);
  $variation->save();
  $variation_ids[] = (int) $variation->id();

  // Create product.
  $product = Product::create([
    'type' => 'physical',
    'title' => $pdef['title'],
    'body' => [
      'value' => $pdef['body'],
      'format' => 'basic_html',
    ],
    'status' => 1,
    'stores' => [$store_id],
    'variations' => [$variation],
    'product_tags' => $tag_refs,
    'product_collections' => $coll_refs,
    'product_brand' => ['target_id' => $brand_id],
    'origem' => ['target_id' => $origem_id],
    'uid' => 1,
  ]);
  $product->save();
  $product_ids[] = (int) $product->id();

  echo "  Criado produto: {$pdef['title']} (€{$pdef['price']}, SKU: {$sku})\n";
}
echo "  Total novos produtos: " . count($product_ids) . "\n\n";

// Load all product variation IDs for order creation.
$all_variations = $etm->getStorage('commerce_product_variation')->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'physical')
  ->execute();
$all_variation_ids = array_values($all_variations);

// ====================================================================
// 5. DONATION ORDERS — 50 varied donations
// ====================================================================
echo "=== 5. DOAÇÕES ===\n";

$donation_amounts = ['5.00', '10.00', '15.00', '20.00', '25.00', '30.00', '50.00', '75.00', '100.00', '150.00', '200.00', '250.00', '500.00'];
$order_states = ['draft', 'completed', 'completed', 'completed', 'completed']; // 80% completed
$designation_types = ['honor', 'memory'];
$gift_types = ['ecard', 'printcard'];

$first_names = ['Ana', 'João', 'Maria', 'Pedro', 'Sofia', 'Carlos', 'Lúcia', 'Miguel', 'Isabel', 'Tomás', 'Beatriz', 'Rui', 'Inês', 'André', 'Mariana', 'Diogo', 'Carolina', 'Rafael', 'Laura', 'Henrique'];
$last_names = ['Silva', 'Costa', 'Santos', 'Oliveira', 'Pereira', 'Ferreira', 'Rodrigues', 'Almeida', 'Gomes', 'Martins', 'Sousa', 'Lopes', 'Dias', 'Ribeiro', 'Carvalho', 'Mendes', 'Teixeira', 'Correia', 'Nunes', 'Moreira'];

$messages_pt = [
  'Em memória do nosso querido avô.',
  'Feliz aniversário! Uma doação em teu nome.',
  'Obrigado por nos inspirares a cuidar do planeta.',
  'Com amor e gratidão pela natureza.',
  'Por um futuro melhor para os nossos filhos.',
  'Em celebração do teu casamento.',
  'Natal é tempo de dar — para o planeta também.',
  'Porque os Açores merecem ser protegidos.',
  'Um pequeno gesto com grande impacto.',
  '',
];

$donation_count = 0;

for ($i = 0; $i < 50; $i++) {
  $uid = $user_ids[array_rand($user_ids)];
  $projeto_id = $projeto_ids[array_rand($projeto_ids)];
  $amount = $donation_amounts[array_rand($donation_amounts)];
  $state = $order_states[array_rand($order_states)];
  $is_monthly = (rand(0, 4) === 0); // 20% chance
  $is_designated = (rand(0, 4) === 0); // 20% chance
  $is_notify = ($is_designated && rand(0, 1) === 0);

  $user = User::load($uid);
  $email = $user ? $user->getEmail() : "doador{$i}@exemplo.com";

  $price = new Price($amount, 'EUR');

  $item_values = [
    'type' => 'donation',
    'title' => sprintf('Doação para %s', $all_projetos[$projeto_id]->label() ?? 'Projeto'),
    'unit_price' => $price,
    'quantity' => 1,
    'field_donation_amount' => $price,
    'field_monthly' => $is_monthly ? 1 : 0,
    'field_designated' => $is_designated ? 1 : 0,
    'field_designation_type' => $designation_types[array_rand($designation_types)],
    'field_gift_type' => $gift_types[array_rand($gift_types)],
  ];

  if ($is_designated) {
    $item_values['field_honoree_first'] = $first_names[array_rand($first_names)];
    $item_values['field_honoree_last'] = $last_names[array_rand($last_names)];
  }

  if ($is_notify) {
    $item_values['field_notify'] = 1;
    $item_values['field_recipient_first_name'] = $first_names[array_rand($first_names)];
    $item_values['field_recipient_last_name'] = $last_names[array_rand($last_names)];
    $item_values['field_card_email'] = 'notificar' . $i . '@exemplo.com';
    $item_values['field_message'] = $messages_pt[array_rand($messages_pt)];
  }

  if ($is_monthly) {
    $days_ago = rand(0, 300);
    $item_values['field_recurring_begins'] = date('Y-m-d', strtotime("-{$days_ago} days"));
  }

  $order_item = OrderItem::create($item_values);
  $order_item->save();

  // Spread orders across the past 18 months.
  $days_back = rand(0, 540);
  $created_time = strtotime("-{$days_back} days") + rand(0, 86400);

  $order = Order::create([
    'type' => 'donation',
    'store_id' => $store_id,
    'uid' => $uid,
    'mail' => $email,
    'order_items' => [$order_item],
    'field_projeto' => ['target_id' => $projeto_id],
    'state' => $state,
    'created' => $created_time,
    'changed' => $created_time + rand(0, 3600),
  ]);

  if ($state === 'completed') {
    $order->setCompletedTime($created_time + rand(60, 7200));
  }

  $order->save();
  $donation_count++;
}

echo "  Criadas {$donation_count} doações.\n\n";

// ====================================================================
// 6. DEFAULT (PRODUCT) ORDERS — 40 varied orders
// ====================================================================
echo "=== 6. ENCOMENDAS DE PRODUTOS ===\n";

$default_states = ['draft', 'completed', 'completed', 'completed', 'completed'];
$default_order_count = 0;

for ($i = 0; $i < 40; $i++) {
  $uid = $user_ids[array_rand($user_ids)];
  $user = User::load($uid);
  $email = $user ? $user->getEmail() : "comprador{$i}@exemplo.com";
  $state = $default_states[array_rand($default_states)];

  // 1-4 items per order — only use EUR-priced variations.
  $num_items = rand(1, 4);
  $order_items = [];

  for ($j = 0; $j < $num_items; $j++) {
    if (!empty($all_variation_ids)) {
      $var_id = $all_variation_ids[array_rand($all_variation_ids)];
      $variation = $etm->getStorage('commerce_product_variation')->load($var_id);
      if ($variation && $variation->getPrice() && $variation->getPrice()->getCurrencyCode() === 'EUR') {
        $item_price = $variation->getPrice();
        $title = $variation->getTitle() ?: 'Produto #' . $var_id;
        $qty = rand(1, 3);

        $order_item = OrderItem::create([
          'type' => 'physical_product',
          'title' => $title,
          'purchased_entity' => $variation,
          'unit_price' => $item_price,
          'quantity' => $qty,
        ]);
        $order_item->save();
        $order_items[] = $order_item;
      }
    }
  }

  if (empty($order_items)) {
    continue;
  }

  $days_back = rand(0, 540);
  $created_time = strtotime("-{$days_back} days") + rand(0, 86400);

  $order = Order::create([
    'type' => 'default',
    'store_id' => $store_id,
    'uid' => $uid,
    'mail' => $email,
    'order_items' => $order_items,
    'state' => $state,
    'created' => $created_time,
    'changed' => $created_time + rand(0, 3600),
  ]);

  if ($state === 'completed') {
    $order->setCompletedTime($created_time + rand(60, 7200));
  }

  $order->save();
  $default_order_count++;
}

echo "  Criadas {$default_order_count} encomendas de produtos.\n\n";

// ====================================================================
// 7. SUBSCRIPTIONS — All users get meaningful subscription history
// ====================================================================
echo "=== 7. SUBSCRIÇÕES ===\n";

$sub_storage = $etm->getStorage('user_subscription');
$tiers = $etm->getStorage('subscription_tier')->loadMultiple();
$active_tier_ids = [];
foreach ($tiers as $t) {
  if ($t->status() && (float) $t->getPrice() > 0) {
    $active_tier_ids[] = $t->id();
  }
}

$sub_count = 0;

if (!empty($active_tier_ids)) {
  foreach ($user_ids as $uid) {
    if ($uid == 0) {
      continue;
    }

    $user = User::load($uid);
    if (!$user) {
      continue;
    }
    $email = $user->getEmail();

    // Determine a subscription story for this user:
    // 40% → 1 active subscription (loyal subscriber)
    // 20% → 1 active + 1 cancelled (upgraded/switched)
    // 15% → 1 paused (taking a break)
    // 10% → 1 cancelled (churned)
    // 10% → 2 active (supports multiple tiers)
    // 5% → no subscription (never subscribed)
    $roll = rand(1, 100);

    if ($roll <= 5) {
      // No subscription for this user.
      continue;
    }

    $scenarios = [];
    if ($roll <= 45) {
      // 1 active subscription.
      $tier_id = $active_tier_ids[array_rand($active_tier_ids)];
      $scenarios[] = ['tier' => $tier_id, 'status' => 'active', 'period' => 'monthly', 'days_ago' => rand(30, 300)];
    }
    elseif ($roll <= 65) {
      // 1 active + 1 older cancelled (upgraded).
      $tier_1 = $active_tier_ids[array_rand($active_tier_ids)];
      $tier_2 = $active_tier_ids[array_rand($active_tier_ids)];
      $scenarios[] = ['tier' => $tier_1, 'status' => 'cancelled', 'period' => 'monthly', 'days_ago' => rand(180, 500)];
      $scenarios[] = ['tier' => $tier_2, 'status' => 'active', 'period' => 'monthly', 'days_ago' => rand(10, 150)];
    }
    elseif ($roll <= 80) {
      // 1 paused.
      $tier_id = $active_tier_ids[array_rand($active_tier_ids)];
      $scenarios[] = ['tier' => $tier_id, 'status' => 'paused', 'period' => 'monthly', 'days_ago' => rand(30, 200)];
    }
    elseif ($roll <= 90) {
      // 1 cancelled.
      $tier_id = $active_tier_ids[array_rand($active_tier_ids)];
      $scenarios[] = ['tier' => $tier_id, 'status' => 'cancelled', 'period' => 'monthly', 'days_ago' => rand(60, 400)];
    }
    else {
      // 2 active (multi-tier supporter).
      $shuffled = $active_tier_ids;
      shuffle($shuffled);
      $scenarios[] = ['tier' => $shuffled[0], 'status' => 'active', 'period' => 'monthly', 'days_ago' => rand(60, 300)];
      if (isset($shuffled[1])) {
        $periods = ['monthly', 'quarterly', 'yearly'];
        $scenarios[] = ['tier' => $shuffled[1], 'status' => 'active', 'period' => $periods[array_rand($periods)], 'days_ago' => rand(30, 200)];
      }
    }

    foreach ($scenarios as $sc) {
      $tier = $tiers[$sc['tier']];
      $created_time = strtotime("-{$sc['days_ago']} days");

      $base_price = (float) $tier->getPrice();
      $price = match ($sc['period']) {
        'quarterly' => number_format($base_price * 3 * 0.9, 2, '.', ''),
        'yearly' => number_format($base_price * 12 * 0.8, 2, '.', ''),
        default => number_format($base_price, 2, '.', ''),
      };

      if ($sc['status'] === 'active') {
        $next_billing = match ($sc['period']) {
          'monthly' => strtotime('+1 month'),
          'quarterly' => strtotime('+' . rand(1, 90) . ' days'),
          'yearly' => strtotime('+' . rand(1, 365) . ' days'),
          default => strtotime('+1 month'),
        };
      }
      else {
        $next_billing = 0;
      }

      $subscription = $sub_storage->create([
        'tier_id' => $sc['tier'],
        'gateway_id' => 'manual',
        'external_id' => 'seed_' . $uid . '_' . $sc['tier'] . '_' . rand(1000, 9999),
        'subscription_status' => $sc['status'],
        'price' => $price,
        'currency' => $tier->getCurrency(),
        'billing_period' => $sc['period'],
        'next_billing_date' => $next_billing,
        'email' => $email,
        'uid' => $uid,
        'created' => $created_time,
        'changed' => $created_time,
      ]);
      $subscription->save();
      $sub_count++;
    }
  }
}

echo "  Criadas {$sub_count} subscrições.\n\n";

// ====================================================================
// SUMMARY
// ====================================================================
echo "╔══════════════════════════════════════════════╗\n";
echo "║           SEED COMPLETO — RESUMO            ║\n";
echo "╠══════════════════════════════════════════════╣\n";

$final_counts = [
  'Utilizadores' => $etm->getStorage('user')->getQuery()->accessCheck(FALSE)->condition('uid', 0, '>')->count()->execute(),
  'Projetos' => $etm->getStorage('node')->getQuery()->accessCheck(FALSE)->condition('type', 'projeto')->count()->execute(),
  'Produtos' => $etm->getStorage('commerce_product')->getQuery()->accessCheck(FALSE)->count()->execute(),
  'Variações' => $etm->getStorage('commerce_product_variation')->getQuery()->accessCheck(FALSE)->count()->execute(),
  'Encomendas (total)' => $etm->getStorage('commerce_order')->getQuery()->accessCheck(FALSE)->count()->execute(),
  'Subscrições' => $etm->getStorage('user_subscription')->getQuery()->accessCheck(FALSE)->count()->execute(),
];

foreach ($final_counts as $label => $count) {
  echo sprintf("║  %-30s %10s  ║\n", $label, $count);
}

// Order breakdown.
echo "╠══════════════════════════════════════════════╣\n";
$orders_all = $etm->getStorage('commerce_order')->loadMultiple();
$breakdown = [];
foreach ($orders_all as $o) {
  $key = $o->bundle() . '/' . $o->getState()->getId();
  $breakdown[$key] = ($breakdown[$key] ?? 0) + 1;
}
ksort($breakdown);
foreach ($breakdown as $k => $v) {
  echo sprintf("║  Encomendas %-20s %10s  ║\n", $k, $v);
}

// Subscription breakdown.
echo "╠══════════════════════════════════════════════╣\n";
$subs_all = $sub_storage->loadMultiple();
$sub_breakdown = [];
foreach ($subs_all as $s) {
  $key = $s->get('subscription_status')->value;
  $sub_breakdown[$key] = ($sub_breakdown[$key] ?? 0) + 1;
}
ksort($sub_breakdown);
foreach ($sub_breakdown as $k => $v) {
  echo sprintf("║  Subscrições %-19s %10s  ║\n", $k, $v);
}

echo "╚══════════════════════════════════════════════╝\n";

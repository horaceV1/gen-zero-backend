<?php

namespace Drupal\gen_zero_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Admin dashboard controller with quick-access panel.
 */
class DashboardController extends ControllerBase {

  /**
   * Renders the admin dashboard.
   */
  public function dashboard(): array {
    $build = [
      '#theme' => 'gen_zero_dashboard',
      '#stats' => $this->getStats(),
      '#sections' => $this->getSections(),
      '#attached' => [
        'library' => ['gen_zero_dashboard/dashboard'],
      ],
    ];

    return $build;
  }

  /**
   * Gets entity count statistics.
   */
  protected function getStats(): array {
    $stats = [];

    try {
      $stats['products'] = [
        'label' => $this->t('Products'),
        'count' => (int) $this->entityTypeManager()->getStorage('commerce_product')
          ->getQuery()->accessCheck(FALSE)->count()->execute(),
        'icon' => 'shopping_bag',
        'url' => Url::fromRoute('entity.commerce_product.collection')->toString(),
      ];
    }
    catch (\Exception $e) {
      // Commerce not available.
    }

    try {
      $stats['orders'] = [
        'label' => $this->t('Orders'),
        'count' => (int) $this->entityTypeManager()->getStorage('commerce_order')
          ->getQuery()->accessCheck(FALSE)->count()->execute(),
        'icon' => 'receipt_long',
        'url' => Url::fromRoute('entity.commerce_order.collection')->toString(),
      ];
    }
    catch (\Exception $e) {
    }

    try {
      $stats['users'] = [
        'label' => $this->t('Users'),
        'count' => (int) $this->entityTypeManager()->getStorage('user')
          ->getQuery()->accessCheck(FALSE)->condition('uid', 0, '>')->count()->execute(),
        'icon' => 'group',
        'url' => Url::fromRoute('entity.user.collection')->toString(),
      ];
    }
    catch (\Exception $e) {
    }

    if ($this->moduleHandler()->moduleExists('gen_zero_subscriptions')) {
      try {
        $stats['subscriptions'] = [
          'label' => $this->t('Subscriptions'),
          'count' => (int) $this->entityTypeManager()->getStorage('user_subscription')
            ->getQuery()->accessCheck(FALSE)
            ->condition('subscription_status', 'active')
            ->count()->execute(),
          'icon' => 'card_membership',
          'url' => Url::fromUri('internal:/admin/commerce/user-subscriptions')->toString(),
        ];
      }
      catch (\Exception $e) {
      }
    }

    $stats['content'] = [
      'label' => $this->t('Content'),
      'count' => (int) $this->entityTypeManager()->getStorage('node')
        ->getQuery()->accessCheck(FALSE)->count()->execute(),
      'icon' => 'article',
      'url' => Url::fromRoute('system.admin_content')->toString(),
    ];

    return $stats;
  }

  /**
   * Returns all dashboard sections with their links.
   */
  protected function getSections(): array {
    $sections = [];

    // --- Commerce ---
    $sections['commerce'] = [
      'title' => $this->t('Loja / Commerce'),
      'icon' => 'storefront',
      'links' => [
        ['label' => $this->t('Todos os Produtos'), 'url' => '/admin/commerce/products', 'icon' => 'inventory_2', 'description' => $this->t('Ver e gerir todos os produtos')],
        ['label' => $this->t('Adicionar Produto'), 'url' => '/admin/commerce/products/add', 'icon' => 'add_shopping_cart', 'description' => $this->t('Criar novo produto')],
        ['label' => $this->t('Encomendas'), 'url' => '/admin/commerce/orders', 'icon' => 'receipt_long', 'description' => $this->t('Gerir encomendas e pedidos')],
        ['label' => $this->t('Promoções'), 'url' => '/admin/commerce/promotions', 'icon' => 'local_offer', 'description' => $this->t('Gerir cupões e promoções')],
        ['label' => $this->t('Lojas'), 'url' => '/admin/commerce/stores', 'icon' => 'store', 'description' => $this->t('Configurar lojas online')],
        ['label' => $this->t('Tipos de Produto'), 'url' => '/admin/commerce/config/product-types', 'icon' => 'category', 'description' => $this->t('Gerir tipos de produto')],
        ['label' => $this->t('Métodos de Pagamento'), 'url' => '/admin/commerce/config/payment-gateways', 'icon' => 'payments', 'description' => $this->t('Configurar gateways de pagamento')],
        ['label' => $this->t('Envio / Shipping'), 'url' => '/admin/commerce/config/shipping-methods', 'icon' => 'local_shipping', 'description' => $this->t('Configurar métodos de envio')],
      ],
    ];

    // --- Content ---
    $sections['content'] = [
      'title' => $this->t('Conteúdo'),
      'icon' => 'edit_note',
      'links' => [
        ['label' => $this->t('Todos os Conteúdos'), 'url' => '/admin/content', 'icon' => 'list', 'description' => $this->t('Lista completa de todos os conteúdos')],
        ['label' => $this->t('Criar Artigo'), 'url' => '/node/add/article', 'icon' => 'post_add', 'description' => $this->t('Novo artigo ou notícia')],
        ['label' => $this->t('Criar Página'), 'url' => '/node/add/page', 'icon' => 'note_add', 'description' => $this->t('Nova página estática')],
        ['label' => $this->t('Criar Projeto'), 'url' => '/node/add/projeto', 'icon' => 'eco', 'description' => $this->t('Novo projeto')],
        ['label' => $this->t('Criar Página Personalizada'), 'url' => '/node/add/pagina_personalizada', 'icon' => 'dashboard_customize', 'description' => $this->t('Nova página personalizada com blocos')],
        ['label' => $this->t('Criar Landing Page'), 'url' => '/node/add/cklb_landing_page', 'icon' => 'web', 'description' => $this->t('Nova landing page com Layout Builder')],
        ['label' => $this->t('Biblioteca de Blocos'), 'url' => '/admin/content/block', 'icon' => 'view_module', 'description' => $this->t('Gerir blocos de conteúdo reutilizáveis')],
        ['label' => $this->t('Biblioteca de Media'), 'url' => '/admin/content/media', 'icon' => 'perm_media', 'description' => $this->t('Gerir imagens, vídeos e documentos')],
      ],
    ];

    // --- Subscriptions ---
    if ($this->moduleHandler()->moduleExists('gen_zero_subscriptions')) {
      $sections['subscriptions'] = [
        'title' => $this->t('Subscrições'),
        'icon' => 'card_membership',
        'links' => [
          ['label' => $this->t('Subscrições de Utilizadores'), 'url' => '/admin/commerce/user-subscriptions', 'icon' => 'people', 'description' => $this->t('Ver e gerir todas as subscrições ativas')],
          ['label' => $this->t('Grupos de Níveis'), 'url' => '/admin/commerce/subscription-tier-groups', 'icon' => 'folder_special', 'description' => $this->t('Gerir grupos de subscrições')],
          ['label' => $this->t('Níveis de Subscrição'), 'url' => '/admin/commerce/subscription-tiers', 'icon' => 'loyalty', 'description' => $this->t('Gerir planos e preços')],
          ['label' => $this->t('Configurar Gateways'), 'url' => '/admin/commerce/subscriptions/gateway-settings', 'icon' => 'settings', 'description' => $this->t('PayPal, EuPago e outros métodos de pagamento')],
        ],
      ];
    }

    // --- Donations ---
    if ($this->moduleHandler()->moduleExists('gen_zero_donations')) {
      $sections['donations'] = [
        'title' => $this->t('Doações'),
        'icon' => 'volunteer_activism',
        'links' => [
          ['label' => $this->t('Encomendas de Doação'), 'url' => '/admin/commerce/orders', 'icon' => 'receipt_long', 'description' => $this->t('Ver pedidos de doação')],
          ['label' => $this->t('Projetos'), 'url' => '/admin/content?type=projeto', 'icon' => 'eco', 'description' => $this->t('Gerir projetos que aceitam doações')],
        ],
      ];
    }

    // --- Taxonomy ---
    $sections['taxonomy'] = [
      'title' => $this->t('Categorias / Taxonomias'),
      'icon' => 'label',
      'links' => [
        ['label' => $this->t('Tags'), 'url' => '/admin/structure/taxonomy/manage/tags/overview', 'icon' => 'sell', 'description' => $this->t('Tags de artigos')],
        ['label' => $this->t('Tags de Produto'), 'url' => '/admin/structure/taxonomy/manage/product_tags/overview', 'icon' => 'sell', 'description' => $this->t('Tags de pesquisa de produtos')],
        ['label' => $this->t('Coleções de Produto'), 'url' => '/admin/structure/taxonomy/manage/product_collections/overview', 'icon' => 'collections_bookmark', 'description' => $this->t('Agrupamentos de produtos')],
        ['label' => $this->t('Marcas'), 'url' => '/admin/structure/taxonomy/manage/product_brands/overview', 'icon' => 'branding_watermark', 'description' => $this->t('Marcas de produtos')],
        ['label' => $this->t('Ecossistemas'), 'url' => '/admin/structure/taxonomy/manage/ecosystems/overview', 'icon' => 'park', 'description' => $this->t('Classificação de ecossistemas')],
        ['label' => $this->t('Origem'), 'url' => '/admin/structure/taxonomy/manage/origem/overview', 'icon' => 'location_on', 'description' => $this->t('Origem do plástico')],
      ],
    ];

    // --- Webforms ---
    $sections['webforms'] = [
      'title' => $this->t('Formulários'),
      'icon' => 'dynamic_form',
      'links' => [
        ['label' => $this->t('Todos os Formulários'), 'url' => '/admin/structure/webform', 'icon' => 'list_alt', 'description' => $this->t('Gerir webforms')],
        ['label' => $this->t('Submissões'), 'url' => '/admin/structure/webform/submissions/manage', 'icon' => 'inbox', 'description' => $this->t('Ver todas as respostas')],
        ['label' => $this->t('Criar Formulário'), 'url' => '/admin/structure/webform/add', 'icon' => 'add_circle', 'description' => $this->t('Novo formulário')],
      ],
    ];

    // --- Menus ---
    $sections['menus'] = [
      'title' => $this->t('Menus e Navegação'),
      'icon' => 'menu',
      'links' => [
        ['label' => $this->t('Menu Principal'), 'url' => '/admin/structure/menu/manage/main', 'icon' => 'menu_open', 'description' => $this->t('Navegação principal do site')],
        ['label' => $this->t('Menu Footer'), 'url' => '/admin/structure/menu/manage/footer', 'icon' => 'vertical_align_bottom', 'description' => $this->t('Links do rodapé')],
        ['label' => $this->t('Menu Bottom Footer'), 'url' => '/admin/structure/menu/manage/bottom-footer', 'icon' => 'vertical_align_bottom', 'description' => $this->t('Links do rodapé inferior')],
        ['label' => $this->t('Navegação Central'), 'url' => '/admin/structure/menu/manage/navegacao-central', 'icon' => 'segment', 'description' => $this->t('Navegação central do site')],
        ['label' => $this->t('Social'), 'url' => '/admin/structure/menu/manage/social', 'icon' => 'share', 'description' => $this->t('Links de redes sociais')],
        ['label' => $this->t('Todos os Menus'), 'url' => '/admin/structure/menu', 'icon' => 'format_list_bulleted', 'description' => $this->t('Ver e gerir todos os menus')],
      ],
    ];

    // --- Users ---
    $sections['users'] = [
      'title' => $this->t('Utilizadores'),
      'icon' => 'group',
      'links' => [
        ['label' => $this->t('Todos os Utilizadores'), 'url' => '/admin/people', 'icon' => 'people', 'description' => $this->t('Gerir utilizadores')],
        ['label' => $this->t('Adicionar Utilizador'), 'url' => '/admin/people/create', 'icon' => 'person_add', 'description' => $this->t('Criar novo utilizador')],
        ['label' => $this->t('Roles e Permissões'), 'url' => '/admin/people/permissions', 'icon' => 'admin_panel_settings', 'description' => $this->t('Gerir permissões de acesso')],
      ],
    ];

    // --- Configuration ---
    $sections['configuration'] = [
      'title' => $this->t('Configuração do Site'),
      'icon' => 'settings',
      'links' => [
        ['label' => $this->t('Informação do Site'), 'url' => '/admin/config/system/site-information', 'icon' => 'info', 'description' => $this->t('Nome, slogan e email do site')],
        ['label' => $this->t('URLs Amigáveis'), 'url' => '/admin/config/search/path', 'icon' => 'link', 'description' => $this->t('Gerir aliases de URL')],
        ['label' => $this->t('Padrões de URL'), 'url' => '/admin/config/search/pathauto', 'icon' => 'auto_fix_high', 'description' => $this->t('Padrões automáticos de URL')],
        ['label' => $this->t('Pesquisa de Produtos'), 'url' => '/admin/config/search/search-api', 'icon' => 'search', 'description' => $this->t('Configurar indexação de pesquisa')],
        ['label' => $this->t('Relatório de Estado'), 'url' => '/admin/reports/status', 'icon' => 'health_and_safety', 'description' => $this->t('Verificar estado do sistema')],
        ['label' => $this->t('Limpar Cache'), 'url' => '/admin/config/development/performance', 'icon' => 'cached', 'description' => $this->t('Desempenho e cache')],
      ],
    ];

    return $sections;
  }

}

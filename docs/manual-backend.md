---
pdf_options:
  format: A4
  margin: 25mm
  printBackground: true
  headerTemplate: '<div style="font-size:8px;width:100%;text-align:center;color:#999;">Gen Zero — Manual de Gestão do Backend</div>'
  footerTemplate: '<div style="font-size:8px;width:100%;text-align:center;color:#999;"><span class="pageNumber"></span> / <span class="totalPages"></span></div>'
  displayHeaderFooter: true
stylesheet: manual-style.css
---

# Manual de Gestão do Backend — Gen Zero

**Última atualização:** Março 2026

Este manual destina-se aos administradores e gestores de conteúdo do site Gen Zero. Aqui encontrará instruções para gerir todos os aspetos do backend: conteúdos, produtos, encomendas, subscrições, doações, menus, formulários, media, utilizadores e configuração do site.

---

## Índice

1. [Acesso ao Backend](#1-acesso-ao-backend)
2. [Painel de Controlo (Dashboard)](#2-painel-de-controlo-dashboard)
3. [Loja / Commerce](#3-loja--commerce)
4. [Gestão de Conteúdo](#4-gestão-de-conteúdo)
5. [Subscrições](#5-subscrições)
6. [Doações](#6-doações)
7. [Categorias / Taxonomias](#7-categorias--taxonomias)
8. [Formulários (Webforms)](#8-formulários-webforms)
9. [Menus e Navegação](#9-menus-e-navegação)
10. [Biblioteca de Media](#10-biblioteca-de-media)
11. [Blocos de Conteúdo](#11-blocos-de-conteúdo)
12. [Utilizadores e Permissões](#12-utilizadores-e-permissões)
13. [Configuração do Site](#13-configuração-do-site)
14. [Manutenção](#14-manutenção)
15. [Glossário](#15-glossário)

---

## 1. Acesso ao Backend

### 1.1. Login

Para aceder ao painel de administração, visite o endereço do site seguido de **/user/login** no navegador. Exemplo:

> https://www.genzero.pt/user/login

Introduza o seu **email** (ou nome de utilizador) e a **palavra-passe**. Após entrar, será redirecionado para o backend.

### 1.2. Barra de Administração

Após login, verá uma barra escura no topo do ecrã com os seguintes menus:

| Menu | Descrição |
|------|-----------|
| **Conteúdo** | Gerir todos os conteúdos do site (artigos, páginas, projetos, etc.) |
| **Estrutura** | Tipos de conteúdo, blocos, menus, taxonomias, formulários |
| **Aparência** | Temas visuais do site |
| **Extensões** | Módulos e funcionalidades ativas |
| **Configuração** | Definições gerais do sistema |
| **Pessoas** | Gestão de utilizadores e permissões |
| **Relatórios** | Estado do sistema e registos de atividade |

> **Dica:** A barra de administração tem submenus. Passe o rato sobre cada item para ver as opções disponíveis.

### 1.3. Tipos de Conta

| Tipo de Conta | Descrição |
|---------------|-----------|
| **Utilizador anónimo** | Visitantes que não entraram no site |
| **Utilizador autenticado** | Utilizadores com conta registada |
| **Administrador** | Acesso total a todas as funcionalidades do backend |

---

## 2. Painel de Controlo (Dashboard)

O **Painel de Controlo** é a página inicial da administração e está acessível em **Administração → Painel de Controlo** (ou diretamente pelo menu).

### 2.1. Cartões de Estatísticas

No topo encontrará cinco cartões com contadores atualizados em tempo real:

| Cartão | O que mostra |
|--------|-------------|
| **Produtos** | Total de produtos na loja |
| **Encomendas** | Total de encomendas recebidas |
| **Utilizadores** | Total de utilizadores registados |
| **Subscrições Ativas** | Subscrições atualmente ativas |
| **Conteúdos** | Total de artigos, páginas e projetos |

Clique em qualquer cartão para aceder diretamente à lista completa desse tipo.

### 2.2. Secções de Acesso Rápido

Abaixo dos cartões, encontram-se **9 secções** organizadas por funcionalidade, cada uma com links diretos para as tarefas mais comuns:

1. **Loja / Commerce** — Produtos, encomendas, promoções, lojas, tipos de produto, pagamentos, envio
2. **Conteúdo** — Todos os conteúdos, criar artigos/páginas/projetos, blocos, media
3. **Subscrições** — Subscrições de utilizadores, grupos, níveis, gateways
4. **Doações** — Encomendas de doação, projetos
5. **Categorias / Taxonomias** — Tags, coleções, marcas, ecossistemas, origem
6. **Formulários** — Todos os formulários, submissões, criar formulário
7. **Menus e Navegação** — Menu principal, rodapé, redes sociais
8. **Utilizadores** — Lista, criar, roles e permissões
9. **Configuração do Site** — Informação do site, URLs, pesquisa, cache

---

## 3. Loja / Commerce

A loja online é gerida através do sistema **Drupal Commerce**.

### 3.1. Produtos

Aceda a **Administração → Commerce → Produtos** para ver a lista de todos os produtos.

#### Tipos de Produto

| Tipo | Descrição |
|------|-----------|
| **Por defeito** | Produto genérico padrão |
| **Físico (Physical)** | Produto físico que requer envio |
| **Subscrição (Subscription)** | Produto de subscrição recorrente |

#### Como Criar um Produto

1. Vá a **Commerce → Produtos** e clique em **Adicionar produto**
2. Selecione o tipo de produto desejado
3. Preencha os campos:
   - **Título** — Nome do produto
   - **Corpo** — Descrição detalhada do produto
   - **Imagens** — Fotografias do produto (pode adicionar várias)
   - **Variações** — Defina preço, SKU (código interno) e stock
   - **Taxonomias** — Associe tags, coleções e/ou marcas
4. Na secção de publicação, escolha se o produto fica publicado imediatamente
5. Clique em **Guardar**

#### Gerir Variações de Produto

Cada produto pode ter múltiplas **variações** (por exemplo: tamanhos, cores). Para cada variação defina:

- **Preço** — Valor em euros (€)
- **SKU** — Código único de identificação interna
- **Stock** — Quantidade disponível (quando aplicável)

Para editar variações, abra o produto e navegue até à secção **Variações**.

### 3.2. Encomendas

Aceda a **Commerce → Encomendas** para ver todas as encomendas do site.

#### Tipos de Encomenda

| Tipo | Descrição |
|------|-----------|
| **Por defeito** | Encomendas normais da loja |
| **Doação** | Encomendas criadas por doações |

#### Estados de Encomenda

| Estado | Significado |
|--------|------------|
| **Draft** | Encomenda ainda no carrinho (não finalizada) |
| **Completed** | Pagamento confirmado, encomenda concluída |
| **Canceled** | Encomenda cancelada |

#### O que pode fazer na lista de encomendas

- **Filtrar** por estado, data ou utilizador
- **Ver detalhes** clicando no número da encomenda
- **Alterar o estado** de uma encomenda (ex: marcar como concluída)
- **Editar informações** de envio
- **Adicionar notas** administrativas internas

### 3.3. Promoções e Cupões

Aceda a **Commerce → Promoções** para gerir ofertas e descontos.

Pode criar dois tipos de promoção:

- **Cupões** — Códigos de desconto que o cliente introduz durante a compra (ex: DESCONTO10)
- **Promoções automáticas** — Aplicadas automaticamente quando certas condições são cumpridas (ex: desconto em compras acima de 50€)

Para criar uma promoção:

1. Clique em **Adicionar promoção**
2. Defina o nome, tipo de desconto (percentagem ou valor fixo) e condições
3. Se desejar cupões, ative a opção e crie os códigos
4. Defina datas de validade, se aplicável
5. Guardar

### 3.4. Lojas

Aceda a **Commerce → Lojas** para configurar as informações da loja:

- Nome e tipo de loja
- Moeda predefinida (EUR)
- Endereço fiscal
- Emails de notificação de encomendas

### 3.5. Métodos de Pagamento

Aceda a **Commerce → Configuração → Gateways de Pagamento** para ver e configurar os métodos de pagamento da loja online.

### 3.6. Métodos de Envio

Aceda a **Commerce → Configuração → Métodos de Envio** para configurar as opções e taxas de envio para produtos físicos.

---

## 4. Gestão de Conteúdo

Aceda a **Conteúdo** na barra de administração para ver a lista completa de todos os conteúdos do site.

### 4.1. Tipos de Conteúdo

O site possui os seguintes tipos de conteúdo:

| Tipo | Descrição |
|------|-----------|
| **Artigo** | Artigos e notícias do blog |
| **Página Básica** | Páginas estáticas simples (ex: "Sobre Nós") |
| **Projeto** | Projetos ambientais e sociais |
| **Página Personalizada** | Página com múltiplos blocos de conteúdo visuais |
| **Landing Page** | Página construída visualmente com Layout Builder |
| **Informações** | Páginas informativas gerais |
| **Webform** | Conteúdo com formulário integrado |

### 4.2. Criar um Artigo

1. Vá a **Conteúdo → Adicionar conteúdo → Artigo**
2. Preencha:
   - **Título** — Título do artigo
   - **Corpo** — Escreva o texto no editor visual (pode formatar texto, inserir links, listas, etc.)
   - **Imagem** — Adicione uma imagem de destaque
   - **Tags** — Associe tags para classificar o artigo
3. Em **Opções de publicação**, escolha:
   - **Publicado** — O artigo fica visível no site
   - **Promovido para a frontpage** — Aparece na página inicial
   - **Fixo no topo** — Mantém-se no topo das listagens
4. Clique em **Guardar**

### 4.3. Criar uma Página Básica

1. Vá a **Conteúdo → Adicionar conteúdo → Página Básica**
2. Preencha o **Título** e o **Corpo**
3. Configure as opções de publicação
4. Guardar

### 4.4. Criar um Projeto

1. Vá a **Conteúdo → Adicionar conteúdo → Projeto**
2. Preencha os campos específicos do projeto (título, descrição, campos ambientais)
3. Os projetos podem ser associados ao sistema de doações
4. Guardar

### 4.5. Criar uma Página Personalizada

1. Vá a **Conteúdo → Adicionar conteúdo → Página Personalizada**
2. Preencha o **Título**
3. Associe **blocos de conteúdo** através do campo de referência — pode escolher entre os vários tipos de blocos disponíveis (hero, texto, imagens, CTA, FAQ, etc.)
4. Ideal para páginas com layouts visuais compostos por múltiplas secções
5. Guardar

### 4.6. Criar uma Landing Page

1. Vá a **Conteúdo → Adicionar conteúdo → Landing Page**
2. Preencha o **Título**
3. Utilize o **Layout Builder** — uma ferramenta visual onde pode:
   - Adicionar **secções** com diferentes layouts (1 coluna, 2 colunas, etc.)
   - Arrastar e soltar **blocos** dentro de cada secção
   - Pré-visualizar o resultado em tempo real
4. Guardar

### 4.7. Editar Conteúdo Existente

1. Aceda a **Conteúdo** na barra de administração
2. Utilize os **filtros** para encontrar o conteúdo:
   - Filtrar por tipo (Artigo, Página, Projeto, etc.)
   - Filtrar por estado (Publicado, Não publicado)
   - Pesquisar por título
3. Clique em **Editar** na linha do conteúdo desejado
4. Faça as alterações necessárias
5. Clique em **Guardar**

### 4.8. Publicar / Despublicar Conteúdo

Para alterar o estado de publicação:

1. Na lista de conteúdos, marque a caixa dos itens desejados
2. No menu **Ação**, selecione "Publicar conteúdo" ou "Despublicar conteúdo"
3. Clique em **Aplicar a itens selecionados**

### 4.9. Revisões e Histórico

Cada vez que guarda uma edição, é criada uma nova **revisão**. Para consultar o histórico:

1. Abra o conteúdo que deseja verificar
2. Clique no separador **Revisões**
3. Pode:
   - **Comparar** duas versões lado a lado
   - **Reverter** para uma versão anterior, caso tenha cometido um erro

---

## 5. Subscrições

O sistema de subscrições permite gerir planos recorrentes para os utilizadores.

### 5.1. Grupos de Níveis

Aceda a **Commerce → Grupos de Níveis de Subscrição** para ver os grupos que organizam os planos.

O grupo atualmente configurado:

| Grupo | Descrição |
|-------|-----------|
| **Geração Sustentável** | Grupo principal de subscrições do site |

Para **criar um novo grupo**:

1. Clique em **Adicionar grupo**
2. Preencha o nome e a descrição
3. Guardar

### 5.2. Níveis de Subscrição (Planos)

Aceda a **Commerce → Níveis de Subscrição** para ver e gerir os planos disponíveis.

Os planos atualmente configurados:

| Plano | Preço Mensal | Descrição |
|-------|-------------|-----------|
| **Semente** | €5/mês | Plano inicial |
| **Arbusto** | €10/mês | Plano intermédio |
| **Árvore** | €20/mês | Plano avançado |
| **Floresta** | €50/mês | Plano premium |

Para **criar ou editar um plano**:

1. Clique em **Adicionar nível** (ou **Editar** num existente)
2. Preencha:
   - **Nome** — Nome do plano (ex: "Floresta")
   - **Grupo** — Selecione o grupo de subscrição
   - **Preço** — Valor em euros
   - **Período de faturação** — Mensal ou anual
   - **Descrição** — Texto descritivo do plano
   - **Benefícios** — Lista de benefícios incluídos (um por linha)
3. Guardar

### 5.3. Subscrições de Utilizadores

Aceda a **Commerce → Subscrições de Utilizadores** para ver todas as subscrições registadas.

Cada subscrição mostra:
- **Utilizador** — Quem subscreveu
- **Plano** — Nível de subscrição
- **Estado** — Estado atual
- **Datas** — Início e próxima renovação

| Estado | Significado |
|--------|------------|
| **Ativa** | Subscrição em dia, a funcionar normalmente |
| **Pendente** | Aguarda confirmação de pagamento |
| **Cancelada** | Cancelada pelo utilizador |
| **Expirada** | Período expirou e não foi renovada |

### 5.4. Configuração dos Gateways de Pagamento

Aceda a **Commerce → Configurar Gateways de Subscrições** para configurar os métodos de pagamento das subscrições.

Existem três gateways disponíveis:

#### PayPal

Pagamentos recorrentes através do PayPal.

Campos a preencher:
- **Client ID** — Fornecido pelo PayPal Business
- **Secret** — Chave secreta fornecida pelo PayPal
- **Modo** — Escolha "sandbox" para testes ou "live" para produção

#### EuPago

Pagamentos portugueses via Multibanco, MB WAY e Cartão de Crédito.

Campos a preencher:
- **API Key** — Chave fornecida pela EuPago
- **Modo** — Escolha "sandbox" para testes ou "live" para produção

#### Manual / Offline

Permite registar subscrições manualmente sem processar pagamento. Útil para:
- Subscrições de cortesia
- Testes internos
- Gestão pelo administrador

---

## 6. Doações

O sistema de doações permite receber contribuições associadas a projetos.

### 6.1. Encomendas de Doação

As doações aparecem como encomendas na secção **Commerce → Encomendas**. Para ver apenas doações, filtre pelo tipo **Doação** na lista de encomendas.

### 6.2. Projetos para Doação

Os projetos disponíveis para doação são conteúdos do tipo **Projeto**. Para gerir os projetos:

1. Aceda a **Conteúdo** e filtre por tipo **Projeto**
2. Cada projeto pode ter um objetivo de angariação financeira
3. Crie novos projetos seguindo as instruções da secção 4.4

---

## 7. Categorias / Taxonomias

As **taxonomias** são sistemas de classificação que organizam os conteúdos e produtos do site. Aceda a **Estrutura → Taxonomias** para gerir todas as categorias.

### 7.1. Vocabulários Disponíveis

| Vocabulário | Utilização |
|-------------|------------|
| **Tags** | Classificação geral de artigos e conteúdos |
| **Product Tags** | Tags de pesquisa específicas para produtos |
| **Product Collections** | Agrupamentos e coleções de produtos (ex: "Coleção Verão") |
| **Product Brands** | Marcas dos produtos |
| **Ecossistemas** | Classificação por tipo de ecossistema |
| **Origem** | Origem geográfica (do plástico/material) |

### 7.2. Adicionar Termos a uma Taxonomia

1. Aceda a **Estrutura → Taxonomias**
2. Na linha do vocabulário desejado, clique em **Listar termos**
3. Clique em **Adicionar termo**
4. Preencha:
   - **Nome** — Nome do termo (ex: "Sustentável", "Portugal")
   - **Descrição** — Descrição opcional
5. Guardar

### 7.3. Reorganizar Termos

Na lista de termos de um vocabulário, pode **arrastar e soltar** os termos para alterar a ordem ou criar hierarquias (termos filhos dentro de termos pai).

Clique em **Guardar** após reorganizar.

### 7.4. Editar ou Eliminar Termos

Na lista de termos, utilize os botões **Editar** ou **Eliminar** na linha do termo desejado.

> **Atenção:** Eliminar um termo remove a sua associação de todos os conteúdos e produtos. Verifique antes se o termo está em uso.

---

## 8. Formulários (Webforms)

O site dispõe de um sistema de formulários flexível. Aceda a **Estrutura → Webforms** para gerir todos os formulários.

### 8.1. Formulários Configurados

| Formulário | Descrição |
|------------|-----------|
| **Contact** | Formulário de contacto geral |
| **Contact Us** | Formulário de contacto alternativo |
| **Inscrição** | Formulário de inscrição ou registo |
| **Subscribe** | Formulário de newsletter |
| **Feedback** | Formulário de feedback e sugestões |
| **Problema** | Reportar problemas ou incidentes |
| **Job Application** | Candidaturas de emprego |
| **Job Seeker Profile** | Perfil de candidato |
| **Employee Evaluation** | Avaliação de colaboradores |
| **Session Evaluation** | Avaliação de sessão/evento |
| **Medical Appointment** | Marcação de consulta médica |
| **User Profile** | Perfil de utilizador |

### 8.2. Criar um Novo Formulário

1. Aceda a **Estrutura → Webforms** e clique em **Adicionar webform**
2. Defina o **título** do formulário
3. Na aba **Construir (Build)**, adicione campos:
   - Clique em **Adicionar elemento**
   - Escolha o tipo de campo: texto simples, email, área de texto, seleção, caixa de verificação, upload de ficheiro, entre outros
   - Configure cada campo (obrigatório, texto de ajuda, valor padrão, etc.)
4. Na aba **Definições (Settings)**:
   - Configure o **email de notificação** — para onde enviar os dados quando alguém preenche o formulário
   - Configure a **mensagem de confirmação** que aparece após submissão
5. Teste o formulário acedendo à sua página

### 8.3. Editar um Formulário Existente

1. Na lista de webforms, clique em **Build** no formulário desejado
2. Adicione, remova ou reordene campos
3. Guardar

### 8.4. Ver Submissões (Respostas)

Aceda a **Estrutura → Webforms → Submissões** para consultar todas as respostas recebidas.

Pode:
- **Filtrar** por formulário e por data
- **Ver detalhes** de cada submissão individual
- **Exportar** os dados em formato CSV (folha de cálculo)
- **Eliminar** submissões específicas

---

## 9. Menus e Navegação

Os menus controlam a navegação do site. Aceda a **Estrutura → Menus** para gerir todos os menus.

### 9.1. Menus do Site

| Menu | Onde aparece |
|------|-------------|
| **Navegação Principal** | Menu principal no cabeçalho do site |
| **Rodapé (Footer)** | Links no rodapé principal |
| **Bottom Footer** | Links no rodapé inferior (termos, privacidade, etc.) |
| **Navegação Central** | Navegação adicional (mega menu ou secções centrais) |
| **Social** | Links para redes sociais (Facebook, Instagram, etc.) |
| **Menu de Utilizador** | Links de conta (login, perfil, logout) |
| **Ferramentas** | Ferramentas de administração |

### 9.2. Adicionar um Link a um Menu

1. Aceda a **Estrutura → Menus**
2. Na linha do menu desejado, clique em **Editar menu**
3. Clique em **Adicionar link**
4. Preencha:
   - **Título do link** — O texto que o visitante verá (ex: "Sobre Nós")
   - **Caminho** — Para páginas internas, use o caminho relativo (ex: `/sobre-nos`). Para sites externos, use o URL completo (ex: `https://facebook.com/genzero`)
   - **Ativado** — Marque para tornar o link visível
   - **Peso** — Define a posição (números menores aparecem primeiro)
5. Guardar

### 9.3. Reorganizar Links

Na página de edição do menu:

1. **Arraste** os links para cima ou para baixo para alterar a ordem
2. **Arraste para a direita** para criar submenus (links filhos)
3. Clique em **Guardar** após reorganizar

### 9.4. Editar ou Remover Links

Na lista de links do menu, utilize os botões de **Editar** ou **Eliminar** em cada link.

> **Nota:** Desativar um link (desmarcar "Ativado") torna-o invisível no site sem o eliminar. Útil para esconder temporariamente itens do menu.

---

## 10. Biblioteca de Media

A biblioteca de media centraliza todos os ficheiros do site. Aceda a **Conteúdo → Media** para gerir ficheiros.

### 10.1. Tipos de Media Disponíveis

| Tipo | Formatos Aceites |
|------|-----------------|
| **Imagem** | JPG, PNG, GIF, WebP, SVG |
| **Vídeo** | MP4, WebM |
| **Vídeo Remoto** | URLs do YouTube e Vimeo |
| **Áudio** | MP3, WAV, OGG |
| **Documento** | PDF, DOC, DOCX, XLSX, etc. |
| **Modelos 3D** | Ficheiros de modelos tridimensionais |

### 10.2. Adicionar Media à Biblioteca

1. Aceda a **Conteúdo → Media** e clique em **Adicionar media**
2. Selecione o tipo de media (imagem, documento, vídeo, etc.)
3. **Faça upload** do ficheiro ou, no caso de vídeos remotos, **cole o URL** do YouTube/Vimeo
4. Preencha:
   - **Nome** — Nome descritivo para identificar o ficheiro na biblioteca
   - **Texto alternativo** (para imagens) — Descrição da imagem para acessibilidade
5. Guardar

### 10.3. Utilizar Media nos Conteúdos

Quando estiver a criar ou editar um artigo, página ou produto:

1. No campo de imagem/media, clique em **Selecionar media**
2. Pode:
   - **Pesquisar** na biblioteca existente pelo nome
   - **Fazer upload** direto de um novo ficheiro
3. Selecione o ficheiro desejado e clique em **Inserir**

> **Dica:** Dê nomes descritivos às suas imagens e documentos para facilitar a pesquisa posterior na biblioteca.

---

## 11. Blocos de Conteúdo

Os **blocos de conteúdo** são componentes visuais reutilizáveis. São especialmente úteis para construir Páginas Personalizadas e Landing Pages. Aceda a **Conteúdo → Biblioteca de Blocos** para os gerir.

### 11.1. Tipos de Bloco Disponíveis

| Tipo | Descrição |
|------|-----------|
| **Bloco Básico** | Texto simples formatado |
| **Hero Simples** | Secção de destaque com título e imagem de fundo |
| **Frontpage Hero** | Hero específico para a página inicial |
| **Hero Block** | Bloco hero genérico personalizável |
| **Imagem** | Bloco com uma imagem |
| **Texto** | Bloco de texto formatado |
| **Título** | Bloco com título estilizado |
| **Botão** | Botão de chamada à ação (Call-to-Action) |
| **Produtos** | Grelha de produtos em destaque |
| **CTA Card** | Cartão de chamada à ação |
| **CTA Imagem** | Chamada à ação com imagem de fundo |
| **CTA Vídeo** | Chamada à ação com vídeo |
| **Contador (Count Up)** | Contador animado para estatísticas (ex: "150 árvores plantadas") |
| **Formulário de Contacto** | Bloco com formulário de contacto embutido |
| **Perguntas Frequentes (FAQ)** | Secção de perguntas e respostas em acordeão |
| **Secção de Imagens** | Galeria ou grelha de imagens |
| **Secção Localização** | Mapa e informações de localização |
| **Subscrições** | Tabela comparativa de planos de subscrição |

### 11.2. Criar um Bloco

1. Aceda a **Conteúdo → Biblioteca de Blocos** e clique em **Adicionar bloco de conteúdo**
2. Selecione o tipo de bloco desejado
3. Preencha os campos específicos de cada tipo (título, texto, imagem, links, etc.)
4. Guardar

### 11.3. Como Usar Blocos nos Conteúdos

Os blocos podem ser utilizados de várias formas:

- **Em Páginas Personalizadas** — Ao criar/editar uma Página Personalizada, utilize o campo de referência de blocos para selecionar e ordenar os blocos que compõem a página
- **Em Landing Pages** — Ao usar o Layout Builder, clique em **Adicionar bloco** dentro de uma secção e selecione da biblioteca
- **Em Regiões do Tema** — Aceda a **Estrutura → Layout de Blocos** para colocar blocos em regiões fixas do site (cabeçalho, barra lateral, rodapé)

> **Dica:** Os blocos são reutilizáveis. Um mesmo bloco pode ser usado em várias páginas. Se editar o bloco, a alteração reflete em todas as páginas onde é utilizado.

---

## 12. Utilizadores e Permissões

### 12.1. Gerir Utilizadores

Aceda a **Pessoas** na barra de administração para ver a lista de todos os utilizadores registados.

Na lista de utilizadores pode:
- **Filtrar** por nome, email, tipo de conta ou estado
- **Bloquear/Desbloquear** contas de utilizador
- **Atribuir ou remover** tipos de conta (roles)
- **Editar** informações de perfil

### 12.2. Criar um Novo Utilizador

1. Aceda a **Pessoas** e clique em **Adicionar utilizador**
2. Preencha:
   - **Email** — Endereço de email (obrigatório)
   - **Nome de utilizador** — Nome usado para login (obrigatório)
   - **Palavra-passe** — Defina uma palavra-passe ou opte por enviar um link de definição ao utilizador
   - **Estado** — Ativo (pode aceder) ou Bloqueado (não pode aceder)
   - **Tipo de conta** — Selecione "Utilizador autenticado" ou "Administrador"
3. Guardar

### 12.3. Gerir Permissões

Aceda a **Pessoas → Permissões** para definir o que cada tipo de conta pode fazer.

A tabela mostra todas as permissões organizadas por funcionalidade. Para cada permissão, marque ou desmarque as caixas de cada tipo de conta.

> **Importante:** Tenha cuidado ao alterar permissões. Atribuir permissões administrativas a utilizadores comuns pode comprometer a segurança do site. Em caso de dúvida, consulte a equipa técnica.

---

## 13. Configuração do Site

### 13.1. Informação Geral do Site

Aceda a **Configuração → Sistema → Informação do site** para alterar:

- **Nome do site** — O nome que aparece no navegador e nos emails enviados pelo site
- **Slogan** — Subtítulo ou tagline do site
- **Email do site** — Endereço de onde saem os emails automáticos (contacto, notificações, etc.)
- **Página de erro 403** — Página mostrada quando alguém tenta aceder sem permissão
- **Página de erro 404** — Página mostrada quando o endereço não existe
- **Página inicial** — Qual página é a frontpage do site

### 13.2. URLs Amigáveis

Aceda a **Configuração → Pesquisa → Aliases de URL** para gerir os endereços amigáveis do site.

Um alias transforma um endereço interno difícil de ler num endereço legível. Por exemplo:
- Interno: `/node/5` → Alias: `/sobre-nos`

Para criar um alias manualmente:
1. Clique em **Adicionar alias**
2. Preencha o caminho interno e o alias desejado
3. Guardar

### 13.3. Padrões Automáticos de URL (Pathauto)

Aceda a **Configuração → Pesquisa → Padrões de URL** para configurar a geração automática de URLs amigáveis.

Exemplo de padrões configurados:
- Artigos ficam com URL do tipo: `/blog/titulo-do-artigo`
- Produtos ficam com URL do tipo: `/loja/nome-do-produto`
- Projetos ficam com URL do tipo: `/projetos/nome-do-projeto`

> **Nota:** Os URLs são gerados automaticamente com base nestes padrões quando cria novo conteúdo. Não precisa de criar aliases manualmente para os tipos que têm padrões definidos.

### 13.4. Pesquisa de Produtos (Search API)

Aceda a **Configuração → Pesquisa → Search API** para gerir a pesquisa de produtos na loja.

Aqui pode:
- Ver o estado do **índice de pesquisa**
- **Forçar a reindexação** se novos produtos não aparecem nos resultados de pesquisa
- Gerir quais campos são pesquisáveis

### 13.5. Relatório de Estado

Aceda a **Relatórios → Estado** para ver uma visão geral do estado do sistema.

Este relatório mostra:
- Versões do software instalado
- Estado da base de dados
- Espaço em disco
- Atualizações de segurança pendentes
- Possíveis problemas de configuração

> Os itens com fundo **vermelho** requerem atenção urgente. Os itens com fundo **amarelo** são avisos que devem ser verificados.

### 13.6. Performance e Cache

Aceda a **Configuração → Desenvolvimento → Performance** para gerir o cache do site.

- **Limpar todas as caches** — Use este botão quando alterações que fez não aparecem no site
- **Agregação de ficheiros** — Quando ativa, melhora a velocidade de carregamento do site

> **Quando limpar cache?** Sempre que editar menus, alterar configurações, ou quando notar que uma alteração não é refletida no site.

---

## 14. Manutenção

### 14.1. Limpar Cache

Se fez alterações que não estão a aparecer no site, limpe a cache:

1. Aceda a **Configuração → Desenvolvimento → Performance**
2. Clique no botão **Limpar todas as caches**
3. Aguarde a confirmação

### 14.2. Executar Cron

O **cron** executa tarefas automáticas do sistema (enviar emails pendentes, atualizar índices de pesquisa, limpar dados temporários). Normalmente é executado automaticamente, mas pode forçar a execução:

1. Aceda a **Configuração → Sistema → Cron**
2. Clique em **Executar cron**

### 14.3. Verificar Atualizações

Aceda a **Relatórios → Atualizações disponíveis** para verificar se existem atualizações de segurança ou funcionalidade pendentes.

> **Importante:** Atualizações devem ser aplicadas pela equipa técnica. Não tente atualizar módulos diretamente pelo backend se não tiver formação técnica.

---

## 15. Glossário

| Termo | Definição |
|-------|-----------|
| **Artigo** | Conteúdo tipo notícia ou post de blog |
| **Bloco** | Componente visual reutilizável que pode ser colocado em páginas |
| **Cache** | Memória temporária que acelera o site; limpar o cache atualiza o conteúdo visível |
| **Cron** | Tarefa automática periódica que mantém o sistema atualizado |
| **Encomenda** | Pedido de compra feito por um cliente na loja |
| **Formulário (Webform)** | Formulário de recolha de dados (contacto, inscrição, feedback, etc.) |
| **Gateway** | Serviço que processa os pagamentos (PayPal, EuPago, etc.) |
| **Landing Page** | Página construída visualmente com o Layout Builder |
| **Layout Builder** | Ferramenta visual para arrastar e soltar secções e blocos numa página |
| **Media** | Ficheiro multimédia (imagem, vídeo, documento, áudio) na biblioteca do site |
| **Menu** | Conjunto de links que forma a navegação do site |
| **Nível de Subscrição** | Plano de subscrição com preço e benefícios específicos |
| **Página Personalizada** | Página composta por múltiplos blocos de conteúdo visual |
| **Promoção** | Desconto ou cupão aplicado a compras na loja |
| **Projeto** | Conteúdo que representa um projeto ambiental ou social |
| **Revisão** | Versão guardada de um conteúdo, permitindo reverter alterações |
| **SKU** | Código único interno de identificação de um produto/variação |
| **Submissão** | Resposta enviada por um visitante ao preencher um formulário |
| **Taxonomia** | Sistema de classificação por categorias (tags, marcas, etc.) |
| **Termo** | Categoria individual dentro de uma taxonomia |
| **Variação** | Versão específica de um produto (tamanho, cor) com preço e stock próprios |

---

*Manual de Gestão do Backend — Gen Zero. Para questões técnicas, contacte a equipa de desenvolvimento.*

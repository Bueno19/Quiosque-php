<div align="center">
  <h1>📊 Kiosk Manager | Sistema de Gestão & PDV</h1>

  <p>
    <strong>Solução robusta para gerenciamento de quiosques, bares e lanchonetes.</strong>
    <br />
    Desenvolvido em PHP Puro (Vanilla) com arquitetura MVC, focado em performance e código limpo.
  </p>

  <br />

  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript" />
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5" />
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3" />
</div>

<br />

![Preview do Sistema](public/preview.png)

## 💻 Sobre o Projeto

O **Kiosk Manager** é um sistema web full-stack desenvolvido para automatizar o fluxo de vendas e gestão.

Diferente de soluções baseadas em frameworks, este projeto foi construído "do zero" utilizando **PHP 8** e o padrão de arquitetura **MVC (Model-View-Controller)**. Isso demonstra um domínio sólido sobre os fundamentos do desenvolvimento web, gerenciamento de sessões, segurança com PDO e criação de APIs internas.

## ✨ Funcionalidades Principais

### 🛒 Frente de Caixa (PDV)
- **Vendas Ágeis:** Interface otimizada para lançar pedidos rapidamente.
- **Controle de Sessão:** Abertura e fechamento de caixa com conferência de valores.
- **Integração:** Baixa automática de estoque ao finalizar venda.

### 🍽 Gestão Operacional
- **Mapa de Mesas:** Visualização em tempo real (Livre/Ocupada).
- **Estoque:** Cadastro de produtos, fornecedores e alertas de nível baixo.
- **Relatórios:** Gráficos financeiros e extratos de movimentações.

### ⚙️ Diferenciais Técnicos
- **Auto-Instalação (Bootstrap):** O sistema detecta a primeira execução e cria as tabelas do banco de dados automaticamente.
- **Arquitetura MVC:** Separação clara de responsabilidades (Models, Views, Controllers/APIs).
- **Dark Mode:** Tema escuro/claro persistente via LocalStorage.
- **Sem Frameworks:** JavaScript puro (Vanilla) consumindo APIs PHP via Fetch.

## 🚀 Instalação e Configuração

Como o sistema possui um instalador automático, o processo é muito simples:

1. **Clone o repositório:**
   ```bash
   git clone [https://github.com/Bueno19/Quiosque-php.git](https://github.com/Bueno19/Quiosque-php.git)

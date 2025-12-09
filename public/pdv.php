<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

// Proteção da página
if (!isset($_SESSION['id_sessao_ativa'])) {
    header("Location: ../index.php");
    exit;
}

// Carrega config para moeda
$configModel = new Configuracao();
$configuracoes = $configModel->carregarConfiguracoes();
$moeda = $configuracoes['moeda_simbolo'] ?? 'R$';

// Verifica permissão (exemplo)
$isSupervisor = ($utilizadorLogado['cargo'] === 'Admin' || $utilizadorLogado['cargo'] === 'Gerente');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"> <title>PDV / Vendas - Sistema Quiosque</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .modal-overlay { display: none; align-items: center; justify-content: center; }

        /* === LAYOUT CORRIGIDO PARA A COLUNA DE PRODUTOS === */
        .produtos-container {
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden; /* Esconde overflow do container */
            padding: 16px !important; 
        }
        
        #pdv-pesquisa-produto {
            flex-shrink: 0; /* Campo de pesquisa não encolhe */
            margin-bottom: 16px;
        }

        .produtos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
            
            /* ESSENCIAL: Ocupa espaço e permite scroll */
            flex-grow: 1;
            overflow-y: auto;
            min-height: 0; 
            
            padding: 5px; 
            margin-right: -5px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
        }
        .produtos-grid::-webkit-scrollbar { width: 8px; }
        .produtos-grid::-webkit-scrollbar-track { background: transparent; }
        .produtos-grid::-webkit-scrollbar-thumb { background-color: var(--primary); border-radius: 4px; border: 2px solid var(--surface); }

        .produto-card {
            background-color: var(--surface-alt); 
            padding: 12px; 
            border-radius: 8px; 
            text-align: center; cursor: pointer; transition: var(--transition);
            border: 1px solid var(--border-dark);
            display: flex; flex-direction: column; justify-content: space-between;
            min-height: 120px; 
        }
        .produto-card:hover { transform: translateY(-3px); border-color: var(--primary); }
        .produto-card-nome {
            font-weight: 600; font-size: 0.85em; 
            margin-bottom: 6px;
            height: 2.8em; 
            line-height: 1.4em;
            overflow: hidden; text-overflow: ellipsis;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        }
        .produto-card-preco { font-size: 1.1em; color: var(--success); font-weight: 700; margin-top: auto; }
        /* === FIM LAYOUT CORRIGIDO PRODUTOS === */

        .movimentacao-botoes {
            display: flex; gap: 10px; margin-top: 16px;
            padding-top: 16px; border-top: 1px solid var(--border-dark);
            flex-shrink: 0; 
        }
        .movimentacao-botoes button { flex-grow: 1; font-size: 0.9em; padding: 12px 10px; }
        
        #modal-movimentacao .modal-body { padding-top: 10px; }
        #modal-movimentacao label { margin-bottom: 4px; font-size: 0.9em; }
        #modal-movimentacao input, #modal-movimentacao select, #modal-movimentacao textarea { margin-bottom: 12px; }
        #modal-movimentacao textarea { height: 70px; }
        #campo-tipo-saida { display: none; }

        /* Menu Supervisor */
        .supervisor-menu { position: relative; }
        #btn-supervisor-menu { background: transparent; color: var(--text-primary); border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        #btn-supervisor-menu:hover { background-color: var(--surface-alt); }
        #supervisor-menu-dropdown { display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); padding: 8px; width: 220px; z-index: 20; border: 1px solid var(--border-dark); }
        #supervisor-menu-dropdown a, #supervisor-menu-dropdown button { display: flex; width: 100%; align-items: center; gap: 8px; padding: 10px 12px; text-decoration: none; color: var(--text-primary); background: none; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; text-align: left; }
        #supervisor-menu-dropdown a:hover, #supervisor-menu-dropdown button:hover { background-color: var(--primary); color: #fff; }

        #btn-atalho-mesas { background: none; border: 1px solid var(--primary); color: var(--primary); padding: 8px 16px; font-size: 13px; }
        #btn-atalho-mesas:hover { background-color: var(--primary-light); }
        
        /* Ajuste coluna carrinho */
        .carrinho { display: flex; flex-direction: column; }
        #form-venda { display: flex; flex-direction: column; flex-grow: 1; min-height: 0; }
        .carrinho-itens-container { min-height: 100px; flex-grow: 1; overflow-y: auto; margin: 16px 0; scrollbar-width: thin; scrollbar-color: var(--primary) transparent; padding-right: 5px; }
        .total-container { flex-shrink: 0; }

    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
            <div class="header-start"> <a href="../index.php" class="btn-header-voltar">Voltar</a> </div>
            <span class="brand">PDV / VENDAS</span>
            <div class="header-actions">
                <button class="btn" id="btn-atalho-mesas" title="Ir para Mesas">Mesas</button>
                <?php if ($isSupervisor): ?>
                <div class="supervisor-menu">
                    <button id="btn-supervisor-menu" title="Menu Supervisor">⚙️</button> <div id="supervisor-menu-dropdown">
                        <button id="btn-fechamento-cego">Fechamento Cego</button>
                        <a href="fechar_caixa.php">Fechamento Detalhado</a>
                    </div>
                </div>
                <?php endif; ?>
                <button class="theme-toggle" id="theme-toggle" title="Alterar tema"><span class="icon-sun">☀️</span><span class="icon-moon">🌙</span></button>
            </div>
        </div>
    </header>
    
    <div class="container pdv-grid-container">
        <div class="produtos-container">
            <input type="search" id="pdv-pesquisa-produto" placeholder="Pesquisar produto...">
            <div class="produtos-grid" id="produtos-grid">
                <p>Carregando produtos...</p>
            </div>
            <div class="movimentacao-botoes">
                <button type="button" class="btn btn-success" id="btn-entrada">Entrada (+)</button>
                <button type="button" class="btn btn-danger" id="btn-saida">Saída (-)</button>
            </div>
        </div>
        
        <div class="carrinho">
            <h2>Registrar Venda</h2>
            <form id="form-venda">
                <label for="cliente-select">Cliente</label> <select name="cliente_id" id="cliente-select" required><option value="">Carregando...</option></select>
                <hr style="border-color: var(--border-dark); margin: 16px 0;">
                <div id="carrinho-itens-container" class="carrinho-itens-container"><p style="text-align: center; color: #aaa;">Adicione produtos</p></div>
                <div class="total-container">
                    <small>TOTAL</small>
                    <h2 id="valor-total"><?php echo $moeda; ?> 0,00</h2>
                    <button type="submit" class="btn btn-primary">Ir para Pagamento</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="toast"></div>
    
    <div class="modal-overlay" id="modal-pagamento">
        <div class="modal-content">
             <div class="modal-header"> <h3>Finalizar Pagamento</h3> <button class="modal-close" id="modal-close-btn">&times;</button> </div>
             <div class="modal-body">
                 <div class="pagamento-total"> <small>Total</small> <h1 id="modal-total-pagar"><?php echo $moeda; ?> 0,00</h1> </div>
                 <form id="form-pagamento">
                     <label for="forma-pagamento">Pagamento</label> <select id="forma-pagamento" name="forma_pagamento"> <option value="Dinheiro">Dinheiro</option> <option value="Cartão de Crédito">Cartão de Crédito</option> <option value="Cartão de Débito">Cartão de Débito</option> <option value="PIX">PIX</option> </select>
                     <div id="campo-valor-pago"> <label for="valor-pago">Valor Entregue</label> <input type="number" step="0.01" id="valor-pago" placeholder="Ex: 50,00"> </div>
                     <div class="troco-display" id="troco-display"></div>
                     <button type="submit" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Confirmar Venda</button>
                 </form>
             </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-movimentacao">
        <div class="modal-box" style="max-width: 450px;">
            <div class="modal-header"> <h3 id="modal-mov-titulo">Movimentação</h3> <button class="btn-fechar-modal" id="btn-fechar-mov">&times;</button> </div>
            <form id="form-movimentacao">
                <div class="modal-body">
                    <input type="hidden" id="mov-tipo" name="tipo" value="">
                    <div class="form-group" id="campo-tipo-saida"> <label for="mov-tipo-saida">Tipo Saída</label> <select id="mov-tipo-saida" name="motivo_saida_tipo"> <option value="Sangria">Sangria</option> <option value="Despesa">Despesa</option> <option value="Outro">Outro</option> </select> </div>
                    <div class="form-group"> <label for="mov-valor">Valor (<?php echo $moeda; ?>)</label> <input type="text" id="mov-valor" name="valor" placeholder="0,00" required inputmode="decimal"> </div>
                    <div class="form-group"> <label for="mov-motivo">Descrição/Motivo</label> <textarea id="mov-motivo" name="motivo" placeholder="Opcional"></textarea> </div>
                    <div id="erro-movimentacao" class="erro-mensagem" style="display: none;"></div>
                </div>
                <div class="modal-footer"> <button type="button" class="btn btn-secondary" id="btn-cancelar-mov">Cancelar</button> <button type="submit" class="btn" id="btn-confirmar-mov">Confirmar</button> </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modal-fechamento-cego">
        <div class="modal-box" style="max-width: 400px;">
            <div class="modal-header"> <h3>Fechamento Cego</h3> <button class="btn-fechar-modal" id="btn-fechar-cego">&times;</button> </div>
            <form id="form-fechamento-cego">
                <div class="modal-body">
                     <p style="text-align: center; color: var(--text-secondary); margin-bottom: 15px;">Informe o valor total contado em dinheiro.</p>
                     <div class="form-group"> <label for="cego-valor-contado">Total Contado (<?php echo $moeda; ?>)</label> <input type="text" id="cego-valor-contado" name="valor_contado_dinheiro" placeholder="0,00" required inputmode="decimal"> </div>
                     <div id="erro-fechamento-cego" class="erro-mensagem" style="display: none;"></div>
                </div>
                <div class="modal-footer"> <button type="button" class="btn btn-secondary" id="btn-cancelar-cego">Cancelar</button> <button type="submit" class="btn btn-danger" id="btn-confirmar-cego">Confirmar</button> </div>
            </form>
        </div>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- SELETORES ---
        const idSessaoAtiva = <?php echo json_encode($_SESSION['id_sessao_ativa']); ?>;
        const MOEDA_SIMBOLO = '<?php echo $moeda; ?>';
        const IS_SUPERVISOR = <?php echo $isSupervisor ? 'true' : 'false'; ?>;
        
        const formVenda = document.getElementById('form-venda');
        const clienteSelect = document.getElementById('cliente-select');
        const produtosGrid = document.getElementById('produtos-grid');
        const carrinhoItensDiv = document.getElementById('carrinho-itens-container');
        const valorTotalEl = document.getElementById('valor-total');
        const toast = document.getElementById('toast');
        
        const modalPagamento = document.getElementById('modal-pagamento');
        const modalCloseBtn = document.getElementById('modal-close-btn');
        const modalTotalPagar = document.getElementById('modal-total-pagar');
        const formPagamento = document.getElementById('form-pagamento');
        const formaPagamentoSelect = document.getElementById('forma-pagamento');
        const campoValorPago = document.getElementById('campo-valor-pago');
        const valorPagoInput = document.getElementById('valor-pago');
        const trocoDisplay = document.getElementById('troco-display');
        
        const pesquisaProdutoInput = document.getElementById('pdv-pesquisa-produto');
        const btnEntrada = document.getElementById('btn-entrada');
        const btnSaida = document.getElementById('btn-saida');
        
        const modalMov = document.getElementById('modal-movimentacao');
        const formMov = document.getElementById('form-movimentacao');
        const modalMovTitulo = document.getElementById('modal-mov-titulo');
        const movTipoInput = document.getElementById('mov-tipo');
        const campoTipoSaida = document.getElementById('campo-tipo-saida');
        const movTipoSaidaSelect = document.getElementById('mov-tipo-saida');
        const movValorInput = document.getElementById('mov-valor');
        const movMotivoInput = document.getElementById('mov-motivo');
        const erroMovDiv = document.getElementById('erro-movimentacao');
        const btnFecharMov = document.getElementById('btn-fechar-mov');
        const btnCancelarMov = document.getElementById('btn-cancelar-mov');
        const btnConfirmarMov = document.getElementById('btn-confirmar-mov');
        
        const btnAtalhoMesas = document.getElementById('btn-atalho-mesas');
        const btnSupervisorMenu = document.getElementById('btn-supervisor-menu');
        const supervisorMenuDropdown = document.getElementById('supervisor-menu-dropdown');
        const btnFechamentoCego = document.getElementById('btn-fechamento-cego');
        const modalCego = document.getElementById('modal-fechamento-cego');
        const formCego = document.getElementById('form-fechamento-cego');
        const erroCegoDiv = document.getElementById('erro-fechamento-cego');
        const btnFecharCego = document.getElementById('btn-fechar-cego');
        const btnCancelarCego = document.getElementById('btn-cancelar-cego');
        const btnConfirmarCego = document.getElementById('btn-confirmar-cego');
        const cegoValorInput = document.getElementById('cego-valor-contado');

        let todosOsProdutos = []; let carrinho = {}; let debounceTimer;

        // --- FUNÇÕES ---
        function showToast(mensagem, isErro = false) { if (!toast) return; toast.textContent = mensagem; toast.className = `show ${isErro ? 'erro' : 'sucesso'}`; toast.style.animation = 'none'; toast.offsetHeight; toast.style.animation = null; setTimeout(() => { toast.className = toast.className.replace('show', ''); }, 3000); }

        async function carregarDadosIniciais() { 
            try { 
                const response = await fetch('api_vendas.php?acao=carregar_dados'); 
                const res = await response.json(); 
                if (res.sucesso) { 
                    if (clienteSelect) { 
                        clienteSelect.innerHTML = '<option value="">Selecione...</option>'; 
                        res.dados.clientes.forEach(c => clienteSelect.add(new Option(c.nome, c.id))); 
                        
                        // Se houver cliente padrão (Consumidor Final), seleciona-o
                        // Nota: O ID 1 geralmente é o consumidor final
                        clienteSelect.value = '1'; 
                    } 
                    todosOsProdutos = res.dados.produtos; 
                    renderizarProdutos(todosOsProdutos); 
                    
                    // Tenta importar carrinho DEPOIS de carregar produtos
                    importarCarrinhoDaMesa();
                    
                } else { showToast(res.erro || "Falha.", true); } 
            } catch (error) { showToast("Erro conexão.", true); console.error("Erro:", error); } 
        }

        function renderizarProdutos(listaDeProdutos) { if (!produtosGrid) return; produtosGrid.innerHTML = ''; if (listaDeProdutos.length === 0) { produtosGrid.innerHTML = '<p style="color: var(--text-secondary); text-align: center;">Nenhum produto.</p>'; } else { listaDeProdutos.forEach(p => { const card = document.createElement('div'); card.className = 'produto-card'; card.dataset.produtoId = p.id; card.innerHTML = `<div class="produto-card-nome">${p.nome}</div><div class="produto-card-preco">${MOEDA_SIMBOLO} ${parseFloat(p.preco).toFixed(2).replace('.',',')}</div>`; card.addEventListener('click', () => adicionarAoCarrinho(p.id)); produtosGrid.appendChild(card); }); } }

        function adicionarAoCarrinho(produtoId) { 
            const produto = todosOsProdutos.find(p => p.id == produtoId); 
            if (!produto) return; 
            if (carrinho[produtoId]) { 
                carrinho[produtoId].quantidade++; 
            } else { 
                carrinho[produtoId] = { ...produto, quantidade: 1 }; 
            } 
            atualizarCarrinho(); 
        }

        function alterarQuantidade(produtoId, delta) { if (carrinho[produtoId]) { carrinho[produtoId].quantidade += delta; if (carrinho[produtoId].quantidade <= 0) { delete carrinho[produtoId]; } } atualizarCarrinho(); }

        function atualizarCarrinho() { 
            if (!carrinhoItensDiv || !valorTotalEl) return; 
            carrinhoItensDiv.innerHTML = ''; 
            let total = 0; 
            const itens = Object.values(carrinho); 
            if (itens.length === 0) { 
                carrinhoItensDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Adicione produtos</p>'; 
            } else { 
                itens.forEach(item => { 
                    const itemDiv = document.createElement('div'); 
                    itemDiv.className = 'carrinho-item'; 
                    itemDiv.innerHTML = `<span>${item.nome}</span><div class="carrinho-item-controles"><button type="button" data-id="${item.id}" class="btn-remove-qtd">-</button><strong>${item.quantidade}</strong><button type="button" data-id="${item.id}" class="btn-add-qtd">+</button></div>`; 
                    carrinhoItensDiv.appendChild(itemDiv); 
                    total += item.quantidade * item.preco; 
                }); 
            } 
            valorTotalEl.textContent = `${MOEDA_SIMBOLO} ${total.toFixed(2).replace('.',',')}`; 
            document.querySelectorAll('.btn-add-qtd').forEach(b => b.addEventListener('click', (e) => alterarQuantidade(e.target.dataset.id, 1))); 
            document.querySelectorAll('.btn-remove-qtd').forEach(b => b.addEventListener('click', (e) => alterarQuantidade(e.target.dataset.id, -1))); 
        }

        function limparVenda() { carrinho = {}; if (formVenda) formVenda.reset(); atualizarCarrinho(); if (pesquisaProdutoInput) pesquisaProdutoInput.value = ''; renderizarProdutos(todosOsProdutos); }

        // --- AQUI ESTÁ A CORREÇÃO: IMPORTAR CARRINHO DA MESA ---
        function importarCarrinhoDaMesa() {
            const dadosMesa = sessionStorage.getItem('carrinhoDaMesa');
            if (dadosMesa) {
                try {
                    const dados = JSON.parse(dadosMesa);
                    console.log("Importando mesa:", dados);
                    
                    // 1. Nome do Cliente (opcional: mostrar em toast ou label)
                    showToast(`Importado: ${dados.cliente_nome || 'Mesa'}`);
                    
                    // 2. Itens
                    if (dados.itens && Array.isArray(dados.itens)) {
                        dados.itens.forEach(item => {
                            // O item da mesa tem {produto_id, quantidade, preco}
                            // Precisamos converter para o formato do carrinho {id, quantidade, preco, nome}
                            
                            // Tenta achar o produto na lista carregada para ter o nome correto
                            const produtoCatalogo = todosOsProdutos.find(p => p.id == item.produto_id);
                            
                            const idProd = item.produto_id;
                            
                            if (carrinho[idProd]) {
                                carrinho[idProd].quantidade += parseInt(item.quantidade);
                            } else {
                                carrinho[idProd] = {
                                    id: idProd,
                                    nome: produtoCatalogo ? produtoCatalogo.nome : (item.nome || "Produto " + idProd),
                                    preco: parseFloat(item.preco),
                                    quantidade: parseInt(item.quantidade)
                                };
                            }
                        });
                        atualizarCarrinho();
                    }
                    
                    // 3. Limpa o storage
                    sessionStorage.removeItem('carrinhoDaMesa');
                    
                } catch (e) {
                    console.error("Erro import mesa:", e);
                }
            }
        }

        function abrirModalMov(tipoMov) { if (!modalMov || !formMov) return; formMov.reset(); erroMovDiv.style.display = 'none'; movTipoInput.value = tipoMov; if (tipoMov === 'ENTRADA') { modalMovTitulo.textContent = 'Registrar Entrada (Suprimento)'; btnConfirmarMov.textContent = 'Confirmar Entrada'; btnConfirmarMov.className = 'btn btn-success'; campoTipoSaida.style.display = 'none'; } else { modalMovTitulo.textContent = 'Registrar Saída (Sangria/Despesa)'; btnConfirmarMov.textContent = 'Confirmar Saída'; btnConfirmarMov.className = 'btn btn-danger'; campoTipoSaida.style.display = 'block'; movTipoSaidaSelect.value = 'Sangria'; } modalMov.style.display = 'flex'; movValorInput.focus(); }

        function fecharModalMov() { if (modalMov) modalMov.style.display = 'none'; }

        function abrirModalCego() { if (!modalCego || !formCego) return; formCego.reset(); erroCegoDiv.style.display = 'none'; modalCego.style.display = 'flex'; if (cegoValorInput) cegoValorInput.focus(); }

        function fecharModalCego() { if (modalCego) modalCego.style.display = 'none'; }

        // --- EVENT LISTENERS ---
        if (pesquisaProdutoInput) { pesquisaProdutoInput.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { const termo = pesquisaProdutoInput.value.toLowerCase(); const produtosFiltrados = termo === '' ? todosOsProdutos : todosOsProdutos.filter(p => p.nome.toLowerCase().includes(termo)); renderizarProdutos(produtosFiltrados); }, 300); }); }

        if (formVenda) { formVenda.addEventListener('submit', function(e) { e.preventDefault(); if (!clienteSelect || !clienteSelect.value) { showToast('Selecione cliente.', true); return; } if (Object.keys(carrinho).length === 0) { showToast('Adicione produtos.', true); return; } const valorTotal = Object.values(carrinho).reduce((acc, item) => acc + (item.quantidade * item.preco), 0); if (modalTotalPagar) modalTotalPagar.textContent = `${MOEDA_SIMBOLO} ${valorTotal.toFixed(2).replace('.',',')}`; if(formaPagamentoSelect) formaPagamentoSelect.value = 'Dinheiro'; if(campoValorPago) campoValorPago.style.display = 'block'; if(valorPagoInput) valorPagoInput.value = ''; if(trocoDisplay) trocoDisplay.textContent = ''; if (modalPagamento) modalPagamento.style.display = 'flex'; }); }

        if (formaPagamentoSelect) { formaPagamentoSelect.addEventListener('change', function() { if(trocoDisplay) trocoDisplay.textContent = ''; if(valorPagoInput) valorPagoInput.value = ''; if(campoValorPago) campoValorPago.style.display = this.value === 'Dinheiro' ? 'block' : 'none'; }); }

        if (valorPagoInput && modalTotalPagar) { valorPagoInput.addEventListener('input', function() { const totalText = modalTotalPagar.textContent.replace(MOEDA_SIMBOLO+' ', '').replace(',', '.'); const total = parseFloat(totalText) || 0; const pago = parseFloat(this.value) || 0; const troco = pago - total; if(trocoDisplay) trocoDisplay.textContent = troco >= 0 ? `Troco: ${MOEDA_SIMBOLO} ${troco.toFixed(2).replace('.',',')}` : ''; }); }

        if (modalCloseBtn && modalPagamento) { modalCloseBtn.addEventListener('click', () => modalPagamento.style.display = 'none'); }

        if (formPagamento) { formPagamento.addEventListener('submit', async function(e) { e.preventDefault(); const vendaParaSalvar = { cliente_id: clienteSelect.value, itens: Object.values(carrinho).map(item => ({ id: item.id, quantidade: item.quantidade, preco: item.preco })), valor_total: Object.values(carrinho).reduce((acc, item) => acc + (item.quantidade * item.preco), 0), forma_pagamento: formaPagamentoSelect.value, id_sessao: idSessaoAtiva }; try { const response = await fetch('api_vendas.php?acao=registrar_venda', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(vendaParaSalvar) }); const resultado = await response.json(); if (resultado.sucesso) { showToast(resultado.mensagem || "Venda OK!"); limparVenda(); if(modalPagamento) modalPagamento.style.display = 'none'; if (resultado.pedido_id) window.open(`recibo.php?id=${resultado.pedido_id}`, 'Recibo', 'width=320,height=500,scrollbars=yes'); } else { showToast(resultado.erro || "Erro.", true); } } catch (error) { showToast("Erro conexão.", true); console.error("Erro:", error); } }); }

        if (btnEntrada) btnEntrada.addEventListener('click', () => abrirModalMov('ENTRADA'));
        if (btnSaida) btnSaida.addEventListener('click', () => abrirModalMov('SAIDA'));
        if (btnFecharMov) btnFecharMov.addEventListener('click', fecharModalMov);
        if (btnCancelarMov) btnCancelarMov.addEventListener('click', fecharModalMov);
        if (modalMov) modalMov.addEventListener('click', (e) => { if (e.target === modalMov) fecharModalMov(); });
        if (formMov) { formMov.addEventListener('submit', async function(e) { e.preventDefault(); const tipoPrincipal = movTipoInput.value; const valorStr = movValorInput.value.replace(MOEDA_SIMBOLO, '').trim().replace(',', '.'); const valor = parseFloat(valorStr); let motivoTexto = movMotivoInput.value.trim(); let tipoSaidaDetalhe = ''; if (isNaN(valor) || valor <= 0) { erroMovDiv.textContent = 'Valor inválido.'; erroMovDiv.style.display = 'block'; return; } erroMovDiv.style.display = 'none'; const formData = new FormData(); formData.append('tipo', tipoPrincipal); formData.append('valor', valor); if (tipoPrincipal === 'SAIDA') { tipoSaidaDetalhe = movTipoSaidaSelect.value; motivoTexto = `[${tipoSaidaDetalhe}] ${motivoTexto}`.trim(); } formData.append('motivo', motivoTexto); btnConfirmarMov.disabled = true; btnConfirmarMov.textContent = 'Aguarde...'; try { const response = await fetch('api_caixa.php?acao=registrar_movimentacao', { method: 'POST', body: formData }); const res = await response.json(); if (res.sucesso) { showToast(res.mensagem || "OK!"); fecharModalMov(); } else { erroMovDiv.textContent = res.erro || 'Erro.'; erroMovDiv.style.display = 'block'; } } catch (error) { erroMovDiv.textContent = 'Erro conexão.'; erroMovDiv.style.display = 'block'; console.error("Erro:", error); } finally { btnConfirmarMov.disabled = false; btnConfirmarMov.textContent = tipoPrincipal === 'ENTRADA' ? 'Confirmar Entrada' : 'Confirmar Saída'; } }); }

        if (btnAtalhoMesas) btnAtalhoMesas.addEventListener('click', () => { window.location.href = 'mesas.php'; });
        if (btnSupervisorMenu && supervisorMenuDropdown) { btnSupervisorMenu.addEventListener('click', (e) => { e.stopPropagation(); supervisorMenuDropdown.style.display = supervisorMenuDropdown.style.display === 'block' ? 'none' : 'block'; }); window.addEventListener('click', () => { if (supervisorMenuDropdown.style.display === 'block') { supervisorMenuDropdown.style.display = 'none'; } }); }
        if (btnFechamentoCego) btnFechamentoCego.addEventListener('click', (e) => { e.preventDefault(); abrirModalCego(); if (supervisorMenuDropdown) supervisorMenuDropdown.style.display = 'none'; });
        if (btnFecharCego) btnFecharCego.addEventListener('click', fecharModalCego);
        if (btnCancelarCego) btnCancelarCego.addEventListener('click', fecharModalCego);
        if (modalCego) modalCego.addEventListener('click', (e) => { if (e.target === modalCego) fecharModalCego(); });
        if (formCego) { formCego.addEventListener('submit', async function(e) { e.preventDefault(); const valorContadoStr = cegoValorInput.value.replace(MOEDA_SIMBOLO, '').trim().replace(',', '.'); const valorContado = parseFloat(valorContadoStr); if (isNaN(valorContado) || valorContado < 0) { erroCegoDiv.textContent = 'Valor inválido.'; erroCegoDiv.style.display = 'block'; return; } erroCegoDiv.style.display = 'none'; btnConfirmarCego.disabled = true; btnConfirmarCego.textContent = 'Fechando...'; showToast('FUNCIONALIDADE BACKEND NÃO IMPLEMENTADA!', true); btnConfirmarCego.disabled = false; btnConfirmarCego.textContent = 'Confirmar'; }); }

        // --- INIT ---
        carregarDadosIniciais(); // Isto agora vai carregar produtos E DEPOIS importar a mesa
    });
    </script>
    <script src="../assets/js/theme.js" defer></script>
</body>
</html>
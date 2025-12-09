<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Clientes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="header-inner header-inner--standard">
            <div class="header-start">
                <a href="../index.php" class="btn-header-voltar">Voltar</a>
            </div>
            <span class="brand">GESTÃO DE CLIENTES</span>
            <div class="header-actions">
                <button class="btn btn-primary" id="btn-novo-cliente">+ Novo Cliente</button>
                <button class="theme-toggle" id="theme-toggle" title="Alterar tema">
                    <span class="icon-sun">☀️</span><span class="icon-moon">🌙</span>
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="gestao-container">
            
            <div class="lista-clientes">
                <input type="search" id="input-pesquisa" placeholder="Pesquisar cliente..." autocomplete="off">
                <ul id="lista-resultados">
                    </ul>
            </div>

            <div class="form-cliente-wrapper">
                
                <div id="form-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                    </svg>
                    <h3>Selecione um cliente para ver os detalhes</h3>
                    <p>Ou clique em "Novo Cliente" para começar um novo registo.</p>
                </div>

                <div id="form-container" style="display: none;">
                    <h2 id="form-titulo">Novo Cliente</h2>
                    
                    <form id="form-cliente">
                        <div class="form-body">
                            <input type="hidden" name="id" id="cliente-id">
                            
                            <div class="form-grid">
                                <div class="full-width">
                                    <label for="nome">Nome Completo*</label>
                                    <input type="text" id="nome" name="nome" required>
                                </div>
                                
                                <div>
                                    <label for="cpf">CPF</label>
                                    <input type="text" id="cpf" name="cpf">
                                </div>
                                <div>
                                    <label for="telefone">Telefone</label>
                                    <input type="text" id="telefone" name="telefone">
                                </div>

                                <div class="full-width">
                                    <label for="email">E-mail</label>
                                    <input type="email" id="email" name="email">
                                </div>

                                <div class="full-width">
                                    <hr style="border-color: var(--border-dark); margin: 10px 0;">
                                    <label style="color: var(--primary);">Endereço</label>
                                </div>

                                <div>
                                    <label for="cep">CEP</label>
                                    <input type="text" id="cep" name="cep">
                                </div>
                                <div>
                                    <label for="numero">Número</label>
                                    <input type="text" id="numero" name="numero">
                                </div>
                                
                                <div class="full-width">
                                    <label for="logradouro">Logradouro</label>
                                    <input type="text" id="logradouro" name="logradouro">
                                </div>
                                
                                <div>
                                    <label for="bairro">Bairro</label>
                                    <input type="text" id="bairro" name="bairro">
                                </div>
                                <div>
                                    <label for="cidade">Cidade</label>
                                    <input type="text" id="cidade" name="cidade">
                                </div>
                                <div>
                                    <label for="uf">UF</label>
                                    <input type="text" id="uf" name="uf" maxlength="2" style="text-transform: uppercase;">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-danger" id="btn-excluir" style="display: none;">Excluir</button>
                            <button type="button" class="btn btn-secondary" id="btn-cancelar">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar Cliente</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    
    <div id="toast"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Elementos
        const listaResultados = document.getElementById('lista-resultados');
        const inputPesquisa = document.getElementById('input-pesquisa');
        const btnNovo = document.getElementById('btn-novo-cliente');
        const formContainer = document.getElementById('form-container');
        const formPlaceholder = document.getElementById('form-placeholder');
        const form = document.getElementById('form-cliente');
        const btnCancelar = document.getElementById('btn-cancelar');
        const btnExcluir = document.getElementById('btn-excluir');
        const formTitulo = document.getElementById('form-titulo');
        const toast = document.getElementById('toast');

        let debounceTimer;

        // 1. Carregar Clientes
        async function carregarClientes(termo = '') {
            try {
                const res = await fetch(`api_clientes.php?acao=listar&termo=${encodeURIComponent(termo)}`);
                const json = await res.json();
                
                listaResultados.innerHTML = '';
                
                if (json.sucesso && json.dados.length > 0) {
                    json.dados.forEach(c => {
                        const li = document.createElement('li');
                        li.className = 'cliente-item';
                        li.innerHTML = `
                            <span class="cliente-item-nome">${c.nome}</span>
                            <small class="cliente-item-detalhes">${c.telefone || 'Sem telefone'} | ${c.email || 'Sem email'}</small>
                        `;
                        li.addEventListener('click', () => carregarDetalhes(c.id));
                        listaResultados.appendChild(li);
                    });
                } else {
                    listaResultados.innerHTML = '<li style="text-align:center; color:#aaa; padding:20px;">Nenhum cliente encontrado.</li>';
                }
            } catch (error) {
                showToast('Erro ao carregar clientes.', true);
                console.error(error);
            }
        }

        // 2. Carregar Detalhes
        async function carregarDetalhes(id) {
            try {
                const res = await fetch(`api_clientes.php?acao=detalhes&id=${id}`);
                const json = await res.json();
                
                if (json.sucesso) {
                    const c = json.dados;
                    form.id.value = c.id;
                    form.nome.value = c.nome;
                    form.cpf.value = c.cpf;
                    form.email.value = c.email;
                    form.telefone.value = c.telefone;
                    form.cep.value = c.cep;
                    form.logradouro.value = c.logradouro;
                    form.numero.value = c.numero;
                    form.bairro.value = c.bairro;
                    form.cidade.value = c.cidade;
                    form.uf.value = c.uf;

                    mostrarFormulario(true); // True = Edição
                }
            } catch (error) {
                showToast('Erro ao carregar detalhes.', true);
            }
        }

        // 3. Salvar
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append('acao', 'salvar');

            try {
                const res = await fetch('api_clientes.php', { method: 'POST', body: formData });
                const json = await res.json();

                if (json.sucesso) {
                    showToast('Cliente salvo com sucesso!');
                    carregarClientes(inputPesquisa.value);
                    esconderFormulario();
                } else {
                    showToast(json.erro || 'Erro ao salvar.', true);
                }
            } catch (error) {
                showToast('Erro de conexão.', true);
            }
        });

        // 4. Excluir
        btnExcluir.addEventListener('click', async () => {
            if(confirm('Tem a certeza que deseja excluir este cliente?')) {
                const formData = new FormData();
                formData.append('acao', 'excluir');
                formData.append('id', form.id.value);

                try {
                    const res = await fetch('api_clientes.php', { method: 'POST', body: formData });
                    const json = await res.json();

                    if (json.sucesso) {
                        showToast('Cliente excluído.');
                        carregarClientes();
                        esconderFormulario();
                    } else {
                        showToast(json.erro, true);
                    }
                } catch (error) {
                    showToast('Erro ao excluir.', true);
                }
            }
        });

        // --- Helpers de UI ---
        function mostrarFormulario(isEdicao) {
            formPlaceholder.style.display = 'none';
            formContainer.style.display = 'flex';
            
            if (isEdicao) {
                formTitulo.textContent = 'Editar Cliente';
                btnExcluir.style.display = 'inline-flex';
            } else {
                formTitulo.textContent = 'Novo Cliente';
                form.reset();
                form.id.value = '';
                btnExcluir.style.display = 'none';
            }
        }

        function esconderFormulario() {
            formContainer.style.display = 'none';
            formPlaceholder.style.display = 'block';
            form.reset();
        }

        // Listeners
        inputPesquisa.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => carregarClientes(inputPesquisa.value), 300);
        });

        btnNovo.addEventListener('click', () => mostrarFormulario(false));
        btnCancelar.addEventListener('click', esconderFormulario);

        function showToast(msg, erro = false) {
            if(toast) { 
                toast.textContent = msg; 
                toast.className = erro ? 'show erro' : 'show sucesso'; 
                setTimeout(() => toast.className = '', 3000); 
            }
        }

        // Inicialização
        carregarClientes();
    });
    </script>
    <script src="../assets/js/theme.js" defer></script>
</body>
</html>
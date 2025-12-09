document.addEventListener('DOMContentLoaded', () => {
    const themeToggleButton = document.getElementById('theme-toggle');
    const body = document.body;

    // Função para aplicar o tema (claro ou escuro)
    const applyTheme = (theme) => {
        if (theme === 'light') {
            body.classList.add('light');
        } else {
            body.classList.remove('light');
        }
    };

    // Verifica se já existe um tema guardado no armazenamento local
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    }

    // Adiciona o evento de clique ao botão
    themeToggleButton.addEventListener('click', () => {
        // Verifica se o corpo JÁ TEM a classe 'light'
        const isLight = body.classList.contains('light');
        
        // Se estiver claro, muda para escuro. Se estiver escuro, muda para claro.
        if (isLight) {
            applyTheme('dark');
            localStorage.setItem('theme', 'dark'); // Guarda a preferência
        } else {
            applyTheme('light');
            localStorage.setItem('theme', 'light'); // Guarda a preferência
        }
    });
});
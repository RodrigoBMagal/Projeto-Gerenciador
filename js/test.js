const tabela = [];

const funcionarios = [
    {
        nome: "Joao",
        cargo: "Admnistrador",
        idade: "66"
    },
    {
        nome: "Marcos",
        cargo: "Operador",
        idade: "21"
    }
]

// Quando a tela carrega
tabela = [... funcionarios];




// Clicou no botão de buscar
busca = "Admin"

tabela = [];

funcionarios.map(funcionario => {

    if (funcionario.nome.includes(busca) || funcionario.cargo.includes(busca) || funcionario.idade.includes(busca)) {
        tabela.push(funcionario)
    }

})
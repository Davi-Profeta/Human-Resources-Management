// ---------- Funções de apoio ----------

// Lê uma cor/variável direto do dashboard.css, assim a paleta fica num lugar só
function pegarCor(nomeVariavel) {
  return getComputedStyle(document.documentElement)
    .getPropertyValue(nomeVariavel)
    .trim();
}

// Transforma 22750 em "R$ 22.750,00"
function formatarReal(valor) {
  return valor.toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
  });
}

// Quem pediu "menos movimento" no sistema não vê animação
function pegarDuracaoAnimacao() {
  const reduzir = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  return reduzir ? 0 : 600;
}

// Configurações que valem para todos os gráficos
function configurarPadroes() {
  Chart.defaults.font.family = pegarCor("--fonte-base");
  Chart.defaults.font.size = 12;
  Chart.defaults.color = "#94a3b8";
  Chart.defaults.borderColor = "rgba(255, 255, 255, 0.08)";
  Chart.defaults.animation.duration = pegarDuracaoAnimacao();
}

configurarPadroes();

// ---------- Gráfico 1: Gastos Gerais (barras) ----------

function criarGraficoGastos(meses, valores) {
  const canvas = document.getElementById("graficoGastos");

  new Chart(canvas, {
    type: "bar",
    data: {
      labels: meses,
      datasets: [
        {
          label: "Gastos",
          data: valores,
          backgroundColor: pegarCor("--cor-primaria"),
          hoverBackgroundColor: pegarCor("--cor-destaque"),
          borderRadius: 6,
          maxBarThickness: 44,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false, // o gráfico acompanha a altura do .grafico
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (item) {
              return formatarReal(item.parsed.y);
            },
          },
        },
      },
      scales: {
        x: { grid: { display: false } },
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (valor) {
              return "R$ " + valor / 1000 + " mil";
            },
          },
        },
      },
    },
  });
}

// ---------- Gráfico 2: Controle de Ponto e Frequência (rosca) ----------

function criarGraficoPonto(situacao) {
  const canvas = document.getElementById("graficoPonto");

  new Chart(canvas, {
    type: "doughnut",
    data: {
      labels: ["Presentes", "Atrasos", "Faltas"],
      datasets: [
        {
          data: situacao,
          backgroundColor: [
            pegarCor("--cor-sucesso"),
            pegarCor("--cor-alerta"),
            pegarCor("--cor-erro"),
          ],
          borderColor: pegarCor("--cor-card"),
          borderWidth: 3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "68%",
      plugins: {
        legend: {
          position: "right",
          labels: { usePointStyle: true, padding: 16 },
        },
      },
    },
  });
}

/* ========================== Valores dashboard =================== */
async function carregarValoresDashboard() {

    try {
        const { data } = await axios.get("../../src/APIs/dashboardApi.php"); 

        let custoColaborador = 0;
        let totalGeral = 0;

        data.forEach(element => {

            Object.entries(element).forEach(([chave, valor]) => {

                console.log(`${chave}: ${valor}`);

            });

            //console.log(element);
            //custoColaborador += Number(element.custo_total_colaborador);

        });

        console.log("Total Geral:", totalGeral);

    } catch (error) {
        console.error("Erro na requisição:", error);
    }

};

carregarValoresDashboard();
// ---------- Configurações gerais ----------

function pegarCor(nomeVariavel) {
  return getComputedStyle(document.documentElement)
    .getPropertyValue(nomeVariavel)
    .trim();
}

function formatarReal(valor) {
  return Number(valor).toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
  });
}

function configurarPadroes() {
  Chart.defaults.font.family = pegarCor("--fonte-base");
  Chart.defaults.font.size = 12;
  Chart.defaults.color = "#94a3b8";
  Chart.defaults.borderColor = "rgba(255, 255, 255, 0.08)";
  Chart.defaults.animation.duration = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches
    ? 0
    : 600;
}

configurarPadroes();

// ---------- Gastos Gerais: pizza de salários e benefícios ----------

function converterValor(valor) {
  if (typeof valor === "number") {
    return Number.isFinite(valor) ? valor : 0;
  }

  if (valor === null || valor === undefined || valor === "") return 0;

  const texto = String(valor)
    .replace(/R\$/gi, "")
    .replace(/\s/g, "");

  // Converte tanto "1621.00" quanto "1.621,00".
  const normalizado = texto.includes(",")
    ? texto.replace(/\./g, "").replace(",", ".")
    : texto;

  const numero = Number(normalizado);
  return Number.isFinite(numero) ? numero : 0;
}

function normalizarNomeCampo(nome) {
  return nome
    .toLocaleLowerCase("pt-BR")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9]/g, "");
}

function obterValorDaLinha(linha, nomesPossiveis, indiceNumerico) {
  if (!linha || typeof linha !== "object") return 0;

  const nomesNormalizados = nomesPossiveis.map(normalizarNomeCampo);
  const chave = Object.keys(linha).find((nome) =>
    nomesNormalizados.includes(normalizarNomeCampo(nome))
  );

  // O endpoint atual retorna mysqli_fetch_array, com as colunas também numeradas.
  const valor = chave !== undefined ? linha[chave] : linha[String(indiceNumerico)];
  return converterValor(valor);
}

function obterColaboradores(resposta) {
  if (Array.isArray(resposta)) return resposta;
  if (Array.isArray(resposta?.dados)) return resposta.dados;
  if (Array.isArray(resposta?.data)) return resposta.data;
  if (Array.isArray(resposta?.colaboradores)) return resposta.colaboradores;
  if (Array.isArray(resposta?.colaboradoresPorEmpresa))
    return resposta.colaboradoresPorEmpresa;
  if (Array.isArray(resposta?.colaboradores_por_empresa))
    return resposta.colaboradores_por_empresa;
  return [];
}

function somarGastos(resposta) {
  return obterColaboradores(resposta).reduce(
    (totais, colaborador) => {
      // Pela resposta mostrada no console: índice 2 = salário e índice 3 = benefício.
      totais.salarios += obterValorDaLinha(
        colaborador,
        [
          "salario",
          "salario_base",
          "salario_mensal",
          "custo_salario",
          "custo_total_colaborador",
        ],
        2
      );

      totais.beneficios += obterValorDaLinha(
        colaborador,
        [
          "beneficio",
          "beneficios",
          "valor_beneficios",
          "total_beneficios",
          "total_beneficios_colaborador",
          "custo_beneficios",
        ],
        3
      );

      return totais;
    },
    { salarios: 0, beneficios: 0 }
  );
}

function normalizarTextoMenu(texto) {
  return String(texto || "")
    .toLocaleLowerCase("pt-BR")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");
}

function encontrarLinkDoMenu(termos) {
  const documentos = [document];

  // O dashboard pode estar dentro de um iframe, então também procura no menu da página pai.
  try {
    if (window.parent !== window && window.parent.document !== document) {
      documentos.push(window.parent.document);
    }
  } catch (erro) {
    // Se o iframe for de outra origem, continua procurando somente nesta página.
  }

  for (const pagina of documentos) {
    const links = Array.from(pagina.querySelectorAll("a[href]"));

    const linkEncontrado = links.find((link) => {
      const informacoes = [
        link.innerText,
        link.getAttribute("aria-label"),
        link.getAttribute("title"),
        link.getAttribute("href"),
      ]
        .join(" ")
        .toLocaleLowerCase("pt-BR");

      const informacoesNormalizadas = normalizarTextoMenu(informacoes);
      return termos.some((termo) => informacoesNormalizadas.includes(termo));
    });

    if (linkEncontrado) return linkEncontrado;
  }

  return null;
}

function abrirAbaDoGasto(indice) {
  const rotas = [
    {
      termos: ["vinculo", "salario", "folha"],
      fallback: "vinculos.php",
    },
    {
      termos: ["beneficio"],
      fallback: "beneficios.php",
    },
  ];

  const categoria = rotas[indice];
  if (!categoria) return;

  const linkDoMenu = encontrarLinkDoMenu(categoria.termos);

  if (linkDoMenu) {
    linkDoMenu.click();
    return;
  }

  // Caminhos padrão caso o menu não tenha links identificáveis.
  window.location.assign(categoria.fallback);
}

function criarGraficoGastos(totais) {
  const canvas = document.getElementById("graficoGastos");
  if (!canvas) {
    console.error('Canvas "graficoGastos" não foi encontrado no dashboard.php.');
    return;
  }

  new Chart(canvas, {
    type: "pie",
    data: {
      labels: ["Salários", "Benefícios"],
      datasets: [
        {
          data: [totais.salarios, totais.beneficios],
          backgroundColor: [
            pegarCor("--cor-primaria"),
            pegarCor("--cor-destaque"),
          ],
          borderColor: pegarCor("--cor-card"),
          borderWidth: 3,
          hoverOffset: 8,
        },
      ],
    },
    plugins: [
      {
        id: "totalGeralAbaixoDaLegenda",
        afterDraw: function (grafico) {
          const legenda = grafico.legend;
          if (!legenda) return;

          const valores = grafico.data.datasets[0].data;
          const total = valores.reduce(
            (soma, valor) => soma + Number(valor || 0),
            0
          );
          const contexto = grafico.ctx;
          const x = legenda.left;
          const y = legenda.top + legenda.height + 24;

          contexto.save();
          contexto.textAlign = "left";
          contexto.textBaseline = "middle";
          contexto.fillStyle = "#94a3b8";
          contexto.font = `600 12px ${Chart.defaults.font.family}`;
          contexto.fillText("Total geral", x, y);

          contexto.fillStyle = "#f8fafc";
          contexto.font = `600 14px ${Chart.defaults.font.family}`;
          contexto.fillText(formatarReal(total), x, y + 22);
          contexto.restore();
        },
      },
    ],
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "right",
          labels: {
            usePointStyle: true,
            padding: 18,
          },
          onClick: function (evento, itemLegenda) {
            abrirAbaDoGasto(itemLegenda.index);
          },
        },
        tooltip: {
          callbacks: {
            label: function (contexto) {
              const valores = contexto.dataset.data;
              const total = valores.reduce((soma, valor) => soma + valor, 0);
              const valor = contexto.parsed;
              const percentual =
                total > 0 ? ((valor / total) * 100).toFixed(1) : "0,0";

              return `${contexto.label}: ${formatarReal(valor)} (${percentual}%)`;
            },
          },
        },
      },
      onClick: function (evento, elementos) {
        if (elementos.length > 0) {
          abrirAbaDoGasto(elementos[0].index);
        }
      },
      onHover: function (evento, elementos) {
        evento.native.target.style.cursor =
          elementos.length > 0 ? "pointer" : "default";
      },
    },
  });
}

function agruparColaboradoresPorEmpresa(resposta) {
  const nomesEmpresa = [
    "nome_empresas",
    "nome_empresa",
    "empresa_nome",
    "nome_da_empresa",
    "empresa",
    "razao_social",
  ].map(normalizarNomeCampo);

  const camposQuantidade = [
    "quantidade_colaboradores",
    "total_colaboradores",
    "qtd_colaboradores",
    "numero_colaboradores",
  ].map(normalizarNomeCampo);

  const totais = new Map();

  obterColaboradores(resposta).forEach((colaborador) => {
    if (!colaborador || typeof colaborador !== "object") return;

    const chaveEmpresa = Object.keys(colaborador).find((chave) =>
      nomesEmpresa.includes(normalizarNomeCampo(chave))
    );

    if (!chaveEmpresa) return;

    const empresa = String(colaborador[chaveEmpresa] ?? "").trim();
    if (!empresa) return;

    const chaveQuantidade = Object.keys(colaborador).find((chave) =>
      camposQuantidade.includes(normalizarNomeCampo(chave))
    );

    // Se a API retorna uma linha por colaborador, conta 1. Se já retorna
    // o total agrupado, usa a quantidade informada pela própria API.
    const quantidade = chaveQuantidade
      ? Number(colaborador[chaveQuantidade]) || 0
      : 1;

    totais.set(empresa, (totais.get(empresa) || 0) + quantidade);
  });

  return Array.from(totais, ([empresa, quantidade]) => ({
    empresa,
    quantidade,
  })).sort((a, b) => b.quantidade - a.quantidade);
}

function criarGraficoColaboradoresEmpresa(dados) {
  const canvas = document.getElementById("graficoColaboradoresEmpresa");
  const aviso = document.getElementById("avisoGraficoEmpresa");
  if (!canvas) return;

  if (dados.length === 0) {
    canvas.hidden = true;
    if (aviso) aviso.hidden = false;
    console.warn(
      "A API não retornou o nome da empresa. Inclua o campo nome_empresas (ou equivalente) na resposta.",
      dados
    );
    return;
  }

  canvas.hidden = false;
  if (aviso) aviso.hidden = true;

  const cores = [
    pegarCor("--cor-primaria"),
    pegarCor("--cor-destaque"),
    pegarCor("--cor-sucesso"),
    pegarCor("--cor-alerta"),
    "#8b5cf6",
    "#ec4899",
  ];

  new Chart(canvas, {
    type: "bar",
    data: {
      labels: dados.map((item) => item.empresa),
      datasets: [
        {
          label: "Colaboradores",
          data: dados.map((item) => item.quantidade),
          backgroundColor: dados.map(
            (_, indice) => cores[indice % cores.length]
          ),
          borderRadius: 6,
          maxBarThickness: 52,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: "x",
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (contexto) {
              const quantidade = contexto.parsed.y;
              return `${quantidade} ${
                quantidade === 1 ? "colaborador" : "colaboradores"
              }`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { autoSkip: false },
        },
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            stepSize: 1,
          },
        },
      },
    },
  });
}

async function carregarDadosDashboard() {
  try {
    const resposta = await axios.get("../../src/APIs/dashboardApi.php");
    const dadosApi = resposta.data;
    const colaboradores = obterColaboradores(dadosApi);
    const totaisGastos = somarGastos(colaboradores);
    const indicadores = dadosApi?.indicadores ?? {};
    const custoMensal =
      indicadores.custo_mensal_total ??
      totaisGastos.salarios + totaisGastos.beneficios;

    const cardColaboradores = document.getElementById(
      "kpiColaboradoresAtivos"
    );
    const cardEmpresas = document.getElementById("kpiEmpresasCadastradas");
    const cardCusto = document.getElementById("kpiCustoMensal");

    if (cardColaboradores) {
      const quantidade = Number(indicadores.colaboradores_ativos);
      cardColaboradores.textContent = Number.isFinite(quantidade)
        ? quantidade.toLocaleString("pt-BR")
        : "—";
    }

    if (cardEmpresas) {
      const quantidade = Number(indicadores.empresas_cadastradas);
      cardEmpresas.textContent = Number.isFinite(quantidade)
        ? quantidade.toLocaleString("pt-BR")
        : "—";
    }

    if (cardCusto) {
      cardCusto.textContent = formatarReal(custoMensal);
    }

    console.log("Total de salários:", formatarReal(totaisGastos.salarios));
    console.log("Total de benefícios:", formatarReal(totaisGastos.beneficios));

    if (totaisGastos.salarios === 0 && totaisGastos.beneficios === 0) {
      console.warn(
        "A API retornou colaboradores, mas os índices 2 (salário) e 3 (benefícios) estão zerados ou não foram encontrados.",
        dadosApi
      );
    }

    criarGraficoGastos(totaisGastos);
    criarGraficoColaboradoresEmpresa(
      agruparColaboradoresPorEmpresa(colaboradores)
    );
  } catch (erro) {
    console.error("Erro ao carregar os dados do dashboard:", erro);

    const cardColaboradores = document.getElementById(
      "kpiColaboradoresAtivos"
    );
    const cardEmpresas = document.getElementById("kpiEmpresasCadastradas");
    const cardCusto = document.getElementById("kpiCustoMensal");

    if (cardColaboradores) cardColaboradores.textContent = "—";
    if (cardEmpresas) cardEmpresas.textContent = "—";
    if (cardCusto) cardCusto.textContent = "—";

    criarGraficoGastos({ salarios: 0, beneficios: 0 });
    criarGraficoColaboradoresEmpresa([]);
  }
}

carregarDadosDashboard();

// ---------- Controle de Ponto e Frequência (mantido para uso posterior) ----------

function criarGraficoPonto(situacao) {
  const canvas = document.getElementById("graficoPonto");
  if (!canvas) return;

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
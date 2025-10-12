=== Horário de Ônibus Itapetininga (Derivado) ===
Contributors: alphamontanari
Tags: ônibus, transporte público, itapetininga, horários, mobilidade
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Fork com tema/JS próprios em rota alternativa (/horario-de-onibus-itapetininga) consumindo as linhas do plugin original.

== Description ==
Este plugin oferece uma interface alternativa (tema/JS próprios) para consulta dos horários das linhas de ônibus municipais de Itapetininga, **reutilizando os dados (linhas/*.js) do plugin original**.

**Como funciona a dependência de dados:**
- Se o **plugin original** “Horário Ônibus Itapetininga” estiver **ativo**, as linhas são servidas pela rota limpa do original: `/horario-onibus-itapetininga/linhas/...`
- Se o original **não estiver ativo**, o fork faz **fallback** para os arquivos estáticos em:
  `/wp-content/plugins/horario-onibus-itapetininga/assets/linhas/*.js`

> Recomendado: manter o plugin original ativo para melhor controle de cache e headers.

**Principais características do fork:**
- Rota própria: `https://SEUSITE/horario-de-onibus-itapetininga`
- Tema/CSS e JS independentes do original (você pode personalizar cores, layout e UX).
- Consome as constantes `LinhaXX` (mesma estrutura do original), sem duplicar dados.
- Navegação fluída, sem refresh, com possibilidade de compartilhar URLs de estados internos.

**Sobre o plugin original (provedor dos dados):**
- Desenvolvido por André Luiz Montanari para visualização de horários.
- Base de dados estruturada em `assets/linhas/*.js` (constantes do tipo `Linha__`).
- Repositório original: https://github.com/alphamontanari/horario-onibus-itapetininga

== Installation ==
1. Baixe o ZIP deste repositório (fork) e envie para `Plugins > Adicionar novo > Enviar plugin`.
2. Ative **Horário de Ônibus Itapetininga (Derivado)**.
3. (Recomendado) Ative também o **plugin original** “Horário Ônibus Itapetininga”.
4. Acesse: `/horario-de-onibus-itapetininga`.

**Observações importantes**
- Com o original **ativo**, o fork consome as linhas via **rota limpa do original**.
- Sem o original, o fork buscará as linhas por **URL estática** em `/wp-content/plugins/horario-onibus-itapetininga/assets/linhas/`.
- Caso a pasta do plugin original tenha sido renomeada, ajuste esse caminho no código do fork.

== Frequently Asked Questions ==
= O fork funciona sem o plugin original ativo? =
Sim, via fallback para os arquivos estáticos no diretório do plugin original. Porém, é **recomendado** manter o original ativo para usar a rota limpa e aproveitar headers/cache configurados por ele.

= As linhas precisam ser duplicadas no fork? =
Não. O fork **não** duplica `linhas/*.js`. Ele apenas **consome** os arquivos do plugin original.

= Os slugs entram em conflito? =
Não. O original usa `/horario-onibus-itapetininga` e o fork usa `/horario-de-onibus-itapetininga`.

= Posso personalizar cores e layout no fork? =
Sim. Edite `assets/style.css` e `assets/main.js` do fork à vontade.

== Screenshots ==
1. Tela inicial do fork com tema próprio.
2. Lista de linhas carregadas a partir do plugin original.
3. Exemplo de detalhamento/itinerário com UI alternativa.

== Changelog ==
= 0.1.0 =
* Primeira versão do fork.
* Consumo das linhas do plugin original (rota limpa se ativo, fallback estático se inativo).
* Rota própria e assets independentes para tema/JS.

== Upgrade Notice ==
= 0.1.0 =
Versão inicial do fork. Recomenda-se manter o plugin original ativo para melhor performance no fornecimento das linhas.

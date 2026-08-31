# Raciocínio: Indicador de Momento Atual na Agenda (FullCalendar)

## Contexto & Objetivo
Na tela `dinovatech/modules/Agenda/dashboard.php`, foi solicitada a exibição da linha visual que sinaliza o momento/horário atual (o *now indicator* do FullCalendar).

## Análise Técnica
1. O FullCalendar possui a opção nativa `nowIndicator: true`, que desenha uma linha e marcador indicando a hora e minuto atuais nas visualizações baseadas em grade de horários (`timeGridWeek`, `timeGridDay`).
2. No arquivo `dashboard.php`, a propriedade `timeZone: 'UTC'` é utilizada intencionalmente para renderização literal dos horários dos eventos (WYSIWYG), evitando distorções ao serializar datas sem conversão de fuso horário.
3. Para que o indicador do momento atual corresponda com exatidão ao relógio local do usuário (e não a um fuso UTC bruto +0), definimos a propriedade `now` como uma função que gera o timestamp UTC equivalente aos valores locais (`getFullYear`, `getMonth`, `getDate`, `getHours`, `getMinutes`, `getSeconds`).
4. Adicionamos estilização CSS dedicada para os seletores `.fc-timegrid-now-indicator-line` e `.fc-timegrid-now-indicator-arrow` para destacar o indicador em vermelho (`#ef4444`) com boa visibilidade e sobreposição adequada (`z-index`).

## Arquivos Modificados
- `dinovatech/modules/Agenda/dashboard.php`

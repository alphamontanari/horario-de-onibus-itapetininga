<?php
/**
 * Plugin Name: Horário de Ônibus Itapetininga (Derivado)
 * Plugin URI:  https://github.com/alphamontanari/horario-de-onibus-itapetininga
 * Description: Fork com tema/JS próprios em rota alternativa (/horario-de-onibus-itapetininga) consumindo as linhas do plugin original.
 * Version:     0.2.1
 * Author:      André Luiz Montanari
 * Author URI:  https://github.com/alphamontanari
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: horario-de-onibus-itapetininga
 * Domain Path: /languages
 *
 * Update URI:  https://github.com/alphamontanari/horario-de-onibus-itapetininga
 *
 * GitHub Plugin URI: alphamontanari/horario-de-onibus-itapetininga
 * Primary Branch:    main
 * Release Asset:     true
 */

if (!defined('ABSPATH')) {
  exit;
}

/** Slug/rota deste fork */
define('HOR2_SLUG', 'horario-de-onibus-itapetininga');

/** Caminhos internos deste fork */
define('HOR2_DIR', plugin_dir_path(__FILE__));
define('HOR2_ASSETS_DIR', trailingslashit(HOR2_DIR . 'assets'));

/** Identificação do plugin original (NÃO precisa editar o original) */
define('HOR_ORIG_DIRNAME', 'horario-onibus-itapetininga-main'); // nome real da pasta
define('HOR_ORIG_MAIN', HOR_ORIG_DIRNAME . '/horario-onibus-itapetininga.php'); // arquivo principal
define('HOR_ORIG_SLUG', 'horario-onibus-itapetininga'); // slug da rota do original (não muda)
define('HOR_ORIG_LINES_DIR', trailingslashit(WP_PLUGIN_DIR . '/' . HOR_ORIG_DIRNAME . '/assets/linhas'));

/** Regras de rota do fork */
add_action('init', function () {
  add_rewrite_tag('%hor2%', '([0-1])');
  add_rewrite_tag('%hor2_asset%', '(.+)');

  add_rewrite_rule('^' . HOR2_SLUG . '/?$', 'index.php?hor2=1', 'top');
  add_rewrite_rule('^' . HOR2_SLUG . '/(.+)$', 'index.php?hor2=1&hor2_asset=$matches[1]', 'top');
});

register_activation_hook(__FILE__, function () {
  do_action('init');
  flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function () {
  flush_rewrite_rules();
});

/** MIME simples */
function hor2_mime($ext)
{
  $map = [
    'css' => 'text/css; charset=UTF-8',
    'js' => 'application/javascript; charset=UTF-8',
    'json' => 'application/json; charset=UTF-8',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'webp' => 'image/webp',
    'html' => 'text/html; charset=UTF-8',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'otf' => 'font/otf',
    'map' => 'application/json; charset=UTF-8',
  ];
  $ext = strtolower((string) $ext);
  return $map[$ext] ?? 'application/octet-stream';
}

/** Servir assets do fork via rota limpa */
function hor2_serve_asset($rel_path)
{
  $clean = ltrim((string) $rel_path, '/');
  if (strpos($clean, 'assets/') === 0)
    $clean = substr($clean, 7);

  $base = realpath(HOR2_ASSETS_DIR);
  $file = realpath(HOR2_ASSETS_DIR . $clean);

  if (!$base || !$file || !is_file($file) || strpos($file, $base) !== 0) {
    status_header(404);
    exit;
  }

  $ext = pathinfo($file, PATHINFO_EXTENSION);
  header('Content-Type: ' . hor2_mime($ext));
  header('Cache-Control: public, max-age=300');
  readfile($file);
  exit;
}

/**
 * Coleta dinâmica das linhas DIRETO DO PLUGIN ORIGINAL
 * - Se o original estiver ativo, usa a rota limpa dele (/horario-onibus-itapetininga/linhas/...).
 * - Se não estiver ativo, usa fallback para URL estática em /wp-content/plugins/...
 * (Não altera nada no plugin original)
 */
function hor2_collect_linhas_from_original()
{
  $orig_ativo = false;
  if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (function_exists('is_plugin_active')) {
      $orig_ativo = is_plugin_active(HOR_ORIG_MAIN);
    }
  }

  $linhas_files = [];
  $linhas_vars = [];

  if (is_dir(HOR_ORIG_LINES_DIR)) {
    $files = glob(HOR_ORIG_LINES_DIR . '*.js') ?: [];
    natsort($files);

    foreach ($files as $full) {
      $base = basename($full);                     // ex.: linha01A.js
      $name = preg_replace('/\.js$/i', '', $base); // ex.: linha01A
      $var = preg_replace('/^linha/', 'Linha', $name);

      // Preferir rota do original quando ativo (melhor cache/headers)
      if ($orig_ativo) {
        $url = home_url('/' . HOR_ORIG_SLUG . '/linhas/' . $base);
      } else {
        // Fallback: URL estática ao arquivo físico do plugin original
        $url = content_url('plugins/' . HOR_ORIG_DIRNAME . '/assets/linhas/' . $base);
      }

      $linhas_files[] = ['url' => $url, 'var' => $var];
      $linhas_vars[] = $var;
    }
  }

  return [$linhas_files, $linhas_vars];
}

/** Página + assets (router do fork) */
add_action('template_redirect', function () {
  if ((int) get_query_var('hor2') !== 1)
    return;

  // Proxy de assets do fork
  $asset = get_query_var('hor2_asset');
  if (!empty($asset))
    hor2_serve_asset($asset);

  // Coletar as linhas do plugin ORIGINAL (sem modificá-lo)
  list($linhas_files, $linhas_vars) = hor2_collect_linhas_from_original();

  status_header(200);
  nocache_headers();
  header('Content-Type: text/html; charset=' . get_bloginfo('charset'), true);
  ?>
  <!DOCTYPE html>
  <html lang="pt-BR">

  <head>
    <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horário de Ônibus</title>

    <!-- CSS do fork -->
    <link rel="stylesheet" href="<?php echo esc_url(home_url('/' . HOR2_SLUG . '/style.css')); ?>">
    <link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=share" />

  </head>

  <body>
    <div class="wrap">
      <div class="card header">
        <img
          src="<?php echo esc_url(home_url('/' . HOR2_SLUG . '/assets/img/logotipo_cabecalho_horario_onibus_512.png')); ?>"
          alt="Logo" class="logo">
        <h1 class="title">Horário de Ônibus</h1>
        <p class="subtitle"></p>
      </div>

      <div id="crumbs" class="crumbs" aria-live="polite"></div>
      <div id="app" class="card">Carregando…</div>
    </div>


    <button id="shareBtn" class="fab-copy" type="button" aria-label="Compartilhar">
      <span class="material-symbols-outlined">share</span>
    </button>

    <!-- BOTÕES 
    <button id="shareBtn" class="fab-share" type="button" aria-label="Compartilhar">📤 <span
        class="label">Compartilhar</span></button>
    <button id="copyLinkBtn" class="fab-copy" type="button" aria-label="Copiar link">🔗 <span class="label">Copiar
        endereço</span></button>
    <span id="copyLinkHint" class="copy-hint" role="status" aria-live="polite"></span>
    
    <button id="copyLinkBtn" class="fab-copy" type="button" aria-label="Copiar link">
      🔗 <span class="label">Copiar endereço</span>
    </button>
    <span id="copyLinkHint" class="copy-hint" role="status" aria-live="polite"></span>
    
    
    -->

    <!-- OPCIONAL: menu de fallback com apps -->
    <div id="shareFallback" hidden>
      <a id="waLink" target="_blank" rel="noopener">WhatsApp</a> ·
      <a id="tgLink" target="_blank" rel="noopener">Telegram</a> ·
      <a id="xLink" target="_blank" rel="noopener">X/Twitter</a> ·
      <a id="fbLink" target="_blank" rel="noopener">Facebook</a>
    </div>

    <script>
      (function () {
        const shareBtn = document.getElementById('shareBtn');
        const copyBtn = document.getElementById('copyLinkBtn');
        const hint = document.getElementById('copyLinkHint');
        const fbk = document.getElementById('shareFallback');
        const waLink = document.getElementById('waLink');
        const tgLink = document.getElementById('tgLink');
        const xLink = document.getElementById('xLink');
        const fbLink = document.getElementById('fbLink');

        // Texto padrão (ajuste como quiser)
        function getShareData() {
          const url = window.location.href;
          const title = document.title || 'Horário de Ônibus de Itapetininga';
          const text = 'ACESSE HORÁRIOS DE ÔNIBUS no 9Itapê, seu dia começa aqui!:';
          return { url, title, text };
        }

        // COPIAR (seu código com leve ajuste)
        async function copy(text) {
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
          }
          const ta = document.createElement('textarea');
          ta.value = text;
          ta.setAttribute('readonly', '');
          ta.style.position = 'fixed';
          ta.style.left = '-9999px';
          document.body.appendChild(ta);
          ta.select();
          const ok = document.execCommand('copy');
          document.body.removeChild(ta);
          if (!ok) throw new Error('copy fallback falhou');
          return true;
        }

        function flash(msg, timeout = 1500) {
          hint.textContent = msg;
          clearTimeout(flash._t);
          flash._t = setTimeout(() => hint.textContent = '', timeout);
        }

        // 1) Compartilhamento nativo (Web Share API)
        async function onShareClick() {
          const { url, title, text } = getShareData();
          if (navigator.share) {
            try {
              await navigator.share({ title, text, url });
              // opcional: feedback
              flash('Compartilhado!');
            } catch (err) {
              // Abort (usuário cancelou) não é erro de UX; outros casos: faz fallback
              if (err && err.name !== 'AbortError') {
                showFallbackLinks({ url, title, text });
                fbk.hidden = false;
                flash('Escolha um app abaixo 👇');
              }
            }
          } else {
            // Sem suporte → fallback
            showFallbackLinks({ url, title, text });
            fbk.hidden = false;
            flash('Escolha um app abaixo 👇');
          }
        }

        // 2) Fallback com links diretos para apps
        function showFallbackLinks({ url, title, text }) {
          const encodedURL = encodeURIComponent(url);
          const encodedText = encodeURIComponent(`${text} ${url}`);
          const encodedTitle = encodeURIComponent(title);

          // WhatsApp (funciona web/mobile)
          waLink.href = `https://api.whatsapp.com/send?text=${encodedText}`;

          // Telegram
          tgLink.href = `https://t.me/share/url?url=${encodedURL}&text=${encodedText}`;

          // X (Twitter)
          xLink.href = `https://twitter.com/intent/tweet?url=${encodedURL}&text=${encodedText}`;

          // Facebook (compartilhamento web)
          fbLink.href = `https://www.facebook.com/sharer/sharer.php?u=${encodedURL}`;
        }

        // 3) Copiar link (separado)
        async function onCopyClick() {
          const { url } = getShareData();
          try {
            await copy(url);
            flash('Link copiado!');
          } catch {
            flash('Não foi possível copiar');
          }
        }

        shareBtn.addEventListener('click', onShareClick);
        copyBtn.addEventListener('click', onShareClick);

        // UX: se o navegador tem Web Share, você pode esconder o botão "Copiar" se quiser
        if (navigator.share) {
          // copyBtn.hidden = true; // opcional
        }
      })();
    </script>


    <!-- IMPORTAR DADOS DE LINHAS (dinâmico, vindos do plugin original) -->
    <?php foreach ($linhas_files as $f): ?>
      <script src="<?php echo esc_url($f['url']); ?>"></script>
    <?php endforeach; ?>

    <!-- CRIAR BD DE LINHAS (compatível com const/let/var) -->
    <script>
      const LINHAS = {};
      <?php foreach ($linhas_vars as $v): ?>
        try { if (typeof <?php echo $v; ?> !== 'undefined') LINHAS.<?php echo $v; ?> = <?php echo $v; ?>; } catch (e) { }
      <?php endforeach; ?>
    </script>

    <!-- FUNÇÃO COMPARTILHAR URL -->

    <script>
      /*(function () {
        const btn = document.getElementById('copyLinkBtn');
        const hint = document.getElementById('copyLinkHint');

        async function copy(text) {
          // Clipboard API (HTTPS/localhost)
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
          }
          // Fallback (funciona em + navegadores)
          const ta = document.createElement('textarea');
          ta.value = text;
          ta.setAttribute('readonly', '');
          ta.style.position = 'fixed';
          ta.style.left = '-9999px';
          document.body.appendChild(ta);
          ta.select();
          const ok = document.execCommand('copy');
          document.body.removeChild(ta);
          if (!ok) throw new Error('copy fallback falhou');
          return true;
        }

        async function onCopyClick() {
          const url = window.location.href;           // pega o URL no momento do clique
          try {
            await copy(url);
            hint.textContent = 'Link copiado!';
          } catch {
            hint.textContent = 'Não foi possível copiar';
          }
          // feedback rápido (você estiliza depois)
          clearTimeout(onCopyClick._t);
          onCopyClick._t = setTimeout(() => hint.textContent = '', 1500);
        }

        btn.addEventListener('click', onCopyClick);
      })();*/
    </script>

    </script>

    <!-- JS do fork (sua UI v2) -->
    <script src="<?php echo esc_url(home_url('/' . HOR2_SLUG . '/main.js')); ?>"></script>
  </body>

  </html>
  <?php
  exit;
});

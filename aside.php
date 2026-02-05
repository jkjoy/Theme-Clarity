<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$showAside = clarity_get('showAside', true);
$enableAside = clarity_bool(clarity_opt('aside_enable', '1'));
$showAside = $showAside && $enableAside;
$enablePostToc = $this->is('post') ? clarity_should_show_toc($this, 'post') : false;
$enablePageToc = $this->is('page') ? clarity_should_show_toc($this, 'page') : false;
$widgets = clarity_get_widgets();
$momentsCount = (int) clarity_opt('moments_widget_count', '3');
$momentsTitle = clarity_opt('moments_widget_title', '微语');
$momentsNoText = clarity_opt('moments_widget_no_text', '');
$moments = $momentsCount > 0 ? clarity_moments_items($momentsCount) : [];
$momentsPageUrl = clarity_moments_base_url();
$momentsPageSep = strpos($momentsPageUrl, '?') === false ? '?' : '&';
$momentsTagLink = function (string $tag) use ($momentsPageUrl, $momentsPageSep): string {
    return $momentsPageUrl . $momentsPageSep . 'tag=' . urlencode($tag);
};
$weatherKey = trim((string) clarity_opt('weather_key', ''));
?>

<?php if (!$showAside): ?>
  <aside id="z-aside" style="display:none"></aside>
  <?php return; ?>
<?php endif; ?>

<aside id="z-aside">
  <?php if ($this->is('post') || $this->is('page')): ?>
    <?php if ($enablePostToc || $enablePageToc): ?>
      <section class="widget toc-widget" id="catalog-widget" style="display:none">
        <hgroup class="widget-title">
          <span class="title-text">文章目录</span>
          <a href="#content" aria-label="返回顶部" data-title="返回顶部" onclick="window.scrollTo({ top: 0, behavior: 'smooth' }); return false;">
            <span class="icon-[ph--arrow-circle-up-bold]"></span>
          </a>
          <a href="#comment" aria-label="评论区" data-title="评论区" onclick="document.getElementById('comment')?.scrollIntoView({ behavior: 'smooth' }); return false;">
            <span class="icon-[ph--chat-circle-text-bold]"></span>
          </a>
        </hgroup>
        <div class="widget-body widget-card toc-body">
          <nav id="catalog-content" class="toc-nav"></nav>
          <p id="no-toc-tip" class="no-toc" style="display:none">暂无目录信息</p>
        </div>
      </section>

      <script>
        (function () {
          function generateCatalog() {
            const article = document.querySelector('.article');
            const catalogWidget = document.getElementById('catalog-widget');
            const catalogContent = document.getElementById('catalog-content');
            const noTocTip = document.getElementById('no-toc-tip');

            if (!article || !catalogWidget || !catalogContent) return;

            const headers = Array.from(article.querySelectorAll('h1, h2, h3, h4, h5, h6'));
            if (headers.length === 0) {
              catalogWidget.style.display = 'block';
              catalogContent.style.display = 'none';
              noTocTip.style.display = 'block';
              return;
            }

            catalogWidget.style.display = 'block';
            catalogContent.style.display = 'block';
            noTocTip.style.display = 'none';
            catalogContent.innerHTML = '';

            const root = { children: [] };
            const stack = [root];

            headers.forEach((header, index) => {
              if (!header.id) header.id = 'heading-' + index;
              const level = parseInt(header.tagName.substring(1));
              const item = { id: header.id, text: header.textContent, level: level, children: [] };
              while (stack.length > 1 && stack[stack.length - 1].level >= level) {
                stack.pop();
              }
              stack[stack.length - 1].children.push(item);
              stack.push(item);
            });

            function renderTree(items) {
              if (!items.length) return null;
              const ol = document.createElement('ol');
              items.forEach((item) => {
                const li = document.createElement('li');
                li.dataset.id = item.id;
                const a = document.createElement('a');
                a.href = '#' + item.id;
                a.textContent = item.text;
                a.title = item.text;
                a.onclick = (e) => {
                  e.preventDefault();
                  const target = document.getElementById(item.id);
                  if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                    history.pushState(null, null, '#' + item.id);
                    target.classList.remove('toc-highlight');
                    void target.offsetWidth;
                    target.classList.add('toc-highlight');
                    setTimeout(() => target.classList.remove('toc-highlight'), 2000);
                  }
                };
                li.appendChild(a);
                const childrenOl = renderTree(item.children);
                if (childrenOl) li.appendChild(childrenOl);
                ol.appendChild(li);
              });
              return ol;
            }

            const treeDom = renderTree(root.children);
            if (treeDom) catalogContent.appendChild(treeDom);

            if (window.tocObserver) window.tocObserver.disconnect();

            const observerCallback = (entries) => {
              entries.forEach((entry) => {
                const li = catalogContent.querySelector('li[data-id="' + entry.target.id + '"]');
                if (!li) return;
                if (entry.isIntersecting) li.classList.add('active');
                else li.classList.remove('active');
              });

              catalogContent.querySelectorAll('.has-active').forEach((el) => el.classList.remove('has-active'));
              let currentActive = catalogContent.querySelector('li.active');
              if (currentActive) {
                let parent = currentActive.parentElement.closest('li');
                while (parent) {
                  parent.classList.add('has-active');
                  parent = parent.parentElement.closest('li');
                }
              }
            };

            window.tocObserver = new IntersectionObserver(observerCallback, { rootMargin: '-80px 0px -70% 0px' });
            headers.forEach((h) => window.tocObserver.observe(h));
          }

          generateCatalog();
        })();
      </script>
    <?php endif; ?>
  <?php else: ?>
    <?php foreach ($widgets as $widget): ?>
      <?php switch ($widget):
        case 'stats': ?>
          <section class="widget">
            <hgroup class="widget-title text-creative">博客统计</hgroup>
            <div class="widget-body widget-card">
              <dl class="dl-group small">
                <div>
                  <dt>运营时长</dt>
                  <dd id="site-runtime" title="博客于 <?php echo htmlspecialchars(clarity_opt('site_start_time', '2024-01-01'), ENT_QUOTES, 'UTF-8'); ?> 上线">-</dd>
                </div>
                <div>
                  <dt>上次更新</dt>
                  <?php $latest = clarity_get_latest_post_time(); ?>
                  <dd class="time-ago" id="latest-update-time" data-time="<?php echo htmlspecialchars($latest ?? '', ENT_QUOTES, 'UTF-8'); ?>">-</dd>
                </div>
                <div>
                  <dt>文章数目</dt>
                  <dd><?php echo clarity_get_post_count(); ?></dd>
                </div>
              </dl>
            </div>
          </section>
          <?php break; ?>
        <?php case 'tech-info': ?>
          <section class="widget">
            <hgroup class="widget-title text-creative">技术信息</hgroup>
            <div class="widget-body widget-card">
              <dl class="dl-group medium">
                <div>
                  <dt>软件协议</dt>
                  <dd>GPL-3.0</dd>
                </div>
                <div>
                  <dt>文章许可</dt>
                  <dd><a href="<?php echo htmlspecialchars(clarity_opt('license_url', ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars(clarity_opt('license', ''), ENT_QUOTES, 'UTF-8'); ?></a></dd>
                </div>
                <div>
                  <dt>规范域名</dt>
                  <dd class="domain-text" title="<?php echo htmlspecialchars($this->options->siteUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php
                    $cleanUrl = preg_replace('#^https?://#', '', $this->options->siteUrl);
                    $cleanUrl = preg_replace('#^www\.#', '', $cleanUrl);
                    $cleanUrl = rtrim($cleanUrl, '/');
                    echo htmlspecialchars($cleanUrl, ENT_QUOTES, 'UTF-8');
                    ?>
                  </dd>
                </div>
              </dl>
              <div class="z-expand" x-data="{ expand: false }">
                <div class="expand-content" x-show="expand" x-collapse>
                  <dl class="dl-group small build-info">
                    <div><dt>Theme</dt><dd>Clarity</dd></div>
                    <div><dt>Version</dt><dd><?php echo CLARITY_VERSION; ?></dd></div>
                    <div><dt>Framework</dt><dd>Typecho</dd></div>
                  </dl>
                </div>
                <button class="toggle-btn in-place" @click="expand = !expand">
                  <span class="icon-[ph--caret-double-down-bold] toggle-icon" :class="{ 'expand': expand }"></span>
                  <span x-text="expand ? '收起构建信息' : '展开构建信息'"></span>
                </button>
              </div>
            </div>
          </section>
          <?php break; ?>
        <?php case 'weather': ?>
          <?php if ($weatherKey !== ''): ?>
            <section class="widget">
              <hgroup class="widget-title text-creative">天气预报</hgroup>
              <div class="widget-body widget-card" id="weather-container" data-key="<?php echo htmlspecialchars($weatherKey, ENT_QUOTES, 'UTF-8'); ?>" data-icon-base="<?php $this->options->themeUrl('assets/images'); ?>">
                <div class="weather-init" style="display:flex;align-items:center;justify-content:center;gap:.5em;padding:.75rem 1rem;font-size:.9em;color:var(--c-text-3);">
                  <span class="icon-[ph--spinner] animate-spin"></span>
                  <span>加载中...</span>
                </div>
              </div>
              <script>
                (function initWeather() {
                  const container = document.getElementById('weather-container');
                  if (!container) return;
                  if (window.mountWeather) {
                    container.innerHTML = '';
                    window.mountWeather(container, container.dataset.key, container.dataset.iconBase);
                  } else {
                    window.addEventListener('load', function () {
                      if (window.mountWeather) {
                        container.innerHTML = '';
                        window.mountWeather(container, container.dataset.key, container.dataset.iconBase);
                      }
                    });
                  }
                })();
              </script>
            </section>
          <?php endif; ?>
          <?php break; ?>
        <?php case 'moments': ?>
          <section class="widget">
            <hgroup class="widget-title">
              <span class="title-text text-creative"><?php echo htmlspecialchars($momentsTitle, ENT_QUOTES, 'UTF-8'); ?></span>
              <a href="<?php echo htmlspecialchars($momentsPageUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="查看全部" title="查看全部">
                <span class="icon-[ph--arrow-right-bold]"></span>
              </a>
            </hgroup>
            <div class="widget-body widget-card moments-widget" data-title="<?php echo htmlspecialchars($momentsTitle, ENT_QUOTES, 'UTF-8'); ?>" data-no-text="<?php echo htmlspecialchars($momentsNoText, ENT_QUOTES, 'UTF-8'); ?>">
              <?php
              $shown = 0;
              foreach ($moments as $moment):
                  if ($shown >= $momentsCount) break;
                  $content = $moment['content'] ?? '';
                  $time = $moment['time'] ?? '';
                  $tags = $moment['tags'] ?? [];
                  $url = $moment['url'] ?? '#';
                  $hasMedia = !empty($moment['media']);
                  $shown++;
              ?>
                <article class="moment-item" data-has-media="<?php echo $hasMedia ? 'true' : 'false'; ?>">
                  <script type="text/plain" class="moment-raw-html"><?php echo $content; ?></script>
                  <a class="moment-content" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"></a>
                  <div class="moment-meta">
                    <time class="moment-time" datetime="<?php echo htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?>">
                      <span class="icon-[ph--clock-bold]"></span>
                      <span><?php echo htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?></span>
                    </time>
                    <?php if (!empty($tags)): ?>
                      <div class="moment-tags">
                        <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                          <a class="moment-tag" href="<?php echo htmlspecialchars($momentsTagLink((string) $tag), ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="tag-hash">#</span><span><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
              <?php if ($shown === 0): ?>
                <div class="empty-tip">
                  <span class="icon-[ph--shooting-star-bold]"></span>
                  暂无微语
                </div>
              <?php endif; ?>
              <script>
                (function () {
                  const widget = document.querySelector('.moments-widget');
                  if (!widget) return;
                  const items = Array.from(widget.querySelectorAll('.moment-item'));
                  const momentsTitle = widget.dataset.title || '微语';
                  const noTextConfig = widget.dataset.noText;
                  if (!items.length) return;
                  const parser = new DOMParser();
                  const run = () => {
                    items.forEach((item) => {
                      const rawHtmlScript = item.querySelector('.moment-raw-html');
                      const raw = rawHtmlScript ? rawHtmlScript.textContent || rawHtmlScript.innerText || '' : '';
                      let displayText = '';
                      let hasMedia = item.dataset.hasMedia === 'true';
                      if (raw) {
                        const doc = parser.parseFromString(raw, 'text/html');
                        const tagElements = doc.querySelectorAll('.tag');
                        tagElements.forEach((tag) => { tag.textContent = ''; });
                        const text = doc.body.textContent || doc.body.innerText || '';
                        displayText = text.trim();
                        const mediaSelectors = ['img[src]','video[src]','audio[src]','figure[data-content-type="image"]','figure[data-content-type="video"]','iframe[src]','source[src]'];
                        hasMedia = hasMedia || mediaSelectors.some((selector) => doc.querySelector(selector));
                      }
                      if (!displayText) {
                        if (hasMedia) {
                          if (noTextConfig && noTextConfig.trim()) displayText = noTextConfig.trim();
                          else displayText = `此${momentsTitle}无文字，请点击查看。`;
                        } else {
                          item.style.display = 'none';
                          return;
                        }
                      }
                      const content = item.querySelector('.moment-content');
                      if (content) content.textContent = displayText;
                    });
                  };
                  window.requestIdleCallback ? requestIdleCallback(run) : run();
                })();
              </script>
            </div>
          </section>
          <?php break; ?>
        <?php case 'community': ?>
          <?php $communityImage = trim((string) clarity_opt('community_image', '')); ?>
          <?php if ($communityImage !== ''): ?>
            <section class="widget dim">
              <hgroup class="widget-title text-creative"><?php echo htmlspecialchars(clarity_opt('community_title', '博客/技术社区'), ENT_QUOTES, 'UTF-8'); ?></hgroup>
              <div class="widget-body widget-card with-bg">
                <img class="bg-img bg-right" src="<?php echo htmlspecialchars($communityImage, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy" />
                <div class="comm-title text-creative"><?php echo htmlspecialchars(clarity_opt('community_name', '技术交流群'), ENT_QUOTES, 'UTF-8'); ?></div>
                <p class="comm-tip">
                  <span class="icon-[ph--chat-circle-dots-bold]"></span>
                  <span><?php echo htmlspecialchars(clarity_opt('community_desc', ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </p>
              </div>
            </section>
          <?php endif; ?>
          <?php break; ?>
        <?php case 'sponsor': ?>
          <section class="widget dim">
            <hgroup class="widget-title text-creative"><?php echo htmlspecialchars(clarity_opt('sponsor_title', '云计算支持'), ENT_QUOTES, 'UTF-8'); ?></hgroup>
            <a href="<?php echo htmlspecialchars(clarity_opt('sponsor_url', '#'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="widget-body widget-card with-bg sponsor-card">
              <?php $sponsorLogo = trim((string) clarity_opt('sponsor_logo', '')); ?>
              <?php if ($sponsorLogo !== ''): ?>
                <img class="bg-img bg-right" src="<?php echo htmlspecialchars($sponsorLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy" />
              <?php endif; ?>
              <div class="sponsor-name text-creative"><?php echo htmlspecialchars(clarity_opt('sponsor_name', '赞助商'), ENT_QUOTES, 'UTF-8'); ?></div>
              <p class="sponsor-tip">
                <span class="icon-[ph--cloud-bold]"></span>
                <span><?php echo htmlspecialchars(clarity_opt('sponsor_desc', '提供云计算服务'), ENT_QUOTES, 'UTF-8'); ?></span>
              </p>
            </a>
          </section>
          <?php break; ?>
        <?php case 'custom': ?>
          <section class="widget">
            <hgroup class="widget-title text-creative"><?php echo htmlspecialchars(clarity_opt('aside_custom_title', '自定义'), ENT_QUOTES, 'UTF-8'); ?></hgroup>
            <div class="widget-body widget-card"><?php echo clarity_opt('aside_custom_html', ''); ?></div>
          </section>
          <?php break; ?>
      <?php endswitch; ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <script>
    (function () {
      const startTime = '<?php echo htmlspecialchars(clarity_opt('site_start_time', ''), ENT_QUOTES, 'UTF-8'); ?>';
      if (startTime) {
        const start = new Date(startTime).getTime();
        const now = Date.now();
        const days = Math.floor((now - start) / (1000 * 60 * 60 * 24));
        const years = Math.floor(days / 365);
        const remainingDays = days % 365;
        const months = Math.floor(remainingDays / 30);
        let text = '';
        if (years > 0) text += years + '年';
        if (months > 0) text += months + '个月';
        if (years === 0 && months === 0) text = days + '天';
        const el = document.getElementById('site-runtime');
        if (el) el.textContent = text;
      }

      document.querySelectorAll('.time-ago').forEach((el) => {
        let timeStr = el.getAttribute('data-time');
        if (!timeStr) timeStr = (el.textContent || '').trim();
        if (!timeStr) return;

        const parsed = new Date(timeStr);
        if (Number.isNaN(parsed.getTime())) return;

        const time = parsed.getTime();
        const now = Date.now();
        const diff = now - time;
        const minutes = Math.floor(diff / (1000 * 60));
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        let text = '';
        if (days > 0) text = days + '天前';
        else if (hours > 0) text = hours + '小时前';
        else if (minutes > 0) text = minutes + '分钟前';
        else text = '刚刚';
        el.textContent = text;
        el.title = '更新于 ' + parsed.toLocaleString('zh-CN');
      });
    })();
  </script>
</aside>

(() => {
  if (document.querySelector('.cr-page-navigation')) return;

  const scriptUrl = document.currentScript?.src;
  if (!scriptUrl) return;
  const appBase = new URL('../', scriptUrl);
  const path = window.location.pathname;
  const homeTarget = /\/admin\//i.test(path)
    ? 'admin/dashboard.php'
    : /\/(student|career)\//i.test(path)
      ? 'student/dashboard.php'
      : 'index.php';
  const homeUrl = new URL(homeTarget, appBase);

  document.querySelectorAll('.admin-home-icon, .home-link').forEach((oldHome) => {
    oldHome.hidden = true;
    oldHome.style.setProperty('display', 'none', 'important');
    oldHome.setAttribute('aria-hidden', 'true');
  });

  const style = document.createElement('style');
  style.textContent = `
    .cr-page-navigation{position:fixed;z-index:9999;top:14px;left:14px;display:flex;gap:9px}
    .cr-page-navigation button{width:46px;height:46px;display:grid;place-items:center;padding:0;border:1px solid #41627b;border-radius:15px;background:linear-gradient(145deg,#13243aee,#111a2dec);color:#7cf2e2;box-shadow:0 8px 24px #02061155,0 0 20px #64e9dc18;cursor:pointer;backdrop-filter:blur(12px);transition:transform .2s,border-color .2s,box-shadow .2s,color .2s}
    .cr-page-navigation button:hover{transform:translateY(-2px);border-color:#78f0e2;color:#fff;box-shadow:0 10px 28px #02061177,0 0 22px #64e9dc44}
    .cr-page-navigation button:active{transform:scale(.94)}
    .cr-page-navigation button:focus-visible{outline:3px solid #9d91ff;outline-offset:3px}
    .cr-page-navigation svg{width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
    @media(max-width:650px){.cr-page-navigation{top:8px;left:8px;gap:6px}.cr-page-navigation button{width:40px;height:40px;border-radius:13px}}
    @media(prefers-reduced-motion:reduce){.cr-page-navigation button{transition:none}}
  `;
  document.head.appendChild(style);

  const nav = document.createElement('nav');
  nav.className = 'cr-page-navigation';
  nav.setAttribute('aria-label', 'Page navigation');
  nav.innerHTML = `
    <button type="button" data-home aria-label="Home" title="Home">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg>
    </button>
    <button type="button" data-back aria-label="Back" title="Back">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
    </button>`;
  document.body.appendChild(nav);
  nav.querySelector('[data-home]').addEventListener('click', () => { window.location.href = homeUrl.href; });
  nav.querySelector('[data-back]').addEventListener('click', () => {
    if (window.history.length > 1) window.history.back();
    else window.location.href = homeUrl.href;
  });
})();

(() => {
    const cfg = window.LSLS_CONFIG || null;
    if (!cfg || !Array.isArray(cfg.languages) || cfg.languages.length === 0) {
        return;
    }

    const safe = (s) => String(s || '').replace(/[<>"']/g, '');
    const norm = (url) => String(url || '').trim();

    function ensureMountPoint() {
        const breakpoint = Number.isFinite(cfg.breakpoint) ? cfg.breakpoint : 768;
        const isMobile = window.matchMedia(`(max-width: ${breakpoint}px)`).matches;

        let container = null;
        if (isMobile) {
            if (cfg.fallbackToBody || !cfg.desktopSelector) {
                container = document.body;
            }
        } else if (cfg.desktopSelector) {
            container = document.querySelector(cfg.desktopSelector);
        }

        const hidden = (el) => !el || getComputedStyle(el).display === 'none' || getComputedStyle(el).visibility === 'hidden';
        if (hidden(container)) {
            if (isMobile) {
                container = document.body;
            } else {
                return null;
            }
        }

        if (!container) {
            container = document.body;
        }

        let mount = document.getElementById('ls-lang-switcher');
        if (mount) {
            return mount;
        }

        mount = document.createElement('div');
        mount.id = 'ls-lang-switcher';
        mount.style.marginRight = '10px';
        mount.style.display = 'inline-block';

        try {
            container.insertBefore(mount, container.firstChild);
        } catch (e) {
            console.error('LSLS: Failed to insert mount point', e);
            return null;
        }
        return mount;
    }

    function renderSwitcher(mount) {
        if (!mount) {
            return;
        }
        if (mount.dataset.lslsMounted === '1') {
            return;
        }

        try {
            const breakpoint = Number.isFinite(cfg.breakpoint) ? cfg.breakpoint : 768;
            const isMobileNow = window.matchMedia(`(max-width: ${breakpoint}px)`).matches;

            const current = (() => {
                const p = location.pathname.toLowerCase();
                let idx = 0;
                cfg.languages.forEach((lang, index) => {
                    const url = norm(lang.url).toLowerCase();
                    if (!url) return;
                    try {
                        const parsed = new URL(url, location.origin);
                        const urlPath = parsed.pathname.toLowerCase();
                        if (urlPath !== '/' && p.startsWith(urlPath)) {
                            idx = index;
                        }
                    } catch (e) {
                        if (url !== '/' && p.startsWith(url)) {
                            idx = index;
                        }
                    }
                });
                return idx;
            })();

            const root = document.createElement('div');
            root.className = 'lsls-root';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lsls-btn';
            btn.setAttribute('aria-haspopup', 'menu');
            btn.setAttribute('aria-expanded', 'false');

            const img = document.createElement('img');
            img.className = 'lsls-flag';
            img.alt = '';
            img.src = norm(cfg.languages[current]?.flag || '');

            const code = document.createElement('span');
            code.className = 'lsls-code';
            code.textContent = safe(cfg.languages[current]?.code || '');

            const caret = document.createElement('span');
            caret.className = 'lsls-caret';
            caret.textContent = '▼';

            btn.appendChild(img);
            btn.appendChild(code);
            btn.appendChild(caret);

            const menu = document.createElement('div');
            menu.className = 'lsls-menu';
            menu.setAttribute('role', 'menu');
            menu.setAttribute('aria-label', 'Language switcher');

            root.appendChild(btn);
            root.appendChild(menu);
            mount.appendChild(root);
            mount.dataset.lslsMounted = '1';

            const applyPlacement = () => {
                const breakpointNow = Number.isFinite(cfg.breakpoint) ? cfg.breakpoint : 768;
                const isMobile = window.matchMedia(`(max-width: ${breakpointNow}px)`).matches;

                if (isMobile && cfg.mobileFixed) {
                    const position = cfg.mobilePosition || 'bottom-left';
                    const offset = (cfg.mobileOffset || 16) + 'px';

                    root.classList.remove('lsls-desktop-placement');
                    root.classList.remove('lsls-fixed-top-left', 'lsls-fixed-top-right', 'lsls-fixed-bottom-left', 'lsls-fixed-bottom-right');

                    const className = `lsls-fixed-${position}`;
                    root.classList.add(className);

                    root.style.position = 'fixed';
                    root.style.zIndex = '100000';

                    root.style.top = '';
                    root.style.right = '';
                    root.style.bottom = '';
                    root.style.left = '';

                    switch (position) {
                        case 'top-left':
                            root.style.top = offset;
                            root.style.left = offset;
                            break;
                        case 'top-right':
                            root.style.top = offset;
                            root.style.right = offset;
                            break;
                        case 'bottom-right':
                            root.style.bottom = offset;
                            root.style.right = offset;
                            break;
                        case 'bottom-left':
                        default:
                            root.style.bottom = offset;
                            root.style.left = offset;
                    }
                } else {
                    root.classList.add('lsls-desktop-placement');
                    root.classList.remove('lsls-fixed-top-left', 'lsls-fixed-top-right', 'lsls-fixed-bottom-left', 'lsls-fixed-bottom-right');
                    root.style.position = '';
                    root.style.zIndex = '';
                    root.style.top = '';
                    root.style.right = '';
                    root.style.bottom = '';
                    root.style.left = '';

                    if (!isMobile && mount.parentElement === document.body && cfg.desktopSelector) {
                        const desktop = document.querySelector(cfg.desktopSelector);
                        if (desktop) {
                            try {
                                desktop.insertBefore(mount, desktop.firstChild);
                            } catch (e) {
                                console.error('LSLS: Failed to move mount to desktop', e);
                            }
                        }
                    }
                }

                if (isMobile) {
                    const position = cfg.mobilePosition || 'bottom-left';
                    const isTop = position.startsWith('top');
                    const isRight = position.endsWith('right');

                    menu.style.top = isTop ? 'calc(100% + 10px)' : 'auto';
                    menu.style.bottom = isTop ? 'auto' : 'calc(100% + 10px)';
                    menu.style.left = isRight ? 'auto' : '0';
                    menu.style.right = isRight ? '0' : 'auto';
                } else {
                    menu.style.top = 'calc(100% + 10px)';
                    menu.style.bottom = 'auto';
                    menu.style.right = '0';
                    menu.style.left = 'auto';
                }
            };

            applyPlacement();
            window.addEventListener('resize', applyPlacement);

            cfg.languages.forEach((lang, index) => {
                const href = norm(lang.url);
                if (!href) {
                    return;
                }
                const a = document.createElement('a');
                a.className = 'lsls-item';
                if (index === current) {
                    a.classList.add('lsls-active');
                }
                a.href = href;
                a.setAttribute('role', 'menuitem');

                const flagImg = document.createElement('img');
                flagImg.className = 'lsls-flag';
                flagImg.alt = '';
                flagImg.src = norm(lang.flag || '');

                const langCode = document.createElement('span');
                langCode.className = 'lsls-code';
                langCode.textContent = safe(lang.code || '');

                const spacer = document.createElement('span');
                spacer.className = 'lsls-spacer';

                const langName = document.createElement('span');
                langName.className = 'lsls-name';
                langName.textContent = safe(lang.name || '');

                a.appendChild(flagImg);
                a.appendChild(langCode);
                a.appendChild(spacer);
                a.appendChild(langName);
                menu.appendChild(a);
            });

            let isOpen = false;

            const positionMenu = () => {
                const rect = btn.getBoundingClientRect();
                const breakpointNow = Number.isFinite(cfg.breakpoint) ? cfg.breakpoint : 768;
                const isMobile = window.matchMedia(`(max-width: ${breakpointNow}px)`).matches;
                const position = cfg.mobilePosition || 'bottom-left';
                const alignRight = isMobile ? position.endsWith('right') : true;

                menu.style.position = 'fixed';
                menu.style.zIndex = '100000';
                menu.style.visibility = 'hidden';
                menu.style.display = 'block';

                const menuRect = menu.getBoundingClientRect();
                const gutter = 10;

                let left = alignRight ? rect.right - menuRect.width : rect.left;
                let top = rect.bottom + gutter;

                if (top + menuRect.height > window.innerHeight - gutter) {
                    top = rect.top - menuRect.height - gutter;
                }

                if (left + menuRect.width > window.innerWidth - gutter) {
                    left = window.innerWidth - menuRect.width - gutter;
                }
                if (left < gutter) {
                    left = gutter;
                }
                if (top < gutter) {
                    top = gutter;
                }

                menu.style.top = `${Math.round(top)}px`;
                menu.style.left = `${Math.round(left)}px`;
                menu.style.right = 'auto';
                menu.style.bottom = 'auto';
                menu.style.visibility = 'visible';
            };

            const setOpen = (value) => {
                isOpen = value;
                if (isOpen) {
                    root.classList.add('lsls-open');
                    btn.setAttribute('aria-expanded', 'true');
                    menu.style.display = 'block';
                    menu.style.pointerEvents = 'auto';
                    positionMenu();
                } else {
                    root.classList.remove('lsls-open');
                    btn.setAttribute('aria-expanded', 'false');
                    menu.style.display = 'none';
                    menu.style.pointerEvents = 'none';
                }
            };

            setOpen(false);

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                setOpen(!isOpen);
            });

            document.addEventListener('pointerdown', (e) => {
                if (!root.contains(e.target)) {
                    setOpen(false);
                }
            }, true);

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && isOpen) {
                    setOpen(false);
                }
            });

            window.addEventListener('resize', () => {
                applyPlacement();
                if (isOpen) {
                    positionMenu();
                }
            });

            window.addEventListener('scroll', () => {
                if (isOpen) {
                    positionMenu();
                }
            }, true);
        } catch (e) {
            console.error('LSLS Error:', e);
        }
    }

    function boot() {
        try {
            const mount = ensureMountPoint();
            if (mount) {
                renderSwitcher(mount);
            }
        } catch (e) {
            console.error('LSLS Boot Error:', e);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    const observer = new MutationObserver(() => {
        try {
            const mount = ensureMountPoint();
            if (mount && mount.dataset.lslsMounted !== '1') {
                renderSwitcher(mount);
            }
        } catch (e) {
            console.error('LSLS MutationObserver Error:', e);
        }
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
})();

{{--
    Viewer foto global: klik thumbnail mana pun (anchor [data-lightbox], atau
    elemen ber-atribut [data-zoom-src] / class .js-zoomable) untuk membuka foto
    dalam layar penuh dengan kontrol perbesar & perkecil.

    Dipakai di semua modul, jadi tidak boleh bergantung pada library apa pun.
--}}
<div id="imgZoomViewer" class="izv" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Penampil foto">
    <div class="izv-stage" id="izvStage">
        <img class="izv-img" id="izvImg" src="" alt="Foto">
    </div>

    <button type="button" class="izv-nav izv-prev" id="izvPrev" aria-label="Foto sebelumnya">&#10094;</button>
    <button type="button" class="izv-nav izv-next" id="izvNext" aria-label="Foto berikutnya">&#10095;</button>

    <div class="izv-toolbar">
        <button type="button" class="izv-btn" id="izvZoomOut" aria-label="Perkecil">&minus;</button>
        <span class="izv-level" id="izvLevel">100%</span>
        <button type="button" class="izv-btn" id="izvZoomIn" aria-label="Perbesar">+</button>
        <button type="button" class="izv-btn izv-btn-text" id="izvReset" aria-label="Reset ukuran">Reset</button>
        <a class="izv-btn izv-btn-text" id="izvOpen" href="#" target="_blank" rel="noopener">Buka</a>
    </div>

    <button type="button" class="izv-close" id="izvClose" aria-label="Tutup">&times;</button>
    <div class="izv-hint">Scroll / cubit layar untuk zoom &middot; seret untuk geser &middot; Esc untuk tutup</div>
</div>

<style>
    .izv {
        position: fixed;
        inset: 0;
        z-index: 20000;
        background: rgba(15, 15, 18, .92);
        display: none;
        touch-action: none;
        user-select: none;
    }

    .izv.izv-open {
        display: block;
    }

    .izv-stage {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: grab;
    }

    .izv-stage.izv-dragging {
        cursor: grabbing;
    }

    .izv-img {
        max-width: 92vw;
        max-height: 84vh;
        transform-origin: center center;
        will-change: transform;
        -webkit-user-drag: none;
        user-select: none;
        pointer-events: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .5);
        background: #fff;
    }

    .izv-img.izv-animate {
        transition: transform .18s ease-out;
    }

    .izv-toolbar {
        position: absolute;
        left: 50%;
        bottom: 18px;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .12);
    }

    .izv-btn {
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        border: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, .18);
        color: #fff;
        font-size: 20px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        cursor: pointer;
    }

    .izv-btn:hover {
        background: rgba(255, 255, 255, .32);
        color: #fff;
    }

    .izv-btn-text {
        font-size: 13px;
        font-weight: 600;
    }

    .izv-level {
        min-width: 58px;
        text-align: center;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
    }

    .izv-close {
        position: absolute;
        top: 14px;
        right: 16px;
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, .18);
        color: #fff;
        font-size: 26px;
        line-height: 1;
        cursor: pointer;
    }

    .izv-close:hover {
        background: rgba(255, 255, 255, .32);
    }

    .izv-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 44px;
        height: 60px;
        border: 0;
        border-radius: 8px;
        background: rgba(255, 255, 255, .14);
        color: #fff;
        font-size: 22px;
        cursor: pointer;
        display: none;
    }

    .izv-nav:hover {
        background: rgba(255, 255, 255, .3);
    }

    .izv-prev {
        left: 14px;
    }

    .izv-next {
        right: 14px;
    }

    .izv.izv-has-group .izv-nav {
        display: block;
    }

    .izv-hint {
        position: absolute;
        top: 18px;
        left: 50%;
        transform: translateX(-50%);
        color: rgba(255, 255, 255, .65);
        font-size: 12px;
        text-align: center;
        pointer-events: none;
    }

    @media (max-width: 576px) {
        .izv-hint {
            display: none;
        }

        .izv-img {
            max-width: 96vw;
            max-height: 76vh;
        }
    }
</style>

<script>
    (function () {
        'use strict';

        var MIN_SCALE = 0.25;
        var MAX_SCALE = 8;
        var STEP = 0.25;

        var root = document.getElementById('imgZoomViewer');
        if (!root) return;

        var stage = document.getElementById('izvStage');
        var img = document.getElementById('izvImg');
        var levelEl = document.getElementById('izvLevel');
        var openEl = document.getElementById('izvOpen');

        var scale = 1, tx = 0, ty = 0;
        var group = [], groupIndex = 0;
        var pointers = new Map();
        var pinchStartDist = 0, pinchStartScale = 1;
        var dragStart = null;
        var dragMoved = false;

        function esc(value) {
            return window.CSS && CSS.escape ? CSS.escape(value) : value.replace(/["\\]/g, '\\$&');
        }

        function apply(animate) {
            img.classList.toggle('izv-animate', !!animate);
            img.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')';
            levelEl.textContent = Math.round(scale * 100) + '%';
        }

        function reset() {
            scale = 1;
            tx = 0;
            ty = 0;
            apply(true);
        }

        /** Zoom ke skala baru sambil menahan titik (px, py) tetap di posisinya. */
        function zoomTo(next, px, py, animate) {
            next = Math.min(MAX_SCALE, Math.max(MIN_SCALE, next));
            if (next === scale) return;

            var rect = stage.getBoundingClientRect();
            var cx = rect.left + rect.width / 2;
            var cy = rect.top + rect.height / 2;

            if (typeof px !== 'number') {
                px = cx;
                py = cy;
            }

            var ratio = next / scale;
            tx = px - cx - ratio * (px - cx - tx);
            ty = py - cy - ratio * (py - cy - ty);
            scale = next;
            apply(animate);
        }

        function zoomBy(delta, px, py, animate) {
            zoomTo(scale + delta, px, py, animate);
        }

        function show(src, alt) {
            img.classList.remove('izv-animate');
            img.src = src;
            img.alt = alt || 'Foto';
            openEl.href = src;
            scale = 1;
            tx = 0;
            ty = 0;
            apply(false);
        }

        function openViewer(src, alt, items, index) {
            group = items || [];
            groupIndex = index || 0;
            root.classList.toggle('izv-has-group', group.length > 1);
            show(src, alt);
            root.classList.add('izv-open');
            root.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeViewer() {
            root.classList.remove('izv-open');
            root.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            img.removeAttribute('src');
            pointers.clear();
        }

        function step(dir) {
            if (group.length < 2) return;
            groupIndex = (groupIndex + dir + group.length) % group.length;
            show(group[groupIndex].src, group[groupIndex].alt);
        }

        // ===== Sumber foto: anchor lightbox lama, atau opt-in data-zoom-src =====
        function resolveTarget(el) {
            var anchor = el.closest('a[data-lightbox], [data-zoom-src], img.js-zoomable');
            if (!anchor) return null;

            var src = anchor.getAttribute('data-zoom-src') || anchor.getAttribute('href');
            if (!src && anchor.tagName === 'IMG') src = anchor.getAttribute('src');
            if (!src) return null;
            // Lampiran PDF bukan gambar: biarkan dibuka browser seperti biasa.
            if (/\.pdf(\?|#|$)/i.test(src)) return null;

            var inner = anchor.querySelector ? anchor.querySelector('img') : null;
            var alt = anchor.getAttribute('data-zoom-alt')
                || (inner && inner.getAttribute('alt'))
                || (anchor.tagName === 'IMG' ? anchor.getAttribute('alt') : '')
                || 'Foto';

            return { el: anchor, src: src, alt: alt };
        }

        function collectGroup(anchor) {
            var name = anchor.getAttribute('data-lightbox') || anchor.getAttribute('data-zoom-group');
            if (!name) return null;

            var nodes = document.querySelectorAll(
                '[data-lightbox="' + esc(name) + '"], [data-zoom-group="' + esc(name) + '"]'
            );

            var items = [];
            Array.prototype.forEach.call(nodes, function (node) {
                var t = resolveTarget(node);
                if (t) items.push({ src: t.src, alt: t.alt, el: node });
            });

            return items;
        }

        // Fase capture, supaya lightbox lama (listener di document) tidak ikut terbuka.
        document.addEventListener('click', function (e) {
            if (root.contains(e.target)) return;
            if (!(e.target instanceof Element)) return;

            var target = resolveTarget(e.target);
            if (!target) return;

            e.preventDefault();
            e.stopPropagation();

            var items = collectGroup(target.el);
            var index = 0;

            if (items && items.length) {
                for (var i = 0; i < items.length; i++) {
                    if (items[i].el === target.el) {
                        index = i;
                        break;
                    }
                }
            } else {
                items = [{ src: target.src, alt: target.alt, el: target.el }];
            }

            openViewer(target.src, target.alt, items, index);
        }, true);

        // ===== Kontrol =====
        document.getElementById('izvZoomIn').addEventListener('click', function () {
            zoomBy(STEP, null, null, true);
        });
        document.getElementById('izvZoomOut').addEventListener('click', function () {
            zoomBy(-STEP, null, null, true);
        });
        document.getElementById('izvReset').addEventListener('click', reset);
        document.getElementById('izvClose').addEventListener('click', closeViewer);
        document.getElementById('izvPrev').addEventListener('click', function () {
            step(-1);
        });
        document.getElementById('izvNext').addEventListener('click', function () {
            step(1);
        });

        // Klik area gelap (bukan hasil menggeser) untuk menutup.
        stage.addEventListener('click', function (e) {
            if (e.target === stage && !dragMoved) closeViewer();
        });

        stage.addEventListener('dblclick', function (e) {
            zoomTo(scale > 1.05 ? 1 : 2, e.clientX, e.clientY, true);
        });

        stage.addEventListener('wheel', function (e) {
            e.preventDefault();
            zoomBy(e.deltaY < 0 ? STEP : -STEP, e.clientX, e.clientY, false);
        }, { passive: false });

        // ===== Geser (pan) & cubit (pinch) =====
        stage.addEventListener('pointerdown', function (e) {
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            dragMoved = false;

            if (pointers.size === 1) {
                dragStart = { x: e.clientX - tx, y: e.clientY - ty, tx0: tx, ty0: ty };
                stage.classList.add('izv-dragging');
                try {
                    stage.setPointerCapture(e.pointerId);
                } catch (err) {
                    // Browser lama: abaikan, pan tetap jalan lewat pointermove.
                }
            } else if (pointers.size === 2) {
                var p = Array.from(pointers.values());
                pinchStartDist = Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y);
                pinchStartScale = scale;
            }
        });

        stage.addEventListener('pointermove', function (e) {
            if (!pointers.has(e.pointerId)) return;
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

            if (pointers.size === 2 && pinchStartDist > 0) {
                var p = Array.from(pointers.values());
                var dist = Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y);
                zoomTo(pinchStartScale * (dist / pinchStartDist), (p[0].x + p[1].x) / 2, (p[0].y + p[1].y) / 2, false);
                dragMoved = true;
                return;
            }

            if (pointers.size === 1 && dragStart) {
                tx = e.clientX - dragStart.x;
                ty = e.clientY - dragStart.y;
                // Ambang 4px supaya klik biasa (tanpa niat menggeser) tetap menutup viewer.
                if (Math.hypot(tx - dragStart.tx0, ty - dragStart.ty0) > 4) dragMoved = true;
                apply(false);
            }
        });

        function endPointer(e) {
            pointers.delete(e.pointerId);
            if (pointers.size < 2) pinchStartDist = 0;
            if (pointers.size === 0) {
                dragStart = null;
                stage.classList.remove('izv-dragging');
            }
        }

        stage.addEventListener('pointerup', endPointer);
        stage.addEventListener('pointercancel', endPointer);

        // ===== Keyboard =====
        document.addEventListener('keydown', function (e) {
            if (!root.classList.contains('izv-open')) return;

            if (e.key === 'Escape') closeViewer();
            else if (e.key === '+' || e.key === '=') zoomBy(STEP, null, null, true);
            else if (e.key === '-' || e.key === '_') zoomBy(-STEP, null, null, true);
            else if (e.key === '0') reset();
            else if (e.key === 'ArrowLeft') step(-1);
            else if (e.key === 'ArrowRight') step(1);
            else return;

            e.preventDefault();
        });
    })();
</script>

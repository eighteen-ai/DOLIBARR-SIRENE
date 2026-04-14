/* Dolisirene - autocomplete on societe/card.php name field */
(function(){
    'use strict';
    var cfg = window.dolisirene_autocomplete;
    if (!cfg) return;

    function esc(s){ return (s===null||s===undefined)?'':String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }

    function findInput(){
        return document.querySelector('input[name="name"], input[name="nom"]');
    }

    function setVal(selector, value){
        var el = document.querySelector(selector);
        if (!el) return;
        if (el.value && el.value.trim() !== '') return; // don't overwrite user input
        el.value = value || '';
        el.dispatchEvent(new Event('change', {bubbles:true}));
    }

    function setSelectVal(selector, value){
        var el = document.querySelector(selector);
        if (!el || value === null || value === undefined || value === '') return;
        for (var i=0; i<el.options.length; i++){
            if (String(el.options[i].value) === String(value)) {
                el.selectedIndex = i;
                if (window.jQuery) { try { jQuery(el).val(value).trigger('change'); } catch(e){} }
                el.dispatchEvent(new Event('change', {bubbles:true}));
                return;
            }
        }
    }

    function applyResult(r){
        var nameInput = findInput();
        if (nameInput) { nameInput.value = r.name; nameInput.dispatchEvent(new Event('change',{bubbles:true})); }
        setVal('input[name="address"], textarea[name="address"]', [r.address, r.address2].filter(Boolean).join("\n"));
        setVal('input[name="zipcode"]', r.postal_code);
        setVal('input[name="town"]', r.city);
        // Dolibarr: idprof1=SIREN (9), idprof2=SIRET (14), idprof3=APE/NAF
        setVal('input[name="idprof1"]', r.siren);
        setVal('input[name="idprof2"]', r.siret);
        setVal('input[name="idprof3"]', r.ape);
        setVal('input[name="tva_intra"]', r.tva_intra);
        setSelectVal('select[name="forme_juridique_code"]', r.legal_form);
        setSelectVal('select[name="country_id"]', cfg.country_id || 1);
        closeBox();
    }

    var box = null;
    function ensureBox(anchor){
        if (box) return box;
        box = document.createElement('div');
        box.className = 'dolisirene-autoc';
        box.style.position = 'absolute';
        box.style.zIndex = 9999;
        box.style.background = '#fff';
        box.style.border = '1px solid #d0d7de';
        box.style.borderRadius = '6px';
        box.style.boxShadow = '0 2px 8px rgba(0,0,0,.12)';
        box.style.maxHeight = '320px';
        box.style.overflow = 'auto';
        box.style.minWidth = '360px';
        box.style.fontSize = '.92em';
        document.body.appendChild(box);
        return box;
    }
    function positionBox(anchor){
        var r = anchor.getBoundingClientRect();
        box.style.left = (window.scrollX + r.left) + 'px';
        box.style.top  = (window.scrollY + r.bottom + 2) + 'px';
        box.style.width = Math.max(r.width, 360) + 'px';
    }
    function closeBox(){ if (box) { box.style.display = 'none'; box.innerHTML=''; } }

    function renderList(anchor, list){
        ensureBox(anchor);
        positionBox(anchor);
        if (!list.length) {
            box.innerHTML = '<div class="dolisirene-autoc-empty" style="padding:10px;color:#666;">'+esc(cfg.labels.no_results)+'</div>';
            box.style.display = 'block';
            return;
        }
        var html = '<div class="dolisirene-autoc-header" style="padding:6px 10px;background:#f4f6f8;color:#666;font-size:.85em;">'+esc(cfg.labels.source)+'</div>';
        list.forEach(function(r, i){
            html += '<div class="dolisirene-autoc-item" data-idx="'+i+'" style="padding:8px 10px;border-top:1px solid #eee;cursor:pointer;">'
                 +  '<div style="font-weight:600;">'+esc(r.name)+'</div>'
                 +  '<div style="color:#666;">'+esc(r.postal_code)+' '+esc(r.city)+' &middot; SIRET '+esc(r.siret)+'</div>'
                 +  '</div>';
        });
        box.innerHTML = html;
        box.style.display = 'block';
        Array.prototype.forEach.call(box.querySelectorAll('.dolisirene-autoc-item'), function(el){
            el.addEventListener('mousedown', function(ev){
                ev.preventDefault();
                var idx = parseInt(el.getAttribute('data-idx'), 10);
                applyResult(list[idx]);
            });
            el.addEventListener('mouseover', function(){ el.style.background = '#eef4fb'; });
            el.addEventListener('mouseout',  function(){ el.style.background = ''; });
        });
    }

    var debounceTimer = null;
    function scheduleSearch(anchor){
        clearTimeout(debounceTimer);
        var q = anchor.value.trim();
        if (q.length < 3) { closeBox(); return; }
        debounceTimer = setTimeout(function(){ doSearch(anchor, q); }, 350);
    }

    function doSearch(anchor, q){
        ensureBox(anchor);
        positionBox(anchor);
        box.innerHTML = '<div style="padding:10px;color:#666;">'+esc(cfg.labels.searching)+'</div>';
        box.style.display = 'block';

        var fd = new FormData();
        fd.append('token', cfg.token);
        fd.append('q', q);
        // Prefill postal / city from form if user already typed them
        var cpEl = document.querySelector('input[name="zipcode"]');
        var cityEl = document.querySelector('input[name="town"]');
        if (cpEl && cpEl.value) fd.append('cp', cpEl.value);
        if (cityEl && cityEl.value) fd.append('city', cityEl.value);

        fetch(cfg.ajax_url, { method:'POST', body: fd, credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                var list = (data && data.results) || [];
                renderList(anchor, list);
            })
            .catch(function(e){
                box.innerHTML = '<div style="padding:10px;color:#c0392b;">'+esc(String(e))+'</div>';
            });
    }

    function wire(){
        var input = findInput();
        if (!input || input.dataset.dolisireneBound) return;
        input.dataset.dolisireneBound = '1';
        input.setAttribute('autocomplete','off');
        input.addEventListener('input', function(){ scheduleSearch(input); });
        input.addEventListener('focus', function(){ if (input.value.trim().length >= 3) scheduleSearch(input); });
        input.addEventListener('blur', function(){ setTimeout(closeBox, 200); });
        window.addEventListener('resize', function(){ if (box && box.style.display !== 'none') positionBox(input); });
        window.addEventListener('scroll', function(){ if (box && box.style.display !== 'none') positionBox(input); }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wire);
    } else {
        wire();
    }
})();

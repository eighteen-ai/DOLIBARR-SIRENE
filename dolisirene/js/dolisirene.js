/* Dolisirene - search UI + create tiers flow */
(function(){
    'use strict';

    var cfg = window.dolisirene_config || null;
    if (!cfg) return;

    function $(id){ return document.getElementById(id); }

    function esc(s){
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    function setStatus(msg, cls){
        var el = $('dolisirene-status');
        if (!el) return;
        el.textContent = msg || '';
        el.className = 'dolisirene-status' + (cls ? ' dolisirene-status-'+cls : '');
    }

    function renderResults(list){
        var c = $('dolisirene-results');
        if (!c) return;
        if (!list.length) { c.innerHTML = ''; setStatus(cfg.labels.no_results, 'warn'); return; }
        setStatus('');
        var html = '';
        list.forEach(function(r){
            var addr = [r.address, r.address2].filter(Boolean).join(', ');
            var inactive = r.active ? '' : '<span class="dolisirene-badge-inactive">'+esc(cfg.labels.inactive)+'</span>';
            html += '<div class="dolisirene-card">';
            html += '<div class="dolisirene-card-head">';
            html += '<h3>'+esc(r.name)+' '+inactive+'</h3>';
            html += '<button type="button" class="button button-add" data-siret="'+esc(r.siret)+'" data-idx="'+esc(r._idx)+'">'+esc(cfg.labels.create)+'</button>';
            html += '</div>';
            html += '<div class="dolisirene-card-body">';
            html += '<div>'+esc(addr)+'</div>';
            html += '<div>'+esc(r.postal_code)+' '+esc(r.city)+'</div>';
            html += '<div class="dolisirene-meta">';
            html += '<span>'+esc(cfg.labels.siret)+': <code>'+esc(r.siret)+'</code></span>';
            if (r.tva_intra) html += '<span>'+esc(cfg.labels.tva)+': <code>'+esc(r.tva_intra)+'</code></span>';
            if (r.ape) html += '<span>'+esc(cfg.labels.ape)+': <code>'+esc(r.ape)+'</code></span>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });
        c.innerHTML = html;

        Array.prototype.forEach.call(c.querySelectorAll('button[data-idx]'), function(btn){
            btn.addEventListener('click', function(){
                var idx = parseInt(btn.getAttribute('data-idx'), 10);
                var r = list[idx];
                if (!r) return;
                if (cfg.mode === 'complete') {
                    completeSociete(r);
                } else {
                    createSociete(r);
                }
            });
        });
    }

    function createSociete(r){
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = cfg.create_url;
        var fields = {
            action: 'create',
            token: cfg.token,
            name: r.name,
            address: [r.address, r.address2].filter(Boolean).join("\n"),
            zipcode: r.postal_code,
            town: r.city,
            country_id: 1,
            tva_intra: r.tva_intra,
            idprof1: r.siret,
            idprof2: r.ape,
            forme_juridique_code: r.legal_form,
            client: 2,
            fournisseur: 0
        };
        Object.keys(fields).forEach(function(k){
            var i = document.createElement('input');
            i.type = 'hidden'; i.name = k; i.value = fields[k] || '';
            form.appendChild(i);
        });
        document.body.appendChild(form);
        form.submit();
    }

    function completeSociete(r){
        if (!cfg.socid) return;
        setStatus(cfg.labels.searching || 'Updating...', 'info');
        var fd = new FormData();
        fd.append('token', cfg.token);
        fd.append('socid', cfg.socid);
        fd.append('siret', r.siret);
        fd.append('overwrite', cfg.overwrite ? 1 : 0);
        fetch(cfg.complete_url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(res){ return res.json(); })
            .then(function(data){
                if (data.error) { setStatus(data.error, 'err'); return; }
                setStatus((cfg.labels.completed || 'OK') + ': ' + (data.updated || []).join(', '), 'ok');
                setTimeout(function(){ window.location.reload(); }, 900);
            })
            .catch(function(e){ setStatus(String(e), 'err'); });
    }

    function doSearch(){
        var q = ($('dolisirene-q') || {}).value || '';
        var cp = ($('dolisirene-cp') || {}).value || '';
        var city = ($('dolisirene-city') || {}).value || '';
        q = q.trim();
        if (q.length < 2) { setStatus(cfg.labels.min_chars, 'warn'); return; }

        setStatus(cfg.labels.searching, 'info');
        var fd = new FormData();
        fd.append('token', cfg.token);
        fd.append('q', q);
        fd.append('cp', cp);
        fd.append('city', city);

        fetch(cfg.ajax_url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(res){ return res.json(); })
            .then(function(data){
                if (data.error) { setStatus(data.error, 'err'); return; }
                var list = data.results || [];
                list.forEach(function(r, i){ r._idx = i; });
                renderResults(list);
            })
            .catch(function(e){ setStatus(String(e), 'err'); });
    }

    document.addEventListener('DOMContentLoaded', function(){
        var btn = $('dolisirene-btn');
        if (btn) btn.addEventListener('click', doSearch);
        ['dolisirene-q','dolisirene-cp','dolisirene-city'].forEach(function(id){
            var el = $(id);
            if (el) el.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); doSearch(); } });
        });

        // Auto-run in "complete" mode (on societe card)
        if (cfg.mode === 'complete' && cfg.autosearch) {
            var q = $('dolisirene-q'); if (q) q.value = cfg.autosearch.q || '';
            var cp = $('dolisirene-cp'); if (cp) cp.value = cfg.autosearch.cp || '';
            var city = $('dolisirene-city'); if (city) city.value = cfg.autosearch.city || '';
            doSearch();
        }
    });
})();

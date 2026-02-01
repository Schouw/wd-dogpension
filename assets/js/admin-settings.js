(function(){
    function addRow(tableId, emptyRowHtml) {
        const tbody = document.querySelector('#'+tableId+' tbody');
        if (!tbody) return;
        const tr = tbody.querySelector('tr:last-child');
        const clone = tr.cloneNode(true);
        clone.querySelectorAll('input').forEach(inp => inp.value = '');
        tbody.appendChild(clone);
        renumber(tableId);
    }
    function renumber(tableId) {
        const tbody = document.querySelector('#'+tableId+' tbody');
        if (!tbody) return;
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((tr,i) => {
            tr.querySelectorAll('input').forEach(inp => {
                inp.name = inp.name.replace(/\[\d+\]/, '['+i+']');
            });
        });
    }
    document.addEventListener('click', function(e){
        if (e.target.matches('#wddp-special-add')) { e.preventDefault(); addRow('wddp-special-periods'); }
        if (e.target.matches('#wddp-closed-add'))  { e.preventDefault(); addRow('wddp-closed-periods'); }
        if (e.target.matches('#wddp-slots-add'))   { e.preventDefault();
            e.preventDefault();
            const tbody = document.querySelector('#mhhc-slots tbody');
            if (!tbody) return;
            const tr = document.createElement('tr');
            tr.innerHTML = `<td><input type="text" name="wddp_hp_slots[]" value="" class="regular-text"></td>
                            <td><a href="#" class="button wddp-row-del">–</a></td>`;
            tbody.appendChild(tr);
        }
        if (e.target.matches('.wddp-row-del')) {
            e.preventDefault();
            const tr = e.target.closest('tr');
            const tbody = tr && tr.parentNode;
            if (tbody && tbody.children.length > 1) {
                tr.remove();
                const table = tbody.closest('table');
                if (table) {
                    if (table.id === 'wddp-special-periods' || table.id === 'wddp-closed-periods') {
                        renumber(table.id); // slots renumbers ikke – bruger []
                    }
                }
            }
        }
    });
})();

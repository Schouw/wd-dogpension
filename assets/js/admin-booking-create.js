document.addEventListener('DOMContentLoaded', function () {

    const fromInput = document.querySelector('input[name="dropoff_date"]');
    const toInput   = document.querySelector('input[name="pickup_date"]');
    const dogsWrap  = document.getElementById('wddp-dog-fields');
    const recalcPriceBtn = document.getElementById('recalculate-price');

    const priceEl   = document.getElementById('calculated-price');
    const loadingEl = document.getElementById('price-loading');

    const manualCheckbox = document.getElementById('manual_price_enable');
    const manualInput    = document.getElementById('manual_price');

    const addBtn = document.getElementById('add-dog');
    const maxDogs = parseInt(addBtn?.dataset.max || '2', 10);

    // ---------- Helpers ----------

    function getDogCount() {
        return dogsWrap.querySelectorAll('.dog-block').length;
    }

    function reindexDogs() {
        dogsWrap.querySelectorAll('.dog-block').forEach((block, i) => {
            block.dataset.index = i;
            block.querySelector('strong').innerText = `Hund ${i + 1}`;

            block.querySelectorAll('input, textarea').forEach(el => {
                el.name = el.name.replace(/dogs\[\d+]/, `dogs[${i}]`);
            });
        });
    }

    function toggleManualPrice() {
        manualInput.disabled = !manualCheckbox.checked;
    }

    // ---------- Price ----------

    let priceRequestId = 0;

    function updatePrice() {
        const from = fromInput.value;
        const to   = toInput.value;
        const dogs = getDogCount();

        if (!from || !to || dogs < 1) {
            priceEl.innerText = '—';
            return;
        }

        const reqId = ++priceRequestId;

        loadingEl.style.display = 'inline-block';
        priceEl.style.display = 'none';

        fetch(wddpPriceCalc.api_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wddpPriceCalc.nonce
            },
            body: JSON.stringify({
                fromDate: from,
                toDate: to,
                dogsCount: dogs
            })
        })
            .then(r => r.json())
            .then(data => {
                if (reqId !== priceRequestId) return; // ignore stale response

                if (typeof data.total === 'number') {
                    const val = data.total.toFixed(2);
                    priceEl.innerText = `${val} kr.`;
                    if (!manualCheckbox.checked) {
                        manualInput.value = val;
                    }
                } else {
                    priceEl.innerText = '—';
                }
            })
            .catch(() => {
                priceEl.innerText = '—';
            })
            .finally(() => {
                if (reqId === priceRequestId) {
                    loadingEl.style.display = 'none';
                    priceEl.style.display = 'inline';
                }
            });
    }

    // ---------- Dogs ----------

    addBtn.addEventListener('click', () => {
        const count = getDogCount();
        if (count >= maxDogs) {
            alert(`Du kan maksimalt tilføje ${maxDogs} hund(e).`);
            return;
        }

        const div = document.createElement('div');
        div.className = 'dog-block';
        div.innerHTML = `
            <strong>Hund ${count + 1}</strong>
            <p><label>Navn<br><input type="text" name="dogs[${count}][name]" required></label></p>
            <p><label>Race<br><input type="text" name="dogs[${count}][breed]" required></label></p>
            <p><label>Alder<br><input type="text" name="dogs[${count}][age]" required></label></p>
            <p><label>Vægt<br><input type="number" step="0.1" name="dogs[${count}][weight]" required></label></p>
            <p><label>Noter<br><textarea name="dogs[${count}][notes]"></textarea></label></p>
            <p><button type="button" class="button remove-dog">Fjern hund</button></p>
        `;

        div.querySelector('.remove-dog').addEventListener('click', () => {
            div.remove();
            reindexDogs();
            updatePrice();
        });

        dogsWrap.appendChild(div);
        updatePrice();
    });

    // ---------- Events ----------

    fromInput.addEventListener('change', updatePrice);
    toInput.addEventListener('change', updatePrice);
    manualCheckbox.addEventListener('change', toggleManualPrice);
    recalcPriceBtn.addEventListener('click', updatePrice);


    toggleManualPrice();
    updatePrice();

    // make sure price is calculated on page load
    window.addEventListener('load', () => {
        updatePrice();
    });
});


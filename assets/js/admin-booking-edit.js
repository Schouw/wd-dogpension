document.addEventListener("DOMContentLoaded", function() {

    const fromInput = document.getElementById("from_date");
    const toInput = document.getElementById("to_date");
    const dogsWrapper = document.getElementById("wddp-dog-fields");
    const calculatedPriceEl = document.getElementById("calculated-price");

    const manualCheckbox = document.getElementById("manual_price_enable");
    const manualInput = document.getElementById("manual_price");

    const addBtn = document.getElementById("add-dog");
    const maxDogs = parseInt(addBtn.dataset.max || 5);

    function updateManualPriceState() {
        manualInput.disabled = !manualCheckbox.checked;
    }

    function getDogCount() {
        return dogsWrapper.querySelectorAll(".dog-block").length;
    }

    function updatePrice() {
        const fromDate = fromInput.value;
        const toDate = toInput.value;
        const dogCount = getDogCount();

        const loadingEl = document.getElementById("price-loading");
        const priceEl = document.getElementById("calculated-price");

        if (!fromDate || !toDate || dogCount === 0) {
            priceEl.innerText = "—";
            if (loadingEl) loadingEl.style.display = "none";
            return;
        }

        if (loadingEl) loadingEl.style.display = "inline-block";
        priceEl.style.display = "none";

        fetch(wddpPriceCalc.api_url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": wddpPriceCalc.nonce
            },
            body: JSON.stringify({
                fromDate: fromDate,
                toDate: toDate,
                dogsCount: dogCount
            })
        })
            .then(res => res.json())
            .then(data => {
                const formatter = new Intl.NumberFormat('da-DK', {
                    style: 'decimal',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                if (data && data.total !== undefined) {
                    priceEl.innerText = formatter.format(data.total) + " kr.";
                } else {
                    priceEl.innerText = "- (Ugyldig data)";
                }
            })
            .catch(() => {
                priceEl.innerText = "—";
            })
            .finally(() => {
                if (loadingEl) loadingEl.style.display = "none";
                priceEl.style.display = "inline";
            });
    }


    function createDogBlock(index) {
        const div = document.createElement("div");
        div.className = "dog-block";
        div.dataset.index = index;
        div.style.border = "1px solid #ccc";
        div.style.padding = "1em";
        div.style.marginBottom = "1em";

        div.innerHTML = `
            <strong>Hund ${index + 1}</strong>
            <p><label>Navn<br><input type="text" name="dogs[${index}][name]" required></label></p>
            <p><label>Race<br><input type="text" name="dogs[${index}][breed]" required></label></p>
            <p><label>Alder<br><input type="number" name="dogs[${index}][age]" required></label></p>
            <p><label>Vægt<br><input type="number" step="0.1" name="dogs[${index}][weight]" required></label></p>
            <p><label>Noter<br><textarea name="dogs[${index}][notes]"></textarea></label></p>
            <p><button type="button" class="button remove-dog">Fjern hund</button></p>
        `;

        return div;
    }

    addBtn.addEventListener("click", function () {
        const index = getDogCount();
        if (index >= maxDogs) {
            alert("Du kan maksimalt tilføje " + maxDogs + " hund(e).");
            return;
        }

        const block = createDogBlock(index);
        dogsWrapper.appendChild(block);
        updatePrice();
    });

    dogsWrapper.addEventListener("click", function(e) {
        if (e.target.classList.contains("remove-dog")) {
            const block = e.target.closest(".dog-block");
            block.remove();

            const remaining = getDogCount();
            if (remaining === 0) {
                alert("Der skal være mindst én hund i bookingen.");
                addBtn.click();
            }

            updatePrice();
        }
    });

    fromInput.addEventListener("change", updatePrice);
    toInput.addEventListener("change", updatePrice);
    dogsWrapper.addEventListener("change", updatePrice);
    manualCheckbox.addEventListener("change", updateManualPriceState);

    updateManualPriceState();
    updatePrice();

    // ✅ VALIDERING VED SUBMIT
    const form = document.getElementById("wddp-edit-booking-form");
    if (form) {
        form.addEventListener("submit", function (e) {
            const from = fromInput.value;
            const to = toInput.value;
            const dogs = dogsWrapper.querySelectorAll(".dog-block");

            let hasError = false;
            let messages = [];

            if (from && to && from > to) {
                hasError = true;
                messages.push("Fra-dato skal være før eller samme som Til-dato.");
            }

            if (!from) {
                hasError = true;
                messages.push("Fra-dato er påkrævet.");
            }
            if (!to) {
                hasError = true;
                messages.push("Til-dato er påkrævet.");
            }

            const validSlots = Array.from(document.querySelectorAll("#arrival_time option")).map(opt => opt.value);
            const arrival = document.getElementById("arrival_time").value;
            const departure = document.getElementById("departure_time").value;

            if (!arrival || !validSlots.includes(arrival)) {
                hasError = true;
                messages.push("Afleveringstidspunkt er ugyldigt.");
            }
            if (!departure || !validSlots.includes(departure)) {
                hasError = true;
                messages.push("Afhentningstidspunkt er ugyldigt.");
            }

            if (dogs.length === 0) {
                hasError = true;
                messages.push("Der skal være mindst én hund.");
            } else {
                dogs.forEach((block, i) => {
                    const name = block.querySelector(`input[name="dogs[${i}][name]"]`)?.value.trim();
                    const breed = block.querySelector(`input[name="dogs[${i}][breed]"]`)?.value.trim();
                    const age = block.querySelector(`input[name="dogs[${i}][age]"]`)?.value;
                    const weight = block.querySelector(`input[name="dogs[${i}][weight]"]`)?.value;

                    if (!name) {
                        hasError = true;
                        messages.push(`Hund ${i + 1}: Navn er påkrævet.`);
                    }
                    if (!breed) {
                        hasError = true;
                        messages.push(`Hund ${i + 1}: Race er påkrævet.`);
                    }
                    if (age === "" || isNaN(parseInt(age))) {
                        hasError = true;
                        messages.push(`Hund ${i + 1}: Alder er påkrævet.`);
                    }
                    if (weight === "" || isNaN(parseFloat(weight))) {
                        hasError = true;
                        messages.push(`Hund ${i + 1}: Vægt er påkrævet.`);
                    }
                });
            }

            if (hasError) {
                e.preventDefault();
                alert("⚠️ Der er fejl i formularen:\n\n" + messages.join("\n"));
            }
        });
    }

});

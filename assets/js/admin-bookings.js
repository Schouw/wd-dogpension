document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.wddp-booking-action');
    buttons.forEach(function (btn) {

        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const action = this.dataset.action;
            const id = this.dataset.id;

            const form = document.getElementById('wddp-booking-action-form');
            form.querySelector('[name="booking_id"]').value = id;

            if (action === 'delete') {
                if (!confirm('Er du sikker på at du vil slette bookingen?')) return;
                form.setAttribute('action', document.getElementById('wddp-action-endpoints').dataset.delete);
                form.submit();
                return;
            }

            form.querySelector('[name="do"]').value = action;

            if (action === 'reject') {
                const note = prompt('Skriv evt. en note til kunden:');
                form.querySelector('[name="notes"]').value = note ?? '';
            }

            form.setAttribute('action', document.getElementById('wddp-action-endpoints').dataset.update);
            form.submit();
        });
    });
});

document.addEventListener("DOMContentLoaded", function(){
    const modal = document.getElementById("wddp-change-modal");
    const overlay = document.getElementById("wddp-change-modal-overlay");
    const content = document.getElementById("wddp-change-modal-content");
    const closeBtn = document.getElementById("wddp-change-modal-close");

    document.querySelectorAll(".wddp-show-history").forEach(el => {
        el.addEventListener("click", function(e) {
            e.preventDefault();
            const html = this.dataset.content;
            content.innerHTML = html;
            modal.style.display = "block";
            overlay.style.display = "block";
        });
    });

    closeBtn.addEventListener("click", function() {
        modal.style.display = "none";
        overlay.style.display = "none";
    });

    overlay.addEventListener("click", function() {
        modal.style.display = "none";
        this.style.display = "none";
    });
});
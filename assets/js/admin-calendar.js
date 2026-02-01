document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("wddp-calendar");

    function createInput(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        return input;
    }


    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        events: wddpCalendarData.events,
        eventClick: function(info) {
            const props = info.event.extendedProps;

            const modal = document.getElementById('wddp-booking-modal');
            const contentEl = modal.querySelector('.modal-content');
            const actionsEl = modal.querySelector('.modal-actions');

            let html = `
        <p><strong>Kunde:</strong> ${props.customer}</p>
        <p><strong>Email:</strong> ${props.email}<br>
        <strong>Telefon:</strong> ${props.phone}</p>
    
        <p><strong>Hunde:</strong><br>
        ${props.dogs.map(d => `<span>${d}</span>`).join('<br>')}
        </p>
    
        <p><strong>Periode:</strong> ${info.event.startStr} → ${info.event.endStr}<br>
        <strong>Aflevering:</strong> ${props.drop_off_time}<br>
        <strong>Afhentning:</strong> ${props.pick_up_time}</p>
    
        ${props.notes ? `<p><strong>Note:</strong> ${props.notes}</p>` : ''}
    `;

            contentEl.innerHTML = html;

            // Tilføj knapper
            actionsEl.innerHTML = ''; // Ryd gamle
            const editUrl = `admin.php?page=wddp_menu-edit-booking&edit=${props.booking_id}`;
            const editBtn = document.createElement('a');
            editBtn.href = editUrl;
            editBtn.className = 'button button-primary';
            editBtn.textContent = 'Rediger';
            actionsEl.appendChild(editBtn);

            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'button button-danger';
            deleteBtn.textContent = 'Slet';
            deleteBtn.addEventListener('click', function () {
                if (confirm('Er du sikker på du vil slette denne booking?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'admin-post.php';

                    form.appendChild(createInput('action', 'wddp_booking_delete'));
                    form.appendChild(createInput('booking_id', props.booking_id));
                    form.appendChild(createInput('wddp_nonce', props.nonce));

                    document.body.appendChild(form);
                    form.submit();
                }
            });
            actionsEl.appendChild(deleteBtn);

            modal.style.display = 'block';
        }

    });

    calendar.render();
});

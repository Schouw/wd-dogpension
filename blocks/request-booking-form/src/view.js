import React from 'react';
import { createRoot } from '@wordpress/element';
import BookingForm from './BookingForm.jsx';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('mhhc_hundepension_form');
    if (container) {
        const root = createRoot(container);
        root.render(<BookingForm />);
    }
});
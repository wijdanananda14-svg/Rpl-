document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const startTime = form.querySelector('[name="start_time"]');
            const endTime = form.querySelector('[name="end_time"]');
            if (startTime?.value && endTime?.value && startTime.value >= endTime.value) {
                event.preventDefault();
                window.alert('Jam selesai harus lebih besar dari jam mulai.');
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Memproses...';
            }
        });
    });
});

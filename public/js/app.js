document.addEventListener('DOMContentLoaded', () => {
    const bookingForm = document.querySelector('.booking-form');
    const startTime = bookingForm?.querySelector('[name="start_time"]');
    const endTime = bookingForm?.querySelector('[name="end_time"]');

    bookingForm?.addEventListener('submit', (event) => {
        if (startTime?.value && endTime?.value && startTime.value >= endTime.value) {
            event.preventDefault();
            window.alert('Jam selesai harus lebih besar dari jam mulai.');
        }
    });
});

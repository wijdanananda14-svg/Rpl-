<form class="booking-form" method="post" action="/booking">
    <label>Lapangan<select name="field_id" required><option value="">Pilih lapangan</option></select></label>
    <label>Nama pemesan<input type="text" name="customer_name" required></label>
    <label>Email<input type="email" name="customer_email" required></label>
    <label>Tanggal<input type="date" name="booking_date" required></label>
    <div class="time-row"><label>Mulai<input type="time" name="start_time" required></label><label>Selesai<input type="time" name="end_time" required></label></div>
    <button type="submit">Buat reservasi</button>
</form>

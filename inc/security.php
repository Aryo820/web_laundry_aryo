<?php
// Header keamanan dasar untuk semua halaman aplikasi.
// Dipanggil sebelum output apa pun supaya header ikut terkirim.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

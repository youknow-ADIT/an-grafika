    <!-- Footer Section -->
    <footer class="bg-slate-950 text-slate-400 mt-auto border-t border-slate-800 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                
                <!-- Col 1: About & Info -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white p-1 flex items-center justify-center shadow-md">
                            <img src="assets/img/logo.png" alt="AN Grafika" class="h-full w-full object-contain">
                        </div>
                        <div>
                            <span class="text-2xl font-black tracking-tight text-white">AN <span class="text-[#C30064]">Grafika</span></span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                        Solusi terpadu percetakan digital (*digital offset printing*), kemasan produk, merchandise, dan studio jasa desain grafis profesional (logo, branding, packaging) dengan teknologi modern.
                    </p>
                    <div class="space-y-1.5 text-xs text-slate-300 pt-2">
                        <p class="flex items-center gap-2">
                            <i data-lucide="clock" class="w-4 h-4 text-pink-400 shrink-0"></i>
                            <span>Buka Senin - Sabtu : 08.00 - 22.00 WIB</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <i data-lucide="phone" class="w-4 h-4 text-blue-400 shrink-0"></i>
                            <span>0857 6009 0065 (WhatsApp Fast Response)</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4 text-slate-400 shrink-0"></i>
                            <span>an.grafika2203@gmail.com</span>
                        </p>
                    </div>
                </div>

                <!-- Col 2: Produk Percetakan -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white">Katalog Cetak</h3>
                    <ul class="space-y-2 text-xs">
                        <li><a href="products.php?cat=kertas-dokumen" class="hover:text-white transition">Kartu Nama & Brosur</a></li>
                        <li><a href="products.php?cat=stiker-label" class="hover:text-white transition">Stiker Vinyl & Cromo</a></li>
                        <li><a href="products.php?cat=spanduk-banner" class="hover:text-white transition">Spanduk Outdoor & Roll-Up</a></li>
                        <li><a href="products.php?cat=kemasan-box" class="hover:text-white transition">Food Pail & Box Kue</a></li>
                        <li><a href="products.php?cat=merchandise-custom" class="hover:text-white transition">Mug, Lanyard & ID Card</a></li>
                    </ul>
                </div>

                <!-- Col 3: Jasa Desain -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white">Jasa Desain Grafis</h3>
                    <ul class="space-y-2 text-xs">
                        <li><a href="design-request.php" class="hover:text-white transition">Desain Logo UMKM & Toko</a></li>
                        <li><a href="design-request.php" class="hover:text-white transition">Desain Kemasan & Label Box</a></li>
                        <li><a href="design-request.php" class="hover:text-white transition">Desain Spanduk & Banner Promo</a></li>
                        <li><a href="design-request.php" class="hover:text-white transition">Desain Brosur & Menu Makanan</a></li>
                        <li><a href="design-request.php" class="text-pink-400 hover:text-pink-300 font-bold flex items-center gap-1">
                            <span>Form Request Desain</span> &rarr;
                        </a></li>
                    </ul>
                </div>

                <!-- Col 4: Layanan & Akses Cepat -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white">Akses Cepat</h3>
                    <ul class="space-y-2 text-xs">
                        <li><a href="track.php" class="text-emerald-400 hover:text-emerald-300 font-bold flex items-center gap-1">
                            <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                            <span>Lacak No. Struk Kasir</span>
                        </a></li>
                        <li><a href="order.php" class="hover:text-white transition">Kalkulator Cetak Online</a></li>
                        <li><a href="login.php" class="hover:text-white transition flex items-center gap-1">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                            <span>Login Portal Pegawai (POS)</span>
                        </a></li>
                        <li><a href="login.php" class="hover:text-white transition">Login Portal Administrator</a></li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Copyright & Badges -->
            <div class="border-t border-slate-800 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y'); ?> AN Grafika. Hak Cipta Dilindungi Undang-Undang.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Action Button (Hijau Khas WhatsApp) -->
    <a href="https://wa.me/6285799990111?text=Halo%20AN%20Grafika,%20saya%20ingin%20tanya%20layanan%20cetak%20dan%20desain" target="_blank" class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 bg-[#25D366] hover:bg-[#20ba59] text-white px-4 py-3 rounded-full shadow-2xl hover:scale-105 transition transform shadow-emerald-500/40 border border-white/20">
        <div class="w-6 h-6 flex items-center justify-center">
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"/></svg>
        </div>
        <span class="text-xs font-bold tracking-wide pr-1 hidden sm:inline">Hubungi AN Grafika</span>
    </a>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>
</body>
</html>

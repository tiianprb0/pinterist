// Nama cache untuk menyimpan GIF yang akan diunduh
const GIF_CACHE_NAME = 'gif-downloads-cache';

// Event 'install' Service Worker
self.addEventListener('install', (event) => {
    console.log('Service Worker: Event install');
    // Memicu Service Worker untuk segera aktif setelah diinstal
    self.skipWaiting();
});

// Event 'activate' Service Worker
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Event activate');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // Hapus cache yang tidak lagi digunakan
                    if (cacheName !== GIF_CACHE_NAME) {
                        console.log('Service Worker: Menghapus cache lama', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Klaim klien agar Service Worker ini segera mengontrol halaman
            return self.clients.claim();
        })
    );
});

// Menerima pesan dari halaman untuk segera mengaktifkan Service Worker
self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Event 'fetch' Service Worker
// Ini adalah inti dari Service Worker download bridge
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Periksa apakah request adalah untuk URL unduhan kita
    // Pathname akan terlihat seperti '/spicette/tools/cv/download/spicette.convert-xxxxxx.gif'
    // Kita mencari path yang mengandung '/download/'
    if (url.pathname.includes('/download/')) {
        console.log('Service Worker: Mengintersep unduhan untuk:', url.pathname);

        event.respondWith(
            (async () => {
                const cache = await caches.open(GIF_CACHE_NAME);
                // event.request.url akan menjadi URL lengkap (misal: https://domain.com/spicette/tools/cv/download/file.gif)
                // Kita perlu memastikan cache.put menggunakan URL yang sama persis
                const cachedResponse = await cache.match(event.request); 

                if (cachedResponse) {
                    console.log('Service Worker: Mengambil Blob dari cache untuk:', url.pathname);
                    const blob = await cachedResponse.blob();
                    
                    // Ekstrak nama file dari URL (misal: 'spicette.convert-xxxxxx.gif')
                    const filename = url.pathname.substring(url.pathname.lastIndexOf('/') + 1);

                    // Buat respons baru dengan header Content-Disposition
                    // Ini akan memicu dialog unduhan di browser
                    return new Response(blob, {
                        headers: {
                            'Content-Type': 'image/gif', // Ubah ke image/gif
                            'Content-Disposition': `attachment; filename="${filename}"` // Memicu unduhan
                        }
                    });
                } else {
                    console.warn('Service Worker: Blob tidak ditemukan di cache untuk:', url.pathname);
                    // Jika Blob tidak ditemukan di cache, mungkin ada kesalahan atau request bukan untuk Blob
                    // Lanjutkan request seperti biasa atau kembalikan 404
                    return new Response('File tidak ditemukan di cache Service Worker.', { status: 404 });
                }
            })()
        );
    } else {
        // Untuk request lain (misalnya, aset statis seperti index.html, CSS, JS),
        // biarkan browser menanganinya secara normal dengan melakukan fetch dari jaringan.
        event.respondWith(fetch(event.request));
    }
});

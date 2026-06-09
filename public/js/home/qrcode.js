import toast from "../toast.js";
import { fetchWithToken, toFormurlenconded } from "../utils.js";

const QRCodeScannerModal = document.getElementById("qrcode-scanner-modal");

// 🎯 DIUBAH KE 'shown': Menunggu animasi modal Bootstrap selesai terbuka 100% baru scanner digambar
QRCodeScannerModal.addEventListener("shown.bs.modal", (event) => {
    const isEnter = event.relatedTarget.dataset.isEnter == "1";

    async function onScanSuccess(code) {
        // Hentikan aktivitas scanner agar tidak melakukan refresh berkali-kali
        html5QrcodeScanner.clear();

        // Kirim data ke backend Laravel dan tunggu prosesnya selesai
        await handlePresence(isEnter ? enterPresenceUrl : outPresenceUrl, code);

        // Beri jeda 1.5 detik agar user bisa membaca pesan toast sukses/gagal baru reload
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }

    // Bersihkan isi div reader terlebih dahulu untuk menghindari penumpukan elemen
    document.getElementById("reader").innerHTML = "";

    // Inisialisasi library UI Scanner bawaan
    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
            rememberLastUsedCamera: false,
            // 💡 SAKTI: Memaksa library menampilkan opsi scan file/kamera secara seimbang
            supportedScanTypes: [
                Html5QrcodeScanType.SCAN_TYPE_CAMERA,
                Html5QrcodeScanType.SCAN_TYPE_FILE
            ]
        },
        /* verbose= */ false
    );

    html5QrcodeScanner.render(onScanSuccess);

    // 🛑 PENGAMAN: Jika modal ditutup paksa oleh user, matikan mesin scannernya agar laptop tidak lemot
    QRCodeScannerModal.addEventListener("hidden.bs.modal", () => {
        html5QrcodeScanner.clear();
    }, { once: true });
});

async function handlePresence(baseurl, code) {
    try {
        const data = await fetchWithToken(baseurl, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-type": "application/x-www-form-urlencoded; charset=UTF-8",
            },
            body: toFormurlenconded({ code }),
        });

        console.log("Respon dari server:", data);

        let dataToast = {
            title: "QRCode Absensi Pesan",
            body: data.message || "Proses absensi selesai dilakukan.",
            colorClass: toast.TOAST_FAILED,
        };

        if (data.success) {
            dataToast["colorClass"] = toast.TOAST_SUCCESS;
        }

        toast.show(dataToast);

    } catch (error) {
        console.error("Gagal memproses QR Code:", error);
        toast.show({
            title: "QRCode Absensi Pesan",
            body: "Terjadi kesalahan koneksi ke server Laravel.",
            colorClass: toast.TOAST_FAILED
        });
    }
}

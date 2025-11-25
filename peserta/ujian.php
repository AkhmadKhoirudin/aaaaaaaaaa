<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek login peserta
if (!isset($_SESSION['peserta_id'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil data ujian yang sedang berlangsung
$peserta_id = $_SESSION['peserta_id'];
$ujian_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ujian_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Cek apakah peserta sudah memiliki sesi ujian
$stmt = $pdo->prepare("SELECT * FROM sesi_ujian WHERE peserta_id = ? AND ujian_id = ?");
$stmt->execute([$peserta_id, $ujian_id]);
$sesi_ujian = $stmt->fetch();

if (!$sesi_ujian) {
    // Buat sesi ujian baru
    $stmt = $pdo->prepare("INSERT INTO sesi_ujian (peserta_id, ujian_id, waktu_mulai, status) VALUES (?, ?, NOW(), 'sedang_ujian')");
    $stmt->execute([$peserta_id, $ujian_id]);
    $sesi_id = $pdo->lastInsertId();
} else {
    $sesi_id = $sesi_ujian['id'];
}

// Ambil data ujian
$stmt = $pdo->prepare("SELECT u.*, p.nama as paket_nama, p.durasi, p.jumlah_soal 
                      FROM ujian u 
                      JOIN paket_soal p ON u.paket_soal_id = p.id 
                      WHERE u.id = ?");
$stmt->execute([$ujian_id]);
$ujian = $stmt->fetch();

if (!$ujian) {
    header("Location: dashboard.php");
    exit();
}

// Hitung waktu tersisa
$waktu_mulai = new DateTime($sesi_ujian['waktu_mulai'] ?? date('Y-m-d H:i:s'));
$durasi_menit = $ujian['durasi'];
$waktu_selesai = clone $waktu_mulai;
$waktu_selesai->add(new DateInterval('PT' . $durasi_menit . 'M'));

$sekarang = new DateTime();
$sisa_waktu = $waktu_selesai->getTimestamp() - $sekarang->getTimestamp();

if ($sisa_waktu <= 0) {
    // Waktu habis, submit otomatis
    header("Location: submit_ujian.php?sesi_id=" . $sesi_id);
    exit();
}

// Ambil soal yang sudah diacak
$stmt = $pdo->prepare("SELECT soal_id FROM urutan_soal WHERE sesi_ujian_id = ? ORDER BY urutan");
$stmt->execute([$sesi_id]);
$urutan_soal = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

if (empty($urutan_soal)) {
    // Acak soal untuk pertama kali
    $stmt = $pdo->prepare("SELECT id FROM soal WHERE paket_soal_id = ? ORDER BY RAND()");
    $stmt->execute([$ujian['paket_soal_id']]);
    $soal_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    foreach ($soal_ids as $index => $soal_id) {
        $stmt = $pdo->prepare("INSERT INTO urutan_soal (sesi_ujian_id, soal_id, urutan) VALUES (?, ?, ?)");
        $stmt->execute([$sesi_id, $soal_id, $index + 1]);
    }
    $urutan_soal = $soal_ids;
}

// Ambil soal saat ini
$nomor_soal = isset($_GET['nomor']) ? (int)$_GET['nomor'] : 1;
if ($nomor_soal < 1 || $nomor_soal > count($urutan_soal)) {
    $nomor_soal = 1;
}

$soal_id = $urutan_soal[$nomor_soal - 1];
$stmt = $pdo->prepare("SELECT * FROM soal WHERE id = ?");
$stmt->execute([$soal_id]);
$soal = $stmt->fetch();

// Ambil jawaban yang sudah disimpan
$stmt = $pdo->prepare("SELECT jawaban FROM jawaban_peserta WHERE sesi_ujian_id = ? AND soal_id = ?");
$stmt->execute([$sesi_id, $soal_id]);
$jawaban = $stmt->fetchColumn();

// Hitung progress
$total_soal = count($urutan_soal);
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT soal_id) FROM jawaban_peserta WHERE sesi_ujian_id = ?");
$stmt->execute([$sesi_id]);
$terjawab = $stmt->fetchColumn();
$progress = ($terjawab / $total_soal) * 100;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian - <?= htmlspecialchars($ujian['nama']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/peserta.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .ujian-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .timer-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .soal-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .navigation-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .soal-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #495057;
            text-align: center;
            line-height: 40px;
            margin: 2px;
            cursor: pointer;
            font-weight: bold;
        }
        .soal-number.answered {
            background: #28a745;
            color: white;
        }
        .soal-number.current {
            background: #007bff;
            color: white;
        }
        .soal-number:hover {
            transform: scale(1.1);
            transition: transform 0.2s;
        }
        .jawaban-option {
            padding: 15px;
            margin: 10px 0;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .jawaban-option:hover {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .jawaban-option.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }
        .progress-bar {
            height: 8px;
            border-radius: 10px;
        }
        .anti-cheat-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            color: white;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            font-size: 24px;
        }
        .soal-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Anti-cheat overlay -->
    <div class="anti-cheat-overlay" id="antiCheatOverlay">
        <div class="text-center">
            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
            <h3>Peringatan!</h3>
            <p>Anda telah keluar dari halaman ujian. Silahkan klik untuk melanjutkan.</p>
            <button class="btn btn-primary" onclick="hideAntiCheat()">Kembali ke Ujian</button>
        </div>
    </div>

    <div class="ujian-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><?= htmlspecialchars($ujian['nama']) ?></h2>
                <p class="text-muted">Paket: <?= htmlspecialchars($ujian['paket_nama']) ?></p>
            </div>
            <div class="col-md-4">
                <div class="timer-card text-center">
                    <h5><i class="fas fa-clock"></i> Sisa Waktu</h5>
                    <h2 id="timerDisplay">--:--:--</h2>
                    <small id="warningText" class="d-none">Waktu hampir habis!</small>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span>Progress: <?= $terjawab ?>/<?= $total_soal ?> soal</span>
                            <span><?= round($progress) ?>%</span>
                        </div>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-success" style="width: <?= $progress ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Soal -->
            <div class="col-md-8">
                <div class="soal-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Soal <?= $nomor_soal ?> dari <?= $total_soal ?></h5>
                        <span class="badge bg-primary"><?= $soal['jenis_soal'] == 'pilihan_ganda' ? 'Pilihan Ganda' : 'Essai' ?></span>
                    </div>

                    <div class="soal-content">
                        <?php if ($soal['gambar']): ?>
                            <img src="../uploads/soal/<?= $soal['gambar'] ?>" alt="Gambar Soal" class="soal-image">
                        <?php endif; ?>

                        <div class="mb-4">
                            <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                        </div>

                        <?php if ($soal['jenis_soal'] == 'pilihan_ganda'): ?>
                            <div class="jawaban-container">
                                <?php
                                $pilihan = ['A' => $soal['pilihan_a'], 'B' => $soal['pilihan_b'], 'C' => $soal['pilihan_c'], 'D' => $soal['pilihan_d'], 'E' => $soal['pilihan_e']];
                                foreach ($pilihan as $key => $value):
                                    if ($value):
                                ?>
                                    <div class="jawaban-option <?= $jawaban == $key ? 'selected' : '' ?>" 
                                         onclick="selectJawaban('<?= $key ?>')">
                                        <strong><?= $key ?>.</strong> <?= htmlspecialchars($value) ?>
                                        <input type="radio" name="jawaban" value="<?= $key ?>" 
                                               <?= $jawaban == $key ? 'checked' : '' ?> style="display: none;">
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label for="jawabanEssai" class="form-label">Jawaban Anda:</label>
                                <textarea class="form-control" id="jawabanEssai" rows="5" 
                                          onblur="saveJawabanEssai(this.value)"><?= htmlspecialchars($jawaban ?? '') ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button class="btn btn-secondary" onclick="previousSoal()" <?= $nomor_soal == 1 ? 'disabled' : '' ?>>
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </button>
                        <button class="btn btn-primary" onclick="nextSoal()" <?= $nomor_soal == $total_soal ? 'disabled' : '' ?>>
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="col-md-4">
                <div class="navigation-card">
                    <h6 class="mb-3">Navigasi Soal</h6>
                    <div class="d-flex flex-wrap">
                        <?php for ($i = 1; $i <= $total_soal; $i++): ?>
                            <?php
                            $stmt = $pdo->prepare("SELECT j.id FROM jawaban_peserta j 
                                                 JOIN soal s ON j.soal_id = s.id 
                                                 JOIN urutan_soal u ON s.id = u.soal_id 
                                                 WHERE j.sesi_ujian_id = ? AND u.urutan = ?");
                            $stmt->execute([$sesi_id, $i]);
                            $is_answered = $stmt->fetch();
                            ?>
                            <div class="soal-number <?= $is_answered ? 'answered' : '' ?> <?= $i == $nomor_soal ? 'current' : '' ?>" 
                                 onclick="goToSoal(<?= $i ?>)">
                                <?= $i ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning" onclick="raguRagu()">
                            <i class="fas fa-question-circle"></i> Ragu-ragu
                        </button>
                        <button class="btn btn-success" onclick="selesaiUjian()">
                            <i class="fas fa-check-circle"></i> Selesai Ujian
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Anti-cheat libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="anti_cheat.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let sisaWaktu = <?= $sisa_waktu ?>;
        let sesiId = <?= $sesi_id ?>;
        let soalId = <?= $soal['id'] ?>;
        let autoSaveInterval;

        // Timer countdown
        function updateTimer() {
            if (sisaWaktu <= 0) {
                selesaiUjian();
                return;
            }

            const hours = Math.floor(sisaWaktu / 3600);
            const minutes = Math.floor((sisaWaktu % 3600) / 60);
            const seconds = sisaWaktu % 60;

            document.getElementById('timerDisplay').textContent =
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            // Warning when less than 5 minutes
            if (sisaWaktu <= 300) {
                document.getElementById('warningText').classList.remove('d-none');
                document.getElementById('timerDisplay').style.color = '#ff6b6b';
            }

            sisaWaktu--;
        }

        // Auto save jawaban
        function autoSave() {
            const jawaban = document.querySelector('input[name="jawaban"]:checked')?.value ||
                           document.getElementById('jawabanEssai')?.value;
            
            if (jawaban) {
                fetch('save_jawaban.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `sesi_id=${sesiId}&soal_id=${soalId}&jawaban=${encodeURIComponent(jawaban)}`
                });
            }
        }

        // Select jawaban
        function selectJawaban(jawaban) {
            document.querySelectorAll('.jawaban-option').forEach(opt => opt.classList.remove('selected'));
            event.target.closest('.jawaban-option').classList.add('selected');
            
            fetch('save_jawaban.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `sesi_id=${sesiId}&soal_id=${soalId}&jawaban=${jawaban}`
            }).then(() => {
                location.reload();
            });
        }

        // Save jawaban esai
        function saveJawabanEssai(jawaban) {
            fetch('save_jawaban.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `sesi_id=${sesiId}&soal_id=${soalId}&jawaban=${encodeURIComponent(jawaban)}`
            });
        }

        // Navigation
        function previousSoal() {
            const current = <?= $nomor_soal ?>;
            if (current > 1) {
                window.location.href = `ujian.php?id=<?= $ujian_id ?>&nomor=${current - 1}`;
            }
        }

        function nextSoal() {
            const current = <?= $nomor_soal ?>;
            const total = <?= $total_soal ?>;
            if (current < total) {
                window.location.href = `ujian.php?id=<?= $ujian_id ?>&nomor=${current + 1}`;
            }
        }

        function goToSoal(nomor) {
            window.location.href = `ujian.php?id=<?= $ujian_id ?>&nomor=${nomor}`;
        }

        function raguRagu() {
            // Toggle ragu-ragu status
            fetch('toggle_ragu.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `sesi_id=${sesiId}&soal_id=${soalId}`
            }).then(() => {
                location.reload();
            });
        }

        function selesaiUjian() {
            if (confirm('Apakah Anda yakin ingin menyelesaikan ujian?')) {
                window.location.href = `submit_ujian.php?sesi_id=${sesiId}`;
            }
        }

        // Initialize
        updateTimer();
        setInterval(updateTimer, 1000);
        autoSaveInterval = setInterval(autoSave, 30000); // Auto save every 30 seconds

        // Before unload
        window.addEventListener('beforeunload', function(e) {
            if (!e.target.submitting) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
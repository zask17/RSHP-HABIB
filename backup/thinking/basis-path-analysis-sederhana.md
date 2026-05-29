# Analisis Basis Path & Cyclomatic Complexity — Versi Sederhana

**Project:** RSHP Habib (Laravel App)  
**Analyst:** Pi Coding Agent  
**Tanggal:** 2026-05-28

---

## Daftar Isi

1. [Fungsi yang Dipilih](#fungsi-yang-dipilih)
2. [Fungsi 1: RoleController::addRole()](#fungsi-1-rolecontrolleraddrole)
   - [Source Code](#source-code-f1)
   - [Pseudocode](#pseudocode-f1)
   - [Control Flow Graph (CFG)](#cfg-f1)
   - [Cyclomatic Complexity](#complexity-f1)
   - [Test Case](#test-case-f1)
3. [Fungsi 2: ProfileController::show()](#fungsi-2-profilecontrollershow)
   - [Source Code](#source-code-f2)
   - [Pseudocode](#pseudocode-f2)
   - [Control Flow Graph (CFG)](#cfg-f2)
   - [Cyclomatic Complexity](#complexity-f2)
   - [Test Case](#test-case-f2)
4. [Ringkasan](#ringkasan)

---

## A. Objek Pengujian

Objek pengujian pada dokumen ini adalah dua fungsi dari aplikasi **RSHP Habib (Laravel App)** yang dipilih berdasarkan kriteria kesederhanaan dan keberadaan kondisi percabangan (`if`):

| No | Objek | File | Deskripsi |
|---|---|---|---|
| 1 | `RoleController::addRole()` | `app/Http/Controllers/Admin/RoleController.php` | Menambahkan role ke user dengan validasi role profile-based, pengecekan duplikasi, dan reaktivasi role nonaktif |
| 2 | `ProfileController::show()` | `app/Http/Controllers/ProfileController.php` | Menampilkan halaman profil user berdasarkan role yang dimiliki (Pemilik, Dokter, Perawat) |

---

## B. Tujuan Pengujian

Tujuan dari pengujian *Basis Path Testing* ini adalah:

1. **Mengukur kompleksitas siklomatis** dari setiap fungsi untuk mengetahui tingkat kerumitan kode.
2. **Mengidentifikasi seluruh independent path** (jalur independen) pada Control Flow Graph (CFG) masing-masing fungsi.
3. **Memastikan setiap jalur independen** memiliki minimal satu test case yang mencakup skenario **true** dan **false** dari setiap predicate node.
4. **Menjamin cakupan pengujian 100% basis path**, sehingga seluruh kemungkinan alur eksekusi kode telah diuji.
5. **Mendeteksi potensi kesalahan** pada logika percabangan seperti kondisi yang tidak pernah terpenuhi (*dead branch*) atau kondisi yang selalu terpenuhi (*infinite branch*).


---

## C. Ruang Lingkup (Fitur)

Ruang lingkup pengujian meliputi fitur-fitur berikut:

| Fitur | Fungsi Terkait | Deskripsi |
|---|---|---|
| **Manajemen Role User** | `RoleController::addRole()` | Penambahan role ke user, validasi role profile-based, pengecekan duplikasi role, dan reaktivasi role nonaktif |
| **Profil User** | `ProfileController::show()` | Menampilkan profil user berdasarkan role yang aktif (Pemilik, Dokter, Perawat) |

**Di luar ruang lingkup (tidak dicakup):**
- Fungsi CRUD lainnya pada RoleController dan ProfileController
- Autentikasi dan otorisasi (sudah ditangani oleh Laravel middleware)
- Validasi request selain yang terdapat pada fungsi yang diuji
- Pengujian keamanan (security testing)
- Pengujian performa (performance testing)

---

## D. Strategi Pengujian

### Metode: White Box Testing

Pengujian ini menggunakan metode **White Box Testing** (*glass box testing* / *structural testing*), yaitu pengujian yang didasarkan pada analisis struktur internal kode sumber (*source code*). Berbeda dengan *Black Box Testing* yang hanya menguji fungsionalitas dari sisi input/output tanpa melihat kode, White Box Testing memungkinkan penguji untuk:

- Memeriksa **setiap jalur logika** (logical path) di dalam kode.
- Mengidentifikasi **kondisi percabangan** (*branching condition*) dan **alur keputusan** (*decision flow*).
- Menjamin bahwa **semua pernyataan** (*statement*) dan **semua cabang** (*branch*) telah dieksekusi minimal satu kali.
- Mendeteksi **kesalahan logika** seperti *dead code*, *infinite loop*, atau kondisi yang tidak pernah terpenuhi.

### Metode: White Box Testing

Pengujian ini menggunakan metode **White Box Testing** (*glass box testing* / *structural testing*), yaitu pengujian yang didasarkan pada analisis struktur internal kode sumber (*source code*). Berbeda dengan *Black Box Testing* yang hanya menguji fungsionalitas dari sisi input/output tanpa melihat kode, White Box Testing memungkinkan penguji untuk:

- Memeriksa **setiap jalur logika** (logical path) di dalam kode.
- Mengidentifikasi **kondisi percabangan** (*branching condition*) dan **alur keputusan** (*decision flow*).
- Menjamin bahwa **semua pernyataan** (*statement*) dan **semua cabang** (*branch*) telah dieksekusi minimal satu kali.
- Mendeteksi **kesalahan logika** seperti *dead code*, *infinite loop*, atau kondisi yang tidak pernah terpenuhi.

### Teknik: Basis Path Testing

**Basis Path Testing** adalah teknik White Box Testing yang diperkenalkan oleh **Thomas J. McCabe** pada tahun 1976. Teknik ini bertujuan untuk mengukur kompleksitas kode dan memastikan cakupan pengujian yang optimal dengan cara:

- Menggambarkan alur eksekusi program ke dalam **Control Flow Graph (CFG)**.
- Menghitung jumlah **independent path** (jalur independen) menggunakan **Cyclomatic Complexity**.
- Menghasilkan **test case minimum** yang diperlukan untuk mencapai **100% cakupan jalur** tanpa redundansi.

**Independent Path** adalah jalur eksekusi yang membawa setidaknya satu *edge* (sisi) baru yang belum pernah dilewati oleh jalur lain. Dengan menguji semua independent path, penguji dapat memastikan bahwa setiap kemungkinan alur eksekusi kode telah diuji.

### Metrik Kompleksitas: Cyclomatic Complexity

**Cyclomatic Complexity** V(G) adalah metrik perangkat lunak yang mengukur kompleksitas logis dari suatu program. Nilai V(G) menunjukkan jumlah independent path dan tingkat kerumitan pengujian yang dibutuhkan. Semakin tinggi nilai V(G), semakin kompleks kode dan semakin banyak pengujian yang diperlukan.

Perhitungan Cyclomatic Complexity dilakukan menggunakan tiga rumus:
1. **V(G) = P + 1** — dengan P adalah jumlah *predicate node* (node keputusan seperti `if`, `while`, `case`)
2. **V(G) = E - N + 2** — dengan E adalah jumlah *edge* (sisi) dan N adalah jumlah *node* (simpul) pada CFG (digunakan sebagai verifikasi)
3. **V(G) = R** — dengan R adalah jumlah *region* (daerah tertutup) dalam Control Flow Graph. Jumlah region dihitung dari area terbatas (*bounded region*) ditambah 1 area luar (*unbounded region*).

Interpretasi nilai Cyclomatic Complexity:

| Nilai V(G) | Tingkat Risiko | Deskripsi |
|---|---|---|
| 1–10 | **Rendah** | Kode sederhana, mudah diuji dan dipelihara |
| 11–20 | **Sedang** | Kompleksitas moderat, perlu perhatian lebih |
| 21–50 | **Tinggi** | Kode kompleks, berisiko tinggi, perlu refaktorisasi |
| > 50 | **Sangat Tinggi** | Kode tidak stabil, sangat sulit diuji, harus direfaktor |

> **Catatan:** Kedua fungsi yang diuji dalam dokumen ini (RoleController::addRole() dan ProfileController::show()) memiliki V(G) = 4, yang termasuk dalam kategori **risiko rendah** (1–10), sehingga relatif sederhana dan mudah diuji.

Metode utama yang digunakan adalah **White Box Testing**, di mana pengujian dilakukan berdasarkan analisis struktur internal kode sumber (*source code*) dan alur logika (*logical paths*) untuk mengukur kompleksitas serta memastikan seluruh jalur eksekusi telah diuji. Untuk menghasilkan *Test Case* yang efisien dan mendalam, strategi pengujian dibagi berdasarkan karakteristik masing-masing fungsi:

**Basis Path Testing & Cyclomatic Complexity (Khusus Function: RoleController::addRole()):** Diterapkan pada fitur penambahan role ke user karena fungsi ini memiliki tiga percabangan logis (*predicate nodes*): pengecekan role profile-based (P1), pengecekan duplikasi role (P2), dan pengecekan status aktif/nonaktif role (P3). Dengan 3 *predicate node*, nilai Cyclomatic Complexity V(G) = 4, yang berarti terdapat **4 independent path** yang harus diuji. Teknik ini digunakan untuk memetakan seluruh kemungkinan alur eksekusi — mulai dari validasi input, penolakan role profile-based, reaktivasi role nonaktif, hingga pembuatan role baru — dan memastikan setiap *branch* (true/false) dari setiap kondisi telah tercakup oleh *test case* tanpa harus mengeksekusi seluruh kemungkinan skenario secara manual (*exhaustive testing*).

**Basis Path Testing & Cyclomatic Complexity (Khusus Function: ProfileController::show()):** Diterapkan pada fitur menampilkan profil user berdasarkan role karena fungsi ini memiliki tiga percabangan logis (*predicate nodes*): pengecekan profil Pemilik (P1), pengecekan profil Dokter (P2), dan pengecekan profil Perawat (P3). Dengan 3 *predicate node*, nilai Cyclomatic Complexity V(G) = 4, yang berarti terdapat **4 independent path** yang harus diuji. Teknik ini digunakan untuk menguji seluruh kombinasi kepemilikan profil user — apakah user tidak memiliki profil sama sekali, hanya memiliki satu profil, atau memiliki semua profil — dan memastikan sistem merender data profil yang sesuai. Basis Path Testing menjamin bahwa setiap kemungkinan kombinasi status profil dan *role* telah diuji secara sistematis melalui *Control Flow Graph (CFG)*, sehingga tim dapat mendeteksi *bug* pada logika percabangan yang mungkin terlewat jika hanya menguji secara fungsional.

### Teknik: Basis Path Testing

**Basis Path Testing** adalah teknik White Box Testing yang diperkenalkan oleh **Thomas J. McCabe** pada tahun 1976. Teknik ini bertujuan untuk mengukur kompleksitas kode dan memastikan cakupan pengujian yang optimal dengan cara:

- Menggambarkan alur eksekusi program ke dalam **Control Flow Graph (CFG)**.
- Menghitung jumlah **independent path** (jalur independen) menggunakan **Cyclomatic Complexity**.
- Menghasilkan **test case minimum** yang diperlukan untuk mencapai **100% cakupan jalur** tanpa redundansi.

**Independent Path** adalah jalur eksekusi yang membawa setidaknya satu *edge* (sisi) baru yang belum pernah dilewati oleh jalur lain. Dengan menguji semua independent path, penguji dapat memastikan bahwa setiap kemungkinan alur eksekusi kode telah diuji.

### Metrik Kompleksitas: Cyclomatic Complexity

**Cyclomatic Complexity** V(G) adalah metrik perangkat lunak yang mengukur kompleksitas logis dari suatu program. Nilai V(G) menunjukkan jumlah independent path dan tingkat kerumitan pengujian yang dibutuhkan. Semakin tinggi nilai V(G), semakin kompleks kode dan semakin banyak pengujian yang diperlukan.

Perhitungan Cyclomatic Complexity dilakukan menggunakan tiga rumus:
1. **V(G) = P + 1** — dengan P adalah jumlah *predicate node* (node keputusan seperti `if`, `while`, `case`)
2. **V(G) = E - N + 2** — dengan E adalah jumlah *edge* (sisi) dan N adalah jumlah *node* (simpul) pada CFG (digunakan sebagai verifikasi)
3. **V(G) = R** — dengan R adalah jumlah *region* (daerah tertutup) dalam Control Flow Graph. Jumlah region dihitung dari area terbatas (*bounded region*) ditambah 1 area luar (*unbounded region*).

Interpretasi nilai Cyclomatic Complexity:

| Nilai V(G) | Tingkat Risiko | Deskripsi |
|---|---|---|
| 1–10 | **Rendah** | Kode sederhana, mudah diuji dan dipelihara |
| 11–20 | **Sedang** | Kompleksitas moderat, perlu perhatian lebih |
| 21–50 | **Tinggi** | Kode kompleks, berisiko tinggi, perlu refaktorisasi |
| > 50 | **Sangat Tinggi** | Kode tidak stabil, sangat sulit diuji, harus direfaktor |

> **Catatan:** Kedua fungsi yang diuji dalam dokumen ini (RoleController::addRole() dan ProfileController::show()) memiliki V(G) = 4, yang termasuk dalam kategori **risiko rendah** (1–10), sehingga relatif sederhana dan mudah diuji.

### Langkah-langkah Strategi Pengujian

1. **Ekstraksi Source Code** — Mengambil source code fungsi yang akan diuji dari basis kode aplikasi.
2. **Pembuatan Pseudocode** — Menulis ulang logika fungsi dalam bentuk pseudocode untuk memudahkan analisis alur.
3. **Pembuatan Control Flow Graph (CFG)** — Menggambarkan alur kendali program dalam bentuk graf berarah dengan node (proses/keputusan) dan edge (alur).
4. **Perhitungan Cyclomatic Complexity V(G)** — Menghitung jumlah independent path menggunakan rumus McCabe.
5. **Identifikasi Independent Paths** — Menentukan seluruh jalur independen yang diperlukan untuk mencapai 100% cakupan.
6. **Perancangan Test Case** — Membuat skenario pengujian untuk setiap independent path dengan data input dan expected output yang spesifik.
7. **Dokumentasi Hasil** — Menyusun laporan hasil pengujian dalam dokumen ini.

### Metrik Keberhasilan Strategi

- 100% independent path tercakup dalam test case
- Setiap predicate node diuji dengan kondisi **true** dan **false**
- Nilai Cyclomatic Complexity terdokumentasi sebagai acuan tingkat risiko kode

---

## E. Jadwal Pengujian

| Tahap | Aktivitas | Durasi | Tanggal |
|---|---|---|---|
| **1. Persiapan** | Pemilihan fungsi, analisis source code, pembuatan pseudocode | 1 hari | 26 Mei 2026 |
| **2. Analisis** | Pembuatan CFG, perhitungan Cyclomatic Complexity, identifikasi independent paths | 1 hari | 27 Mei 2026 |
| **3. Perancangan Test Case** | Penyusunan skenario uji, data input, dan expected output untuk setiap path | 1 hari | 28 Mei 2026 |
| **4. Review & Dokumentasi** | Review hasil analisis, finalisasi dokumen | 1 hari | 29 Mei 2026 |
| **5. Eksekusi ( jika ada )** | Implementasi test case ke dalam kode pengujian (PHPUnit) | 2 hari | 30–31 Mei 2026 |


| Tahap | Tanggal | Aktivitas |
|---|---|---|
| **Tahap 1** | 26 Mei 2026 | Analisis source code fungsi yang akan diuji (*RoleController::addRole()* & *ProfileController::show()*), penentuan ruang lingkup pengujian (*Basis Path Testing*), dan pembagian tugas. |
| **Tahap 2** | 27 Mei 2026 | Pembuatan *Control Flow Graph (CFG)*, perhitungan *Cyclomatic Complexity*, dan identifikasi *independent paths* untuk setiap fungsi. |
| **Tahap 3** | 28 Mei 2026 | Pelaksanaan uji coba (*Test Execution*) dan pencatatan kesesuaian *output* sistem terhadap *expected output* setiap *test case*. |
| **Tahap 4** | 29 Mei 2026 | Evaluasi hasil temuan dan finalisasi laporan akhir. |

---

## F. Tools dan Lingkungan

### Tools

| Tool / Software | Versi | Kegunaan |
|---|---|---|
| **PHP** | ^8.1 | Bahasa pemrograman backend aplikasi |
| **Laravel** | ^10.0 | Framework PHP yang digunakan aplikasi |
| **PHPUnit** | ^10.x | Framework pengujian unit untuk eksekusi test case |
| **Laravel Dusk** | (opsional) | Pengujian browser jika diperlukan untuk integrasi |
| **Visual Studio Code** | Terbaru | Editor kode untuk analisis dan dokumentasi |
| **Git** | Terbaru | Version control dan manajemen perubahan |
| **Markdown Editor** | — | Dokumentasi laporan pengujian |

### Lingkungan Pengujian

| Komponen | Spesifikasi |
|---|---|
| **OS** | Windows 11 / Linux (sesuai lingkungan pengembangan) |
| **Web Server** | Apache / Nginx (Laragon di lingkungan lokal) |
| **Database** | MySQL / MariaDB |
| **Node.js** | ^18.x (untuk asset compilation jika diperlukan) |
| **Composer** | ^2.x (dependency manager PHP) |

**Lingkungan (Environment):** Pengujian *White Box Testing* dan *Basis Path Testing* dilakukan pada lingkungan *Development* (Laragon Local Server) dengan mengakses langsung source code aplikasi RSHP Habib. Pengujian bersifat *structural* pada kode backend (*server-side*) dengan menganalisis *Control Flow Graph (CFG)*, menghitung *Cyclomatic Complexity*, dan mengeksekusi *test case* menggunakan PHPUnit tanpa bergantung pada antarmuka pengguna (UI).

---

## G. Kriteria Kelulusan (Pass/Fail Criteria)

### Kriteria Kelulusan (Pass)

Suatu fungsi dinyatakan **LULUS** pengujian Basis Path jika memenuhi **semua** kriteria berikut:

1. **Cakupan Path 100%** — Seluruh independent path yang diidentifikasi (V(G) paths) telah dibuat test case-nya.
2. **Semua Test Case Pass** — Setiap test case yang dijalankan menghasilkan *expected output* sesuai yang ditentukan.
3. **Tidak Ada Error Fatal** — Tidak ditemukan *unhandled exception*, *fatal error*, atau *bug* yang menghentikan eksekusi selama pengujian.
4. **Kesesuaian Logika** — Hasil eksekusi setiap path sesuai dengan spesifikasi yang diharapkan (tidak ada penyimpangan logika).
5. **Semua Predicate Teruji** — Setiap predicate node telah diuji dengan nilai **true** dan **false** minimal satu kali.

### Kriteria Kegagalan (Fail)

Suatu fungsi dinyatakan **GAGAL** jika memenuhi **salah satu** dari kriteria berikut:

1. **Cakupan Path Tidak Lengkap** — Tidak semua independent path memiliki test case.
2. **Test Case Gagal** — Satu atau lebih test case menghasilkan output yang tidak sesuai *expected output*.
3. **Ditemukan Bug** — Terdapat *unhandled exception*, *infinite loop*, *null pointer exception*, atau *logic error* pada alur yang diuji.
4. **Dead Branch** — Ditemukan predicate node yang salah satu cabangnya (*true* atau *false*) tidak pernah dapat dieksekusi (kode mati).
5. **Deviasi Spesifikasi** — Hasil eksekusi menyimpang dari spesifikasi fungsional yang ditentukan.

### Matriks Keputusan

| Kondisi | Status |
|---|---|
| Semua kriteria pass terpenuhi | ✅ **PASS (LULUS)** |
| Salah satu kriteria fail terpenuhi | ❌ **FAIL (GAGAL)** — Perlu perbaikan kode dan pengujian ulang |

---

## Fungsi yang Dipilih

| No | Nama Fungsi | File | Kondisi | V(G) |
|---|---|---|---|---|
| 1 | `RoleController::addRole()` | `app/Http/Controllers/Admin/RoleController.php` | 3 if | **4** |
| 2 | `ProfileController::show()` | `app/Http/Controllers/ProfileController.php` | 3 if | **4** |

Kedua fungsi dipilih karena:
- ✅ Memiliki **kondisi** (if)
- ✅ **Sederhana** dan **singkat** (< 30 baris)
- ✅ Mudah digambar CFG-nya
- ✅ Cocok untuk demonstrasi Basis Path Testing

---

## Fungsi 1: RoleController::addRole()

### Source Code {#source-code-f1}

```php
public function addRole(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:user,iduser',
        'role_id' => 'required|exists:role,idrole',
    ]);

    // Prevent assignment of profile-based roles
    $profileBasedRoleNames = ['Dokter', 'Perawat', 'Pemilik'];
    $role = Role::findOrFail($request->role_id);
    
    // P1: Cek apakah role termasuk profile-based
    if (in_array($role->nama_role, $profileBasedRoleNames)) {
        return redirect()->route('data.roles.index')
            ->with('error', 'Peran ' . $role->nama_role . ' hanya dapat ditambahkan melalui halaman manajemen profil yang sesuai.');
    }

    // P2: Cek apakah user sudah memiliki role ini
    $existingRole = RoleUser::where('iduser', $request->user_id)
        ->where('idrole', $request->role_id)
        ->first();

    if ($existingRole) {
        // P3: Jika sudah ada tapi nonaktif, reaktifkan
        if (!$existingRole->status) {
            $existingRole->update(['status' => 1]);
            return redirect()->route('data.roles.index')
                ->with('success', 'Peran berhasil diaktifkan kembali');
        }
        
        return redirect()->route('data.roles.index')
            ->with('error', 'Pengguna sudah memiliki peran ini');
    }

    // Buat role assignment baru
    RoleUser::create([
        'iduser' => $request->user_id,
        'idrole' => $request->role_id,
        'status' => 1
    ]);

    return redirect()->route('data.roles.index')
        ->with('success', 'Peran berhasil ditambahkan');
}
```

### Pseudocode {#pseudocode-f1}

```
START
  Validasi request (user_id, role_id)
  Ambil data role berdasarkan role_id
  P1: IF role adalah profile-based (Dokter/Perawat/Pemilik) THEN
        RETURN redirect dengan error
  END IF
  
  Cari existing role user
  P2: IF existingRole ditemukan THEN
        P3: IF existingRole.status == 0 (nonaktif) THEN
              Update status jadi 1 (aktif)
              RETURN redirect dengan success "diaktifkan kembali"
        ELSE
              RETURN redirect dengan error "sudah memiliki peran"
        END IF
  END IF
  
  Buat RoleUser baru dengan status = 1
  RETURN redirect dengan success "berhasil ditambahkan"
END
```

### Pseudocode Notasi a0, a1, a2... {#pseudocode-f1-a}

```
a0  : public function addRole(Request \$request)
    {
a1  :     \$request->validate([
              'user_id' => 'required|exists:user,iduser',
              'role_id' => 'required|exists:role,idrole',
          ]);

     // Prevent assignment of profile-based roles
a2  :     \$profileBasedRoleNames = ['Dokter', 'Perawat', 'Pemilik'];
a3  :     \$role = Role::findOrFail(\$request->role_id);
    
     // P1: Cek apakah role termasuk profile-based
a4  :     if (in_array(\$role->nama_role, \$profileBasedRoleNames)) {
              return redirect()->route('data.roles.index')
                  ->with('error', 'Peran ' . \$role->nama_role . ' hanya dapat ditambahkan melalui halaman manajemen profil yang sesuai.');
          }

     // P2: Cek apakah user sudah memiliki role ini
a5  :     \$existingRole = RoleUser::where('iduser', \$request->user_id)
              ->where('idrole', \$request->role_id)
              ->first();

a6  :     if (\$existingRole) {
              // P3: Jika sudah ada tapi nonaktif, reaktifkan
a7  :         if (!\$existingRole->status) {
a8  :             \$existingRole->update(['status' => 1]);
a9  :             return redirect()->route('data.roles.index')
                      ->with('success', 'Peran berhasil diaktifkan kembali');
              }
        
a10 :         return redirect()->route('data.roles.index')
                  ->with('error', 'Pengguna sudah memiliki peran ini');
          }

     // Buat role assignment baru
a11 :     RoleUser::create([
              'iduser' => \$request->user_id,
              'idrole' => \$request->role_id,
              'status' => 1
          ]);

a12 :     return redirect()->route('data.roles.index')
              ->with('success', 'Peran berhasil ditambahkan');
    }
```

### Control Flow Graph (CFG) {#cfg-f1}

```
                         [START]
                            |
                            v
                    [Validasi Request]
                            |
                            v
                    [Cari Role by ID]
                            |
                            v
              (P1) ┌──────────────────┐
           ┌───────┤ Role termasuk    │
           │ true  │ profile-based?   │
           │       └────────┬─────────┘
           v                │ false
     [Redirect Error]       v
                    [Cari Existing RoleUser]
                            |
                            v
              (P2) ┌──────────────────┐
           ┌───────┤ ExistingRole     │
           │ true  │ ditemukan?       │
           │       └────────┬─────────┘
           v                │ false
     (P3) ┌────────────┐    │
    ┌─────┤ Status == 0│    │
    │true │ (nonaktif)?│    │
    │     └─────┬──────┘    │
    v          │ false      │
 [Reaktifkan   v            v
  Status=1] [Redirect     [Buat RoleUser
    |         Error:       Baru Status=1]
    v        "sudah          |
 [Redirect    memiliki"]     v
  Success:                  [Redirect Success:
  "diaktifkan                 "berhasil
  kembali"]                   ditambahkan"]
                            |
                            v
                         [END]
```

### Cyclomatic Complexity {#complexity-f1}

**Rumus McCabe:** **V(G) = P + 1**

| Predicate Node | Source Code | Keterangan |
|---|---|---|
| **P1** | `if (in_array($role->nama_role, $profileBasedRoleNames))` | Cek apakah role adalah profile-based |
| **P2** | `if ($existingRole)` | Cek apakah user sudah memiliki role |
| **P3** | `if (!$existingRole->status)` | Cek apakah role dalam status nonaktif |

```
V(G) = P + 1 = 3 + 1 = 4
```

| Metode | Perhitungan | Hasil |
|---|---|---|
| V(G) = P + 1 | 3 + 1 | **4** |
| V(G) = E - N + 2 | 13 - 11 + 2 | **4** |
| V(G) = R | 4 region (3 bounded + 1 unbounded) | **4** |

### Test Case — Basis Path Testing {#test-case-f1}

**V(G) = 4 → 4 Independent Paths**

| Path | Rute | Skenario |
|---|---|---|
| **1** | START → P1(**true**) → Redirect Error | Role adalah profile-based (Dokter/Perawat/Pemilik) |
| **2** | START → P1(false) → P2(**true**) → P3(**true**) → Reaktifkan → Redirect Success | Role sudah ada tapi nonaktif, berhasil diaktifkan kembali |
| **3** | START → P1(false) → P2(**true**) → P3(**false**) → Redirect Error | Role sudah ada dan aktif, gagal ditambahkan |
| **4** | START → P1(false) → P2(**false**) → Buat Baru → Redirect Success | Role belum dimiliki user, berhasil ditambahkan |

**Detail Test Case:**

| TC ID | Path | user_id | role_id | Kondisi Awal | Expected Output |
|---|---|---|---|---|---|
| **TC-R1-1** | 1 | 1 | role_id untuk 'Dokter' | Role 'Dokter' termasuk profile-based | Redirect error: "Peran Dokter hanya dapat ditambahkan melalui halaman manajemen profil yang sesuai." |
| **TC-R1-2** | 2 | 2 | 3 (misal: 'Resepsionis') | User_id=2 sudah punya role_id=3 tapi status=0 (nonaktif) | Role diaktifkan → Redirect success: "Peran berhasil diaktifkan kembali" |
| **TC-R1-3** | 3 | 2 | 3 | User_id=2 sudah punya role_id=3 dan status=1 (aktif) | Redirect error: "Pengguna sudah memiliki peran ini" |
| **TC-R1-4** | 4 | 3 | 3 | User_id=3 belum punya role_id=3 sama sekali | RoleUser baru dibuat → Redirect success: "Peran berhasil ditambahkan" |

---

## Fungsi 2: ProfileController::show()

### Source Code {#source-code-f2}

```php
public function show()
{
    $user = Auth::user();
    $userId = $user->iduser;
    
    // Get user's roles
    $userRoles = DB::table('role_user')
        ->join('role', 'role_user.idrole', '=', 'role.idrole')
        ->where('role_user.iduser', $userId)
        ->where('role_user.status', 1)
        ->pluck('role.nama_role')
        ->toArray();

    $profiles = [];
    
    // P1: Cek profil Pemilik
    $pemilik = Pemilik::where('iduser', $userId)->first();
    if ($pemilik && in_array('Pemilik', $userRoles)) {
        $profiles['pemilik'] = $pemilik;
    }
    
    // P2: Cek profil Dokter
    $dokter = Dokter::where('iduser', $userId)->first();
    if ($dokter && in_array('Dokter', $userRoles)) {
        $profiles['dokter'] = $dokter;
    }
    
    // P3: Cek profil Perawat
    $perawat = Perawat::where('iduser', $userId)->first();
    if ($perawat && in_array('Perawat', $userRoles)) {
        $profiles['perawat'] = $perawat;
    }

    return view('profile.show', compact('user', 'profiles', 'userRoles'));
}
```

### Pseudocode {#pseudocode-f2}

```
START
  Dapatkan user yang login
  Dapatkan daftar roles user
  Inisialisasi array $profiles = []
  
  P1: IF user punya profil Pemilik AND user punya role 'Pemilik' THEN
        Tambahkan data pemilik ke $profiles['pemilik']
  END IF
  
  P2: IF user punya profil Dokter AND user punya role 'Dokter' THEN
        Tambahkan data dokter ke $profiles['dokter']
  END IF
  
  P3: IF user punya profil Perawat AND user punya role 'Perawat' THEN
        Tambahkan data perawat ke $profiles['perawat']
  END IF
  
  RETURN view dengan data user, profiles, userRoles
END
```

### Pseudocode Notasi a0, a1, a2... {#pseudocode-f2-a}

```
a0 : public function show()
    {
a1 :     \$user = Auth::user();
          \$userId = \$user->iduser;
a2 :     \$userRoles = DB::table('role_user')
              ->join('role', 'role_user.idrole', '=', 'role.idrole')
              ->where('role_user.iduser', \$userId)
              ->where('role_user.status', 1)
              ->pluck('role.nama_role')
              ->toArray();
a3 :     \$profiles = [];
a4 :     P1 → \$pemilik = Pemilik::where('iduser', \$userId)->first();
          if (\$pemilik && in_array('Pemilik', \$userRoles)) {
              \$profiles['pemilik'] = \$pemilik;
          }
a5 :     P2 → \$dokter = Dokter::where('iduser', \$userId)->first();
          if (\$dokter && in_array('Dokter', \$userRoles)) {
              \$profiles['dokter'] = \$dokter;
          }
a6 :     P3 → \$perawat = Perawat::where('iduser', \$userId)->first();
          if (\$perawat && in_array('Perawat', \$userRoles)) {
              \$profiles['perawat'] = \$perawat;
          }
a7 :     return view('profile.show', compact('user', 'profiles', 'userRoles'));
    }
```

### Control Flow Graph (CFG) {#cfg-f2}

```
                      [START]
                         |
                         v
                 [Auth::user()]
                         |
                         v
                 [Ambil UserRoles]
                         |
                         v
                 [Inisialisasi $profiles = []]
                         |
                         v
            (P1) ┌──────────────────────┐
         ┌───────┤ Punya profil         │
         │ true  │ Pemilik & role?      │
         │       └──────────┬───────────┘
         v                  │ false
  [$profiles['pemilik']     v
   = $pemilik]      (P2) ┌──────────────────────┘
                    ┌─────┤ Punya profil         │
                    │true │ Dokter & role?       │
                    │     └──────────┬───────────┘
                    v               │ false
             [$profiles['dokter']    v
              = $dokter]     (P3) ┌──────────────────────┐
                         ┌───────┤ Punya profil         │
                         │ true  │ Perawat & role?      │
                         │       └──────────┬───────────┘
                         v                  │ false
                  [$profiles['perawat']      v
                   = $perawat]         [Return view]
                                           |
                                           v
                                        [END]
```

### Cyclomatic Complexity {#complexity-f2}

**Rumus McCabe:** **V(G) = P + 1**

| Predicate Node | Source Code | Keterangan |
|---|---|---|
| **P1** | `if ($pemilik && in_array('Pemilik', $userRoles))` | Cek apakah user punya profil Pemilik |
| **P2** | `if ($dokter && in_array('Dokter', $userRoles))` | Cek apakah user punya profil Dokter |
| **P3** | `if ($perawat && in_array('Perawat', $userRoles))` | Cek apakah user punya profil Perawat |

```
V(G) = P + 1 = 3 + 1 = 4
```

| Metode | Perhitungan | Hasil |
|---|---|---|
| V(G) = P + 1 | 3 + 1 | **4** |
| V(G) = E - N + 2 | 12 - 10 + 2 | **4** |
| V(G) = R | 4 region (3 bounded + 1 unbounded) | **4** |

### Test Case — Basis Path Testing {#test-case-f2}

**V(G) = 4 → 4 Independent Paths**

| Path | Rute | Skenario |
|---|---|---|
| **1** | START → P1(**false**) → P2(**false**) → P3(**false**) → Return view | User tidak punya profil apapun (hanya user biasa) |
| **2** | START → P1(**true**) → P2(**false**) → P3(**false**) → Return view | User hanya punya profil Pemilik |
| **3** | START → P1(false) → P2(**true**) → P3(**false**) → Return view | User hanya punya profil Dokter |
| **4** | START → P1(true) → P2(true) → P3(**true**) → Return view | User punya semua profil (Pemilik + Dokter + Perawat) |

**Detail Test Case:**

| TC ID | Path | User | Data Profil | Data Role | Expected Output |
|---|---|---|---|---|---|
| **TC-PS-1** | 1 | User A (iduser=1) | Tidak ada profil apapun | Tidak ada roles | View dengan `$profiles = []` (kosong) |
| **TC-PS-2** | 2 | User B (iduser=2) | Hanya ada data di tabel `pemilik` untuk user_id=2 | Role: ['Pemilik'] | View dengan `$profiles['pemilik']` terisi |
| **TC-PS-3** | 3 | User C (iduser=3) | Hanya ada data di tabel `dokter` untuk user_id=3 | Role: ['Dokter'] | View dengan `$profiles['dokter']` terisi |
| **TC-PS-4** | 4 | User D (iduser=4) | Ada data di tabel `pemilik`, `dokter`, dan `perawat` | Role: ['Pemilik', 'Dokter', 'Perawat'] | View dengan `$profiles['pemilik']`, `$profiles['dokter']`, `$profiles['perawat']` semua terisi |

---

## Ringkasan {#ringkasan}

| Fungsi | Jumlah Predicate (P) | V(G) = P + 1 | Jumlah Independent Paths | Jumlah Test Case |
|---|---|---|---|---|
| `RoleController::addRole()` | 3 | **4** | 4 | 4 |
| `ProfileController::show()` | 3 | **4** | 4 | 4 |

### Komponen yang Diuji

**Kondisi (Branches):**
- Setiap `if` statement diuji dengan skenario **true** dan **false**
- Fungsi 1 menguji: profile-based check, existing role check, status aktif/nonaktif
- Fungsi 2 menguji: kepemilikan profil Pemilik, Dokter, dan Perawat

**Basis Path:**
- Setiap **independent path** dalam CFG memiliki minimal 1 test case unik
- Total **8 test case** untuk 2 fungsi (masing-masing 4)

# Analisis Basis Path & Cyclomatic Complexity

**Project:** RSHP Habib (Laravel App)  
**Analyst:** Pi Coding Agent  
**Tanggal:** 2026-05-28

---

## Fungsi yang Dipilih

| No | Nama Fungsi | File | KONDISI | PERULANGAN |
|---|---|---|---|---|
| 1 | `RekamMedisController::updateDetail()` | `app/Http/Controllers/Admin/RekamMedisController.php` | ✅ (7 if) | ✅ (1 foreach) |
| 2 | `TemuDokterController::storeRekamMedis()` | `app/Http/Controllers/Admin/TemuDokterController.php` | ✅ (3 if) | ✅ (1 foreach) |

---

## Source Code

### Fungsi 1 — `RekamMedisController::updateDetail()`

```php
public function updateDetail(Request $request, $id)
{
    // P1: Authorization check
    if (!Auth::user()->hasRole('Administrator') && !Auth::user()->hasRole('Dokter')) {
        return redirect()->route('data.rekam-medis.index')
            ->with('error', 'Anda tidak memiliki akses untuk mengedit detail tindakan.');
    }

    // Get medical record
    $rekamMedis = DB::table('rekam_medis')->where('idrekam_medis', $id)->first();
    
    // P2: Check if record exists
    if (!$rekamMedis) {
        abort(404);
    }

    // P3: Additional check for Dokter (must be examining doctor)
    if (Auth::user()->hasRole('Dokter') && !Auth::user()->hasRole('Administrator')) {
        $dokterRoleUserId = DB::table('role_user')
            ->join('role', 'role_user.idrole', '=', 'role.idrole')
            ->where('role_user.iduser', Auth::user()->iduser)
            ->where('role.nama_role', 'Dokter')
            ->where('role_user.status', 1)
            ->value('role_user.idrole_user');
        
        // P4: Check dokter ownership
        if (!$dokterRoleUserId || $rekamMedis->dokter_pemeriksa != $dokterRoleUserId) {
            return redirect()->route('data.rekam-medis.index')
                ->with('error', 'Anda hanya dapat mengedit detail tindakan dari rekam medis yang Anda periksa sendiri.');
        }
    }

    $request->validate([
        'detail_tindakan' => 'array',
        'detail_tindakan.*.idkode_tindakan_terapi' => 'required|exists:kode_tindakan_terapi,idkode_tindakan_terapi',
        'detail_tindakan.*.detail' => 'nullable|string',
    ]);

    DB::beginTransaction();
    try {
        // Delete existing details
        DB::table('detail_rekam_medis')->where('idrekam_medis', $id)->delete();
        
        // P5: Check if detail actions provided
        if ($request->has('detail_tindakan') && is_array($request->detail_tindakan)) {
            // P6: foreach loop
            foreach ($request->detail_tindakan as $detail) {
                // P7: Check if treatment code is provided
                if (!empty($detail['idkode_tindakan_terapi'])) {
                    DB::table('detail_rekam_medis')->insert([
                        'idrekam_medis' => $id,
                        'idkode_tindakan_terapi' => $detail['idkode_tindakan_terapi'],
                        'detail' => $detail['detail'] ?? null,
                    ]);
                }
            }
        }

        DB::commit();

        return redirect()->route('data.rekam-medis.index')
            ->with('success', 'Detail tindakan berhasil diperbarui');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()
            ->with('error', 'Gagal memperbarui detail tindakan: ' . $e->getMessage())
            ->withInput();
    }
}
```

---

### Fungsi 2 — `TemuDokterController::storeRekamMedis()`

```php
public function storeRekamMedis(Request $request, $temuDokterId)
{
    $request->validate([
        'anamnesa' => 'required|string',
        'temuan_klinis' => 'required|string',
        'diagnosa' => 'required|string',
        'idpet' => 'required|exists:pet,idpet',
        'detail_tindakan' => 'array',
        'detail_tindakan.*.idkode_tindakan_terapi' => 'required|exists:kode_tindakan_terapi,idkode_tindakan_terapi',
        'detail_tindakan.*.detail' => 'nullable|string',
    ]);

    // P1: Verify temu dokter exists
    $temuDokter = DB::table('temu_dokter')->where('idreservasi_dokter', $temuDokterId)->first();
    if (!$temuDokter) {
        return response()->json([
            'success' => false,
            'message' => 'Data reservasi dokter tidak ditemukan'
        ], 404);
    }

    DB::beginTransaction();
    try {
        // Insert medical record with temu dokter relationship
        $rekamMedisId = DB::table('rekam_medis')->insertGetId([
            'anamnesa' => $request->anamnesa,
            'temuan_klinis' => $request->temuan_klinis,
            'diagnosa' => $request->diagnosa,
            'idpet' => $request->idpet,
            'dokter_pemeriksa' => $temuDokter->idrole_user,
            'idreservasi_dokter' => $temuDokterId,
            'created_at' => now(),
        ]);

        // P2: Check if detail actions provided
        if ($request->has('detail_tindakan') && is_array($request->detail_tindakan)) {
            // P3: foreach loop
            foreach ($request->detail_tindakan as $detail) {
                // P4: Check if treatment code is provided
                if (!empty($detail['idkode_tindakan_terapi'])) {
                    DB::table('detail_rekam_medis')->insert([
                        'idrekam_medis' => $rekamMedisId,
                        'idkode_tindakan_terapi' => $detail['idkode_tindakan_terapi'],
                        'detail' => $detail['detail'] ?? null,
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Rekam medis berhasil ditambahkan',
            'data' => ['id' => $rekamMedisId]
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Gagal menambahkan rekam medis: ' . $e->getMessage()
        ], 422);
    }
}
```

---

## 1. Control Flow Graph (CFG)

### CFG — Fungsi 1: `RekamMedisController::updateDetail()`

```
                            [START]
                               |
                               v
                (P1) ┌──────────────────────┐
             ┌───────┤ Cek Role:            │
             │ true  │ Admin atau Dokter?   │
             │       └──────────┬───────────┘
             v                  │ false
       [Redirect Error]         v
                          [Cari Rekam Medis by ID]
                               |
                               v
                (P2) ┌──────────────────────┐
             ┌───────┤ Rekam Medis          │
             │ true  │ Ditemukan?           │
             │       └──────────┬───────────┘
             v                  │ false
       [abort(404)]            v
                    (P3) ┌──────────────────────────┐
                 ┌───────┤ Role = Dokter            │
                 │ true  │ && !Administrator?       │
                 │       └────────────┬─────────────┘
                 v                    │ false
           [Cari Dokter              │
            Role User ID]            │
                 |                   │
                 v                   │
              (P4) ┌─────────────┐   │
           ┌───────┤ Dokter punya│   │
           │ true  │ akses?      │   │
           │       └──────┬──────┘   │
           v              │ false    │
     [Redirect Error]     v          v
                     [Validasi Request]
                          |
                          v
                    [DB::beginTransaction]
                          |
                          v
                    [Hapus Detail Lama]
                          |
                          v
               (P5) ┌──────────────────────────┐
           ┌───────┤ Request memiliki          │
           │ true  │ detail_tindakan?          │
           │       └────────────┬──────────────┘
           v                    │ false
      (P6) foreach loop        │
        ┌────────────────┐     │
        |                |     │
        v                |     │
     (P7) ┌─────────┐    |     │
    ┌─────┤ idkode   │    |     │
    │true │ kosong?  │    |     │
    │     └────┬─────┘    |     │
    v         │ false     |     │
 [Insert]     v           |     │
    |    (lanjut loop)    |     │
    └─────────────────────┘     │
        (loop ends)             │
           |                    │
           v                    v
                      [DB::commit]
                          |
                          v
                 [Redirect Success]
                          |
                          v
              ┌─────────────────────┐
              │   [EXCEPTION]       │
              └─────────┬───────────┘
                        v
                [DB::rollBack]
                        |
                        v
               [Redirect Error]
                        |
                        v
                      [END]
```

---

### CFG — Fungsi 2: `TemuDokterController::storeRekamMedis()`

```
                        [START]
                           |
                           v
                   [Validasi Request]
                           |
                           v
                (P1) ┌──────────────────────┐
             ┌───────┤ Temu Dokter          │
             │ true  │ Ditemukan?           │
             │       └──────────┬───────────┘
             v                  │ false
      [Return JSON 404]        v
                       [DB::beginTransaction]
                           |
                           v
                    [Insert Rekam Medis]
                           |
                           v
                (P2) ┌──────────────────────────┐
             ┌───────┤ Request memiliki          │
             │ true  │ detail_tindakan?          │
             │       └────────────┬──────────────┘
             v                    │ false
        (P3) foreach loop        │
          ┌──────────────┐       │
          |              |       │
          v              |       │
       (P4) ┌────────┐   |       │
      ┌─────┤ idkode  │   |       │
      │true │ kosong? │   |       │
      │     └───┬─────┘   |       │
      v         │ false   |       │
   [Insert      v         |       │
    Detail] (lanjut loop)  |       │
      └───────────────────┘       │
         (loop ends)              │
            |                     │
            v                     v
                       [DB::commit]
                           |
                           v
                  [Return JSON Success]
                           |
                           v
               ┌──────────────────────┐
               │    [EXCEPTION]       │
               └──────────┬───────────┘
                          v
                  [DB::rollBack]
                          |
                          v
              [Return JSON Error 422]
                          |
                          v
                        [END]
```

---

## 2. Cyclomatic Complexity — V(G)

### Rumus McCabe

**V(G) = P + 1**

Dimana **P** = jumlah **predicate node** (node yang memiliki 2 atau lebih cabang: if, elseif, for, foreach, while, case).

---

### Fungsi 1: `RekamMedisController::updateDetail()` — V(G) = 8

| Predicate Node | Source Code | Jenis | Keterangan |
|---|---|---|---|
| **P1** | `if (!Auth::user()->hasRole('Administrator') && !Auth::user()->hasRole('Dokter'))` | if-condition | Cek otorisasi user |
| **P2** | `if (!$rekamMedis)` | if-condition | Cek apakah record ditemukan |
| **P3** | `if (Auth::user()->hasRole('Dokter') && !Auth::user()->hasRole('Administrator'))` | if-condition | Cek apakah user adalah Dokter (bukan Admin) |
| **P4** | `if (!$dokterRoleUserId \|\| $rekamMedis->dokter_pemeriksa != $dokterRoleUserId)` | if-condition | Cek kepemilikan rekam medis |
| **P5** | `if ($request->has('detail_tindakan') && is_array($request->detail_tindakan))` | if-condition | Cek apakah ada data detail tindakan |
| **P6** | `foreach ($request->detail_tindakan as $detail)` | foreach-loop | Perulangan untuk setiap detail |
| **P7** | `if (!empty($detail['idkode_tindakan_terapi']))` | if-condition | Cek apakah kode tindakan diisi |

```
V(G) = 7 + 1 = 8
```

| Komplemen | Nilai |
|---|---|
| Jumlah Edge (E) | 23 |
| Jumlah Node (N) | 17 |
| V(G) = E - N + 2 | 23 - 17 + 2 = 8 |
| V(G) = P + 1 | 7 + 1 = 8 |

---

### Fungsi 2: `TemuDokterController::storeRekamMedis()` — V(G) = 5

| Predicate Node | Source Code | Jenis | Keterangan |
|---|---|---|---|
| **P1** | `if (!$temuDokter)` | if-condition | Cek apakah temu dokter ditemukan |
| **P2** | `if ($request->has('detail_tindakan') && is_array($request->detail_tindakan))` | if-condition | Cek apakah ada data detail tindakan |
| **P3** | `foreach ($request->detail_tindakan as $detail)` | foreach-loop | Perulangan untuk setiap detail |
| **P4** | `if (!empty($detail['idkode_tindakan_terapi']))` | if-condition | Cek apakah kode tindakan diisi |

```
V(G) = 4 + 1 = 5
```

| Komplemen | Nilai |
|---|---|
| Jumlah Edge (E) | 15 |
| Jumlah Node (N) | 12 |
| V(G) = E - N + 2 | 15 - 12 + 2 = 5 |
| V(G) = P + 1 | 4 + 1 = 5 |

---

## 3. Design Test Case — Basis Path Testing

### Fungsi 1: `RekamMedisController::updateDetail()` — V(G) = 8

#### 8 Independent Paths

| Path | Rute Lengkap | Skenario |
|---|---|---|
| **1** | START → **P1(true)** → Redirect Error | User role selain Admin & Dokter (misal: Pemilik) |
| **2** | START → P1(false) → **P2(true)** → abort(404) | Record tidak ditemukan di DB |
| **3** | START → P1(false) → P2(false) → **P3(true)** → **P4(true)** → Redirect Error | Dokter mencoba edit rekam medis dokter lain |
| **4** | START → P1(false) → P2(false) → P3(true) → P4(false) → **P5(false)** → Commit → Success | Dokter punya akses, tanpa detail tindakan |
| **5** | START → P1(false) → P2(false) → P3(true) → P4(false) → P5(true) → P6[**P7(true)**] → Commit → Success | Dokter punya akses, 1 detail dengan kode valid |
| **6** | START → P1(false) → P2(false) → P3(true) → P4(false) → P5(true) → P6[**P7(false)**] → Commit → Success | Dokter punya akses, detail dengan kode kosong |
| **7** | START → P1(false) → P2(false) → **P3(false)** → P5(true) → P6[P7(true)] → Commit → Success | Admin (bukan Dokter), ada detail valid |
| **8** | START → ... → **EXCEPTION** → rollBack → Redirect Error | Terjadi exception database |

#### Detail Test Cases

| TC ID | Path | Role User | ID Rekam Medis | detail_tindakan | Expected Result |
|---|---|---|---|---|---|
| **TC-F1-1** | 1 | Pemilik | (any) | - | Redirect error: "Anda tidak memiliki akses untuk mengedit detail tindakan." |
| **TC-F1-2** | 2 | Administrator | 99999 (invalid) | - | HTTP 404 |
| **TC-F1-3** | 3 | Dokter (bukan pemeriksa) | ID milik dokter lain | - | Redirect error: "Anda hanya dapat mengedit detail tindakan dari rekam medis yang Anda periksa sendiri." |
| **TC-F1-4** | 4 | Dokter (pemeriksa) | Valid, miliknya | `null` | Commit → Redirect success: "Detail tindakan berhasil diperbarui" |
| **TC-F1-5** | 5 | Dokter (pemeriksa) | Valid | `[{idkode: "K001", detail: "Obat antibiotik"}]` | Insert detail → Commit → Redirect success |
| **TC-F1-6** | 6 | Dokter (pemeriksa) | Valid | `[{idkode: "", detail: "test"}]` | Skip insert (kode kosong) → Commit → Redirect success |
| **TC-F1-7** | 7 | Administrator | Valid | `[{idkode: "K002", detail: "Vitamin"}]` | Insert detail → Commit → Redirect success |
| **TC-F1-8** | 8 | Administrator | Valid (DB error) | `[{idkode: "X", detail: "err"}]` (foreign key fail) | RollBack → Redirect error: "Gagal memperbarui detail tindakan" |

---

### Fungsi 2: `TemuDokterController::storeRekamMedis()` — V(G) = 5

#### 5 Independent Paths

| Path | Rute Lengkap | Skenario |
|---|---|---|
| **1** | START → Validasi → **P1(true)** → Return JSON 404 | Temu Dokter tidak ditemukan |
| **2** | START → Validasi → P1(false) → Trans → Insert → **P2(false)** → Commit → JSON Success | Tidak ada detail tindakan |
| **3** | START → Validasi → P1(false) → Trans → Insert → P2(true) → P3[**P4(true)**] → Commit → JSON Success | Ada detail dengan kode valid |
| **4** | START → Validasi → P1(false) → Trans → Insert → P2(true) → P3[**P4(false)**] → Commit → JSON Success | Ada detail dengan kode kosong |
| **5** | START → Validasi → P1(false) → Trans → Insert → ... → **EXCEPTION** → rollBack → JSON Error 422 | Terjadi exception database |

#### Detail Test Cases

| TC ID | Path | ID Temu Dokter | detail_tindakan | Expected Result |
|---|---|---|---|---|
| **TC-F2-1** | 1 | 99999 (invalid) | - | JSON `{"success": false, "message": "Data reservasi dokter tidak ditemukan"}`, HTTP 404 |
| **TC-F2-2** | 2 | Valid ID | `null` / tidak dikirim | Insert rekam medis → Commit → JSON `{"success": true, "message": "Rekam medis berhasil ditambahkan"}` |
| **TC-F2-3** | 3 | Valid ID | `[{idkode: "K001", detail: "Obat cacing"}]` | Insert rekam medis + detail → Commit → JSON success |
| **TC-F2-4** | 4 | Valid ID | `[{idkode: "", detail: "test"}]` | Insert rekam medis saja (skip detail karena kode kosong) → Commit → JSON success |
| **TC-F2-5** | 5 | Valid ID (tapi DB error) | `[{idkode: "INVALID", detail: "err"}]` (foreign key violation) | RollBack → JSON `{"success": false, "message": "Gagal menambahkan rekam medis: ..."}`, HTTP 422 |

---

## Ringkasan

| Fungsi | Jumlah Predicate (P) | V(G) = P + 1 | Jumlah Independent Paths | Jumlah Test Case |
|---|---|---|---|---|
| `RekamMedisController::updateDetail()` | 7 | **8** | 8 | 8 |
| `TemuDokterController::storeRekamMedis()` | 4 | **5** | 5 | 5 |

### Komponen yang Diuji

- **Kondisi (Branches):** Setiap `if` statement diuji dengan skenario **true** dan **false**.
- **Perulangan (Loops):** Setiap `foreach` diuji dengan:
  - **Loop tidak dieksekusi** (array kosong / null)
  - **Loop dieksekusi minimal 1 iterasi**
  - **Inner condition di dalam loop** (true / false)
- **Exception Handling:** Setiap `try-catch` diuji dengan skenario database error.
- **Basis Path:** Setiap **independent path** dalam CFG memiliki minimal 1 test case yang unik.

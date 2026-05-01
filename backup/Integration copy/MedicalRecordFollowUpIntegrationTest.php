<?php

namespace Tests\Integration;

use App\Models\RekamMedis;
use App\Models\TemuDokter;
use App\Models\Pet;
use Carbon\Carbon;

/**
 * LEVEL 2 - MEDICAL RECORD FOLLOW-UP WORKFLOW
 * =============================================
 * 
 * Fokus pada proses follow-up dan pembaruan rekam medis:
 * - Follow-up examination
 * - Update hasil treatment
 * - Monitoring progress pasien
 * - Riwayat perkembangan
 * 
 * @group integration
 * @group medical-record-followup
 */
class MedicalRecordFollowUpIntegrationTest extends IntegrationTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestActors();
        $this->setUpMasterData();
    }

    // =====================================================================
    // TEST WORKFLOW 1: FOLLOW-UP EXAMINATION
    // =====================================================================

    /**
     * TEST #001: Follow-up Examination Setelah Treatment
     * 
     * Verifikasi:
     * - Dapat membuat appointment follow-up
     * - Hasil follow-up examination mencatat progress treatment
     * 
     * @test
     */
    public function test_follow_up_examination_after_treatment()
    {
        // ─────────────────────────────────────────────────────────────
        // INITIAL EXAMINATION
        // ─────────────────────────────────────────────────────────────
        
        $initialAppointment = $this->createPendingAppointment();
        
        $initialMR = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Hewan terlihat lemas, muntah-muntah',
            'temuan_klinis' => 'Mata redup, turgor kulit buruk, suhu 39.8°C',
            'diagnosa' => 'Gastroenteritis akut',
            'created_at' => Carbon::now()->subDays(7)
        ]);

        $this->completeAppointment($initialAppointment);

        // ─────────────────────────────────────────────────────────────
        // TREATMENT PERIOD (5 days)
        // Simulasi: Dokter memberikan obat antibiotik, cairan IV, diet khusus
        // ─────────────────────────────────────────────────────────────

        // ─────────────────────────────────────────────────────────────
        // FOLLOW-UP EXAMINATION (5 days later)
        // ─────────────────────────────────────────────────────────────
        
        $followUpAppointment = $this->createPendingAppointment();

        $followUpMR = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Follow-up setelah 5 hari treatment. Pemilik melaporkan nafsu makan mulai membaik, muntah berkurang drastis.',
            'temuan_klinis' => 'Mata mulai cerah, turgor kulit membaik, suhu normal 38.5°C, berat badan stabil',
            'diagnosa' => 'Gastroenteritis - responding to treatment. Lanjutkan treatment.',
            'created_at' => now()
        ]);

        $this->completeAppointment($followUpAppointment);

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        // Pet memiliki 2 rekam medis
        $this->assertCount(2, $this->pet->rekamMedis);

        // Verifikasi progress improvement
        $initialDiagnosis = $initialMR->fresh()->diagnosa;
        $followUpDiagnosis = $followUpMR->fresh()->diagnosa;

        $this->assertEquals('Gastroenteritis akut', $initialDiagnosis);
        $this->assertStringContainsString('responding to treatment', $followUpDiagnosis);

        // Timeline check
        $this->assertTrue($followUpMR->created_at > $initialMR->created_at);
    }

    // =====================================================================
    // TEST WORKFLOW 2: MONITORING PROGRESS
    // =====================================================================

    /**
     * TEST #002: Long-term Monitoring (Multiple Follow-ups)
     * 
     * Verifikasi:
     * - Dapat mencatat multiple follow-up visits
     * - Menunjukkan trajectory recovery/progression
     * 
     * @test
     */
    public function test_long_term_monitoring_with_multiple_followups()
    {
        $examinationDates = [
            Carbon::now()->subDays(30), // Initial
            Carbon::now()->subDays(25), // 5 days after
            Carbon::now()->subDays(18), // 12 days after
            Carbon::now()->subDays(10), // 20 days after
            Carbon::now(),              // 30 days after
        ];

        $diagnosticProgression = [
            'Infeksi kulit staph - Acute phase',
            'Infeksi kulit - Early response to antibiotics',
            'Infeksi kulit - 50% improvement, lesions reducing',
            'Infeksi kulit - 80% healing, minimal remaining lesions',
            'Infeksi kulit - Recovery complete, skin healing well'
        ];

        $appointments = [];
        $medicalRecords = [];

        // Buat appointment dan rekam medis untuk setiap visit
        for ($i = 0; $i < count($examinationDates); $i++) {
            $apt = $this->createPendingAppointment();
            $appointments[] = $apt;

            $mr = RekamMedis::create([
                'idpet' => $this->pet->idpet,
                'dokter_pemeriksa' => $this->idRoleUserDokter,
                'anamnesa' => "Visit ke-" . ($i + 1),
                'temuan_klinis' => "Clinical findings for visit " . ($i + 1),
                'diagnosa' => $diagnosticProgression[$i],
                'created_at' => $examinationDates[$i]
            ]);

            $medicalRecords[] = $mr;
            $this->completeAppointment($apt);
        }

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        // Pet memiliki 5 rekam medis
        $this->assertCount(5, $this->pet->rekamMedis);

        // Verifikasi urutan kronologis
        $petMedicalHistoryOrdered = $this->pet->rekamMedis()->orderBy('created_at')->get();
        
        foreach ($petMedicalHistoryOrdered as $index => $record) {
            $this->assertStringContainsString(
                'Visit ke-' . ($index + 1),
                $record->anamnesa
            );
        }

        // Verifikasi progress dari Acute → Recovery
        $this->assertStringContainsString('Acute phase', $medicalRecords[0]->diagnosa);
        $this->assertStringContainsString('Recovery complete', $medicalRecords[4]->diagnosa);
    }

    // =====================================================================
    // TEST WORKFLOW 3: TREATMENT RESPONSE TRACKING
    // =====================================================================

    /**
     * TEST #003: Tracking Treatment Response Rate
     * 
     * Verifikasi:
     * - Dapat membedakan response positif vs negatif terhadap treatment
     * - Dokter dapat membuat keputusan terapeutik berdasarkan data
     * 
     * @test
     */
    public function test_tracking_positive_vs_negative_treatment_response()
    {
        // ─────────────────────────────────────────────────────────────
        // CASE 1: POSITIVE RESPONSE
        // ─────────────────────────────────────────────────────────────
        
        $positiveResponseMR1 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Initial: Kesulitan menelan, drooling excessive',
            'temuan_klinis' => 'Pharyngeal edema (swelling) - severe',
            'diagnosa' => 'Pharyngitis - Started prednisolone',
            'created_at' => Carbon::now()->subDays(5)
        ]);

        $positiveResponseMR2 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Follow-up: Mampu menelan dengan baik, drooling berkurang',
            'temuan_klinis' => 'Pharyngeal edema - REDUCED 70%',
            'diagnosa' => 'Pharyngitis - POSITIVE RESPONSE to prednisolone. Continue treatment.',
            'created_at' => now()
        ]);

        // ─────────────────────────────────────────────────────────────
        // CASE 2: NEGATIVE RESPONSE (Different pet)
        // ─────────────────────────────────────────────────────────────
        
        $pet2 = Pet::create([
            'nama' => 'Sylvester',
            'tanggal_lahir' => '2023-01-01',
            'warna_tanda' => 'Black',
            'jenis_kelamin' => 'Jantan',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => 1
        ]);

        $negativeResponseMR1 = RekamMedis::create([
            'idpet' => $pet2->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Initial: Kesulitan bernafas, lethargy',
            'temuan_klinis' => 'Respiratory distress - moderate',
            'diagnosa' => 'Possible pneumonia - Started amoxicillin',
            'created_at' => Carbon::now()->subDays(5)
        ]);

        $negativeResponseMR2 = RekamMedis::create([
            'idpet' => $pet2->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Follow-up: Kondisi memburuk, nafas semakin berat',
            'temuan_klinis' => 'Respiratory distress - INCREASED to severe',
            'diagnosa' => 'Pneumonia - NO RESPONSE to amoxicillin. Change to fluoroquinolone.',
            'created_at' => now()
        ]);

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        // Positive response case
        $this->assertStringContainsString('POSITIVE RESPONSE', $positiveResponseMR2->diagnosa);
        $this->assertStringContainsString('REDUCED 70%', $positiveResponseMR2->temuan_klinis);

        // Negative response case
        $this->assertStringContainsString('NO RESPONSE', $negativeResponseMR2->diagnosa);
        $this->assertStringContainsString('INCREASED', $negativeResponseMR2->temuan_klinis);

        // Both cases have 2 follow-ups
        $this->assertCount(2, $this->pet->rekamMedis);
        $this->assertCount(2, $pet2->rekamMedis);
    }

    // =====================================================================
    // TEST WORKFLOW 4: DISCHARGE PLANNING
    // =====================================================================

    /**
     * TEST #004: Discharge from Treatment - Recovery Complete
     * 
     * Verifikasi:
     * - Final examination menunjukkan recovery complete
     * - Discharge notes terdata dengan baik
     * 
     * @test
     */
    public function test_discharge_planning_after_successful_treatment()
    {
        // ─────────────────────────────────────────────────────────────
        // VISIT 1: Initial
        // ─────────────────────────────────────────────────────────────
        
        $visit1 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Kucing patah kaki kiri depan',
            'temuan_klinis' => 'Fracture of left front leg - requires immediate stabilization',
            'diagnosa' => 'Broken leg - Treated with splinting',
            'created_at' => Carbon::now()->subDays(14)
        ]);

        // ─────────────────────────────────────────────────────────────
        // VISIT 2: Follow-up (1 week)
        // ─────────────────────────────────────────────────────────────
        
        $visit2 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Follow-up 1 minggu, kucing dapat menapak sedikit',
            'temuan_klinis' => 'Splint masih intact, swelling berkurang, mobility improving',
            'diagnosa' => 'Fracture healing well - Maintain splint and rest',
            'created_at' => Carbon::now()->subDays(7)
        ]);

        // ─────────────────────────────────────────────────────────────
        // VISIT 3: Final Check (14 days total)
        // ─────────────────────────────────────────────────────────────
        
        $finalVisit = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Final check-up, kucing sudah normal jalan',
            'temuan_klinis' => 'X-ray shows good bone healing, splint removed, full weight bearing, no lameness',
            'diagnosa' => 'DISCHARGED - Fracture healed successfully. Return to normal activity. Follow home care instructions.',
            'created_at' => now()
        ]);

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        $this->assertCount(3, $this->pet->rekamMedis);

        // Verify discharge status in final diagnosis
        $this->assertStringContainsString('DISCHARGED', $finalVisit->diagnosa);
        $this->assertStringContainsString('healed successfully', $finalVisit->diagnosa);

        // Timeline verification
        $medicalHistory = $this->pet->rekamMedis()->orderBy('created_at')->get();
        $this->assertEquals('Broken leg - Treated with splinting', $medicalHistory[0]->diagnosa);
        $this->assertStringContainsString('healing well', $medicalHistory[1]->diagnosa);
        $this->assertStringContainsString('DISCHARGED', $medicalHistory[2]->diagnosa);
    }

    // =====================================================================
    // TEST WORKFLOW 5: HOSPITALIZATION TRACKING
    // =====================================================================

    /**
     * TEST #005: Hospitalization Record (Multiple Daily Visits)
     * 
     * Verifikasi:
     * - Dapat mencatat multiple daily examination untuk patient yang dirawat
     * - Tracking kondisi harian
     * 
     * @test
     */
    public function test_hospitalization_daily_monitoring()
    {
        // ─────────────────────────────────────────────────────────────
        // HOSPITALIZATION: DAY 1
        // ─────────────────────────────────────────────────────────────
        
        $day1 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Day 1 - Admission untuk post-operative monitoring setelah operasi',
            'temuan_klinis' => 'Alert, mucous membranes pink, CRT <2s, suhu 38.9°C, IV drip running',
            'diagnosa' => 'Post-op Day 1 - Stable condition',
            'created_at' => now()->subDays(2)->setTime(8, 0)
        ]);

        // ─────────────────────────────────────────────────────────────
        // HOSPITALIZATION: DAY 2
        // ─────────────────────────────────────────────────────────────
        
        $day2 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Day 2 - Pasien mulai makan, aktivitas bertambah',
            'temuan_klinis' => 'Responsif, incision site clean, minimal swelling, suhu normal 38.5°C',
            'diagnosa' => 'Post-op Day 2 - Good recovery progress',
            'created_at' => now()->subDay()->setTime(8, 0)
        ]);

        // ─────────────────────────────────────────────────────────────
        // HOSPITALIZATION: DAY 3 (DISCHARGE)
        // ─────────────────────────────────────────────────────────────
        
        $day3 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Day 3 - Ready for discharge, instructions diberikan',
            'temuan_klinis' => 'Wound healing well, normal appetite, playful behavior',
            'diagnosa' => 'Post-op Day 3 - DISCHARGED with home care instructions',
            'created_at' => now()->setTime(10, 0)
        ]);

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        $hospitalizationRecords = $this->pet->rekamMedis()->orderBy('created_at')->get();
        
        $this->assertCount(3, $hospitalizationRecords);

        // Verify day-by-day progression
        $this->assertStringContainsString('Day 1', $hospitalizationRecords[0]->anamnesa);
        $this->assertStringContainsString('Stable condition', $hospitalizationRecords[0]->diagnosa);

        $this->assertStringContainsString('Day 2', $hospitalizationRecords[1]->anamnesa);
        $this->assertStringContainsString('progress', $hospitalizationRecords[1]->diagnosa);

        $this->assertStringContainsString('Day 3', $hospitalizationRecords[2]->anamnesa);
        $this->assertStringContainsString('DISCHARGED', $hospitalizationRecords[2]->diagnosa);
    }

    // =====================================================================
    // TEST WORKFLOW 6: CHRONIC DISEASE MANAGEMENT
    // =====================================================================

    /**
     * TEST #006: Chronic Disease Long-term Management
     * 
     * Verifikasi:
     * - Chronic condition dapat di-track jangka panjang
     * - Regular monitoring schedule
     * 
     * @test
     */
    public function test_chronic_disease_long_term_management()
    {
        $diagnoses = [
            'Chronic renal disease - Stage 2 - Start renal diet and monitoring',
            'CRD Stage 2 - Month 1 review - Stable, kidney parameters good',
            'CRD Stage 2 - Month 2 review - Minor weight loss noted, diet compliance check',
            'CRD Stage 2 - Month 3 review - Progressed to Stage 3, introduce phosphate binder',
            'CRD Stage 3 - Month 4 review - Stage 3 management ongoing',
        ];

        $medicalRecords = [];

        // Simulasi 5 bulan monitoring dengan appointment setiap bulan
        for ($month = 0; $month < 5; $month++) {
            $mr = RekamMedis::create([
                'idpet' => $this->pet->idpet,
                'dokter_pemeriksa' => $this->idRoleUserDokter,
                'anamnesa' => 'Monthly check-up for chronic renal disease management',
                'temuan_klinis' => 'BUN level: ' . (45 + $month * 5) . ' mg/dL, Creatinine: ' . (1.2 + $month * 0.15),
                'diagnosa' => $diagnoses[$month],
                'created_at' => Carbon::now()->subMonths(4 - $month)->startOfMonth()
            ]);

            $medicalRecords[] = $mr;
        }

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        $this->assertCount(5, $this->pet->rekamMedis);

        // Verify progression: Stage 2 → Stage 3
        $this->assertStringContainsString('Stage 2', $medicalRecords[0]->diagnosa);
        $this->assertStringContainsString('Stage 3', $medicalRecords[3]->diagnosa);

        // Verify disease monitoring ongoing
        foreach ($medicalRecords as $record) {
            $this->assertStringContainsString('renal', strtolower($record->diagnosa));
        }
    }

    // =====================================================================
    // TEST WORKFLOW 7: MEDICATION TRACKING
    // =====================================================================

    /**
     * TEST #007: Medication Change Documentation
     * 
     * Verifikasi:
     * - Perubahan obat-obatan tercatat dalam rekam medis
     * - Dapat track reason untuk medication change
     * 
     * @test
     */
    public function test_medication_change_documentation()
    {
        // ─────────────────────────────────────────────────────────────
        // VISIT 1: Antibiotic 1
        // ─────────────────────────────────────────────────────────────
        
        $visit1 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Respiratory infection suspect',
            'temuan_klinis' => 'Fever 39.5°C, abnormal lung sounds',
            'diagnosa' => 'Respiratory infection - Started on Amoxicillin 250mg BID',
            'created_at' => Carbon::now()->subDays(10)
        ]);

        // ─────────────────────────────────────────────────────────────
        // VISIT 2: Antibiotic Changed
        // ─────────────────────────────────────────────────────────────
        
        $visit2 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Follow-up after 5 days Amoxicillin - No improvement, still coughing',
            'temuan_klinis' => 'Fever persists 39.2°C, lung sounds still abnormal',
            'diagnosa' => 'No response to Amoxicillin. Switch to Doxycycline 50mg BID for broader coverage.',
            'created_at' => Carbon::now()->subDays(5)
        ]);

        // ─────────────────────────────────────────────────────────────
        // VISIT 3: Positive Response
        // ─────────────────────────────────────────────────────────────
        
        $visit3 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Follow-up after 5 days Doxycycline - Improvement noted',
            'temuan_klinis' => 'Fever resolved (38.2°C), coughing reduced 70%, lung sounds clearer',
            'diagnosa' => 'Good response to Doxycycline. Continue for 5 more days, then reassess.',
            'created_at' => now()
        ]);

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        // Verify medication changes tracked
        $history = $this->pet->rekamMedis()->orderBy('created_at')->get();

        $this->assertStringContainsString('Amoxicillin', $history[0]->diagnosa);
        $this->assertStringContainsString('Doxycycline', $history[1]->diagnosa);
        $this->assertStringContainsString('Good response', $history[2]->diagnosa);

        // Verify reason for change
        $this->assertStringContainsString('No response', $history[1]->diagnosa);
    }
}

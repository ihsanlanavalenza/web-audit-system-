# Test Use Cases

Dokumen ini memetakan use case utama ke automated tests yang sudah ada di repository.

## Auth dan onboarding
- Registrasi publik dengan role auditor -> tests/Feature/RegisterRoleGuardTest.php
- Registrasi via invitation -> tests/Feature/RegisterRoleGuardTest.php
- Login Google (konfigurasi dan access denied) -> tests/Feature/GoogleAuthFlowTest.php

## Akses dan undangan
- Pembatasan akses auditor ke client tertentu -> tests/Feature/AuditorClientScopeTest.php
- Validasi invitation auditor membutuhkan client -> tests/Feature/AuditorClientScopeTest.php
- Acceptance invitation auditor (termasuk legacy tanpa client) -> tests/Feature/AuditorClientScopeTest.php
- Acceptance invitation pada login user existing dan audit log -> tests/Feature/InvitationGovernanceTest.php
- Perubahan role oleh admin dan pembatalan invitation konflik -> tests/Feature/InvitationGovernanceTest.php

## Data request
- Upload multi file dan pembuatan versi -> tests/Feature/DataRequestUploadFlowTest.php
- Validasi file upload (type dan size) -> tests/Feature/DataRequestUploadValidationTest.php
- Filter table (status, tanggal, uploaded state) -> tests/Feature/DataRequestTableFilterTest.php
- Header table tetap tampil saat filter kosong -> tests/Feature/DataRequestTableFilterRenderingTest.php
- Request revisi dan notifikasi -> tests/Feature/AuditFlowTest.php
- Activity log pada perubahan status data request -> tests/Feature/AuditFlowTest.php

## Reminder dan notifikasi
- Followup milestone 7/15 hari dan idempotensi -> tests/Feature/FollowupReminderCommandTest.php

## Bootstrap admin
- Seeder super admin -> tests/Feature/SuperAdminSeederTest.php

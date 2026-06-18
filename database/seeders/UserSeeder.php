<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'nama'     => 'Administrator',
                'username' => 'admin',
                'email'    => 'admin@sekolah.sch.id',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ],
            [
                'nama'     => 'Budi Santoso',
                'username' => 'guru_budi',
                'email'    => 'budi.santoso@sekolah.sch.id',
                'password' => Hash::make('guru123'),
                'role'     => 'guru',
            ],
            [
                'nama'     => 'Siti Rahayu',
                'username' => 'siswa_siti',
                'email'    => 'siti.rahayu@sekolah.sch.id',
                'password' => Hash::make('siswa123'),
                'role'     => 'siswa',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }

        $this->command->info('✅ Seeder berhasil: 3 akun default dibuat (admin, guru, siswa)');
    }
}

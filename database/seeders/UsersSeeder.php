<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = SeederData::read();
        $rows = array_map(static function (array $row): array {
            $createdAt = $row['created_at'] ?? now()->toDateTimeString();
            $email = $row['username'] . '@minilog.local';

            return [
                'id' => $row['user_id'],
                'name' => $row['full_name'],
                'username' => $row['username'],
                'email' => $email,
                'email_verified_at' => null,
                'password' => $row['password_hash'],
                'role' => $row['role'],
                'department' => $row['department'] ?? null,
                'is_active' => (bool) $row['is_active'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }, $data['users'] ?? []);

        if ($rows === []) {
            return;
        }

        DB::table('users')->upsert(
            $rows,
            ['id'],
            ['name', 'username', 'email', 'email_verified_at', 'password', 'role', 'department', 'is_active', 'created_at', 'updated_at']
        );
    }
}

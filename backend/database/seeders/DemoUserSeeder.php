<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts listed in the README. Every account uses the password
 * "password". Safe to run more than once.
 */
class DemoUserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $admin = $this->user('Platform Admin', 'admin@edu-platform.test', Role::Admin);

        $teachers = [
            ['Ahmed Samir', 'teacher1@edu-platform.test', 'Physics', 'Physics teacher for secondary school with 10 years of experience.'],
            ['Mona Hassan', 'teacher2@edu-platform.test', 'Mathematics', 'Mathematics teacher specialised in algebra and calculus.'],
        ];

        foreach ($teachers as [$name, $email, $specialty, $bio]) {
            $this->user($name, $email, Role::Teacher)
                ->teacherProfile()
                ->updateOrCreate([], ['specialty' => $specialty, 'bio' => $bio, 'is_approved' => true]);
        }

        $students = [
            ['Omar Khaled', 'Grade 10', '01012345671'],
            ['Nour Ali', 'Grade 11', '01112345672'],
            ['Youssef Ibrahim', 'Grade 12', '01212345673'],
            ['Salma Mahmoud', 'Grade 11', '01512345674'],
            ['Karim Adel', 'Grade 12', null],
        ];

        foreach ($students as $i => [$name, $gradeLevel, $parentPhone]) {
            $this->user($name, 'student'.($i + 1).'@edu-platform.test', Role::Student)
                ->studentProfile()
                ->updateOrCreate([], ['grade_level' => $gradeLevel, 'parent_phone' => $parentPhone]);
        }

        $this->command->info("Demo users ready ({$admin->email} and others), password: ".self::PASSWORD);
    }

    private function user(string $name, string $email, Role $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => self::PASSWORD],
        );

        $user->syncRoles([$role]);

        return $user;
    }
}

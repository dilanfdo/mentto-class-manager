<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use App\Support\TenantProvisioner;
use Illuminate\Database\Seeder;

/**
 * Local demo data. All logins use the password "password".
 *   NexusDigitalLabs admin: admin@classmanager.test
 *   Tutor:                  amila@classmanager.test
 *   Institute:              office@brightfuture.test
 */
class DatabaseSeeder extends Seeder
{
    private const NAMES = [
        'Dulani Herath', 'Nethmi Perera', 'Kasun Abeysekara', 'Ravindu Senanayake', 'Tharushi Fernando',
        'Sachini Wickramasinghe', 'Pasindu Jayasuriya', 'Hiruni Dissanayake', 'Isuru Bandara', 'Chamodi Silva',
        'Yasiru Gunawardena', 'Minuri Rajapaksha', 'Sanduni Weerasinghe', 'Dineth Karunaratne', 'Ishara Mendis',
        'Nadeesha Ranasinghe', 'Kaveesha Liyanage', 'Thisara Wijesinghe', 'Oshadi Samarasinghe', 'Lahiru Ekanayake',
    ];

    public function run(TenantProvisioner $provisioner, TenantContext $context): void
    {
        User::create([
            'name' => 'NDL Admin',
            'email' => 'admin@classmanager.test',
            'password' => 'password',
            'role' => 'platform_admin',
        ]);

        $tutor = $provisioner->create([
            'name' => 'Amila Combined Maths',
            'type' => 'tutor',
            'contact_name' => 'Amila Rathnayake',
            'contact_email' => 'amila@classmanager.test',
            'contact_phone' => '071 000 0001',
        ], 'password');

        $institute = $provisioner->create([
            'name' => 'Bright Future Institute',
            'type' => 'institute',
            'contact_name' => 'Office',
            'contact_email' => 'office@brightfuture.test',
            'contact_phone' => '011 000 0002',
        ], 'password');
        $institute->features()->updateOrCreate(['feature' => 'sms'], ['enabled' => true]);
        $institute->features()->updateOrCreate(['feature' => 'parents'], ['enabled' => true]);

        $trial = $provisioner->create([
            'name' => 'Lakmini English Classes',
            'type' => 'tutor',
            'status' => 'trial',
            'contact_name' => 'Lakmini Jayawardena',
            'contact_email' => 'lakmini@classmanager.test',
        ], 'password');

        $provisioner->create([
            'name' => 'Rainbow Tuition Centre',
            'type' => 'institute',
            'status' => 'suspended',
            'contact_name' => 'Owner',
            'contact_email' => 'rainbow@classmanager.test',
            'notes' => 'Suspended for unpaid October invoice.',
        ], 'password');

        $this->fill($context, $tutor, [
            ['Combined Maths 2027 · Theory', 'online', 1, '18:30', 2500],
            ['Combined Maths 2026 · Revision', 'online', 3, '18:30', 3000],
            ['Paper class', 'online', 6, '08:00', 1500],
        ], 0, 14);

        $this->fill($context, $institute, [
            ['Physics 2026 A/L', 'physical', 2, '15:30', 2500, 'Hall A'],
            ['Chemistry 2026 A/L', 'physical', 4, '15:30', 2500, 'Hall B'],
            ['O/L Science Grade 11', 'physical', 6, '09:00', 2000, 'Hall C'],
            ['English Speaking', 'online', 7, '10:00', 1800, null],
        ], 4, 20);

        $this->fill($context, $trial, [['Spoken English · Beginners', 'online', 2, '19:00', 2000]], 10, 6);

        $context->set(null);
    }

    /** @param list<array{0: string, 1: string, 2: int, 3: string, 4: int, 5?: ?string}> $classes */
    private function fill(TenantContext $context, Tenant $tenant, array $classes, int $offset, int $count): void
    {
        $context->set($tenant->load('features'));
        $month = now()->format('Y-m');

        $groups = collect($classes)->map(fn ($c) => $tenant->classGroups()->create([
            'name' => $c[0], 'subject' => explode(' ', $c[0])[0], 'mode' => $c[1], 'day_of_week' => $c[2],
            'start_time' => $c[3], 'monthly_fee' => $c[4], 'hall' => $c[5] ?? null,
            'meeting_link' => $c[1] === 'online' ? 'https://zoom.us/j/1234567890' : null,
        ]));

        for ($i = 0; $i < $count; $i++) {
            $name = self::NAMES[($offset + $i) % count(self::NAMES)];
            $student = $tenant->students()->create([
                'name' => $name,
                'phone' => sprintf('07%d %03d %04d', 1 + $i % 8, 100 + $i * 7, 1000 + $i * 37),
                'parent_name' => 'Parent of '.explode(' ', $name)[0],
                'parent_phone' => sprintf('077 %03d %04d', 200 + $i * 3, 4000 + $i * 11),
                'grade' => $i % 3 === 0 ? '2026 A/L' : '2027 A/L',
            ]);

            $joined = $groups->filter(fn ($g, $k) => ($i + $k) % 2 === 0 || $k === 0);
            $student->classGroups()->attach($joined->pluck('id')->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tenant->id]]));

            foreach ($joined as $group) {
                // Most have paid; a few slips wait for approval and a few still owe.
                if ($i % 5 === 4) {
                    continue;
                }
                Payment::create([
                    'student_id' => $student->id, 'class_group_id' => $group->id, 'month' => $month,
                    'amount' => $group->monthly_fee, 'method' => ['bank', 'cash', 'card'][$i % 3],
                    'status' => $i % 7 === 3 ? 'pending' : 'approved',
                    'approved_at' => $i % 7 === 3 ? null : now(),
                ]);
            }
        }
    }
}

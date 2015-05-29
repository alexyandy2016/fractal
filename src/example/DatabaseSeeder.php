<?php namespace Appkr\Fractal\Example;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Eloquent;
use DB;

class DatabaseSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Eloquent::unguard();
        $faker = Faker::create();

        // Seeding managers table
        Manager::truncate();

        foreach (range(1, 10) as $index) {
            Manager::create([
                'name'  => $faker->userName,
                'email' => $faker->safeEmail
            ]);
        }

        $this->command->info('managers table - example data seeded.');

        // Seeding resources table
        Resource::truncate();
        $manager_ids = Manager::lists('id');

        foreach (range(1, 100) as $index) {
            Resource::create([
                'title'       => $faker->sentence(),
                'manager_id'  => $faker->randomElement($manager_ids),
                'description' => $faker->randomElement([$faker->paragraph(), null]),
                'deprecated'  => $faker->randomElement([0, 1])
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('resources table - example data seeded.');

    }

}

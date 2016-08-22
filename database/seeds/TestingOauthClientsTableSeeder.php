<?php

use Illuminate\Database\Seeder;

class TestingOauthClientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => bcrypt('test'),
        ]);

        DB::table('oauth_clients')->insert([
            'id' => 'a94a8fe5ccb19ba61c4c0873d391e987982fbbd3',
            'secret' => 'e5e9fa1ba31ecd1ae84f75caaa474f3a663f05f4',
            'name' => 'test',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('oauth_client_endpoints')->insert([
            'client_id' => 'a94a8fe5ccb19ba61c4c0873d391e987982fbbd3',
            'redirect_uri' => 'http://example.demo/redirect',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
    }
}

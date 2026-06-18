<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleMakeSeeder extends Seeder
{
    public function run(): void
    {
        $makes = $this->makesWithModels();

        foreach ($makes as $order => $make) {
            $makeId = (string) Str::uuid();

            DB::table('vehicle_makes')->insertOrIgnore([
                'id'         => $makeId,
                'name'       => $make['name'],
                'slug'       => Str::slug($make['name']),
                'sort_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($make['models'] as $model) {
                DB::table('vehicle_models')->insertOrIgnore([
                    'id'         => (string) Str::uuid(),
                    'make_id'    => $makeId,
                    'name'       => $model,
                    'slug'       => Str::slug($model),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function makesWithModels(): array
    {
        return [
            ['name' => 'Toyota',      'models' => ['Corolla', 'Camry', 'Hilux', 'Land Cruiser', 'RAV4', 'Fortuner', 'Prado', 'Prius', 'Yaris', 'Hiace', 'Rush', 'Vitz', 'Allion']],
            ['name' => 'Honda',       'models' => ['Civic', 'Accord', 'CR-V', 'HR-V', 'Fit', 'Jazz', 'Pilot', 'Odyssey', 'Freed', 'Vezel', 'Stream', 'Stepwgn']],
            ['name' => 'Nissan',      'models' => ['Navara', 'Patrol', 'X-Trail', 'Juke', 'Micra', 'Note', 'Qashqai', 'Tiida', 'NP300', 'Hardbody', 'Sentra', 'Almera']],
            ['name' => 'Mazda',       'models' => ['Mazda2', 'Mazda3', 'Mazda6', 'CX-3', 'CX-5', 'CX-9', 'BT-50', 'Demio', 'Atenza', 'Axela']],
            ['name' => 'Mitsubishi',  'models' => ['Pajero', 'Outlander', 'Eclipse Cross', 'L200', 'Triton', 'Galant', 'Lancer', 'ASX', 'Grandis', 'Colt']],
            ['name' => 'Isuzu',       'models' => ['D-Max', 'MU-X', 'KB', 'Trooper', 'Rodeo', 'Amigo', 'Ascender']],
            ['name' => 'Suzuki',      'models' => ['Swift', 'Vitara', 'Jimny', 'Alto', 'Grand Vitara', 'Ignis', 'Baleno', 'Ertiga', 'SX4']],
            ['name' => 'Subaru',      'models' => ['Forester', 'Outback', 'Impreza', 'Legacy', 'XV', 'WRX', 'BRZ', 'Tribeca']],
            ['name' => 'BMW',         'models' => ['1 Series', '2 Series', '3 Series', '4 Series', '5 Series', '7 Series', 'X1', 'X3', 'X5', 'X6', 'X7', 'M3', 'M5']],
            ['name' => 'Mercedes-Benz', 'models' => ['A-Class', 'B-Class', 'C-Class', 'E-Class', 'S-Class', 'GLA', 'GLC', 'GLE', 'GLS', 'Vito', 'Sprinter', 'ML']],
            ['name' => 'Volkswagen',  'models' => ['Golf', 'Polo', 'Passat', 'Tiguan', 'Touareg', 'Amarok', 'Transporter', 'Caddy', 'Jetta', 'Touran']],
            ['name' => 'Audi',        'models' => ['A1', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'Q2', 'Q3', 'Q5', 'Q7', 'Q8', 'TT']],
            ['name' => 'Ford',        'models' => ['Ranger', 'Everest', 'Explorer', 'F-150', 'Focus', 'Fiesta', 'EcoSport', 'Edge', 'Escape', 'Mustang', 'Transit']],
            ['name' => 'Chevrolet',   'models' => ['Captiva', 'Trailblazer', 'Colorado', 'Suburban', 'Tahoe', 'Cruze', 'Sonic', 'Spark', 'Silverado']],
            ['name' => 'Hyundai',     'models' => ['Tucson', 'Santa Fe', 'i10', 'i20', 'i30', 'Creta', 'Elantra', 'Sonata', 'H1', 'Accent', 'Venue']],
            ['name' => 'Kia',         'models' => ['Sportage', 'Sorento', 'Picanto', 'Rio', 'Cerato', 'Optima', 'Carnival', 'Seltos', 'Telluride']],
            ['name' => 'Land Rover',  'models' => ['Defender', 'Discovery', 'Discovery Sport', 'Range Rover', 'Range Rover Sport', 'Freelander', 'Evoque']],
            ['name' => 'Jeep',        'models' => ['Wrangler', 'Cherokee', 'Grand Cherokee', 'Compass', 'Renegade', 'Gladiator', 'Commander']],
            ['name' => 'Peugeot',     'models' => ['207', '208', '301', '308', '3008', '408', '5008', 'Partner', 'Expert', 'Boxer']],
            ['name' => 'Renault',     'models' => ['Clio', 'Megane', 'Duster', 'Kadjar', 'Koleos', 'Logan', 'Sandero', 'Trafic', 'Kangoo']],
            ['name' => 'Volvo',       'models' => ['XC40', 'XC60', 'XC90', 'S60', 'S90', 'V40', 'V60', 'V90']],
            ['name' => 'Lexus',       'models' => ['IS', 'ES', 'LS', 'GS', 'NX', 'RX', 'GX', 'LX', 'CT', 'UX']],
            ['name' => 'Infiniti',    'models' => ['Q30', 'Q50', 'Q60', 'Q70', 'QX30', 'QX50', 'QX60', 'QX70', 'QX80']],
            ['name' => 'Acura',       'models' => ['ILX', 'TLX', 'RLX', 'RDX', 'MDX', 'NSX']],
            ['name' => 'Porsche',     'models' => ['911', 'Cayenne', 'Macan', 'Panamera', 'Taycan', 'Boxster', 'Cayman']],
            ['name' => 'Jaguar',      'models' => ['F-Pace', 'E-Pace', 'I-Pace', 'XE', 'XF', 'XJ', 'F-Type']],
            ['name' => 'Fiat',        'models' => ['500', 'Punto', 'Bravo', 'Doblo', 'Ducato', 'Panda', 'Tipo', 'Fullback']],
            ['name' => 'Alfa Romeo',  'models' => ['Giulia', 'Stelvio', 'Giulietta', '4C', 'MiTo']],
            ['name' => 'SEAT',        'models' => ['Ibiza', 'Leon', 'Ateca', 'Arona', 'Tarraco', 'Alhambra']],
            ['name' => 'Skoda',       'models' => ['Octavia', 'Superb', 'Kodiaq', 'Karoq', 'Fabia', 'Rapid', 'Kamiq']],
            ['name' => 'Opel',        'models' => ['Astra', 'Insignia', 'Corsa', 'Mokka', 'Zafira', 'Vivaro', 'Grandland']],
            ['name' => 'Citroen',     'models' => ['C1', 'C3', 'C4', 'C5', 'Berlingo', 'Dispatch', 'Jumper', 'SpaceTourer']],
            ['name' => 'Dodge',       'models' => ['Durango', 'Journey', 'Challenger', 'Charger', 'Ram 1500', 'Ram 2500']],
            ['name' => 'Chrysler',    'models' => ['300', 'Grand Voyager', 'Pacifica', 'Crossfire']],
            ['name' => 'GMC',         'models' => ['Sierra', 'Canyon', 'Yukon', 'Terrain', 'Acadia', 'Envoy']],
            ['name' => 'Cadillac',    'models' => ['Escalade', 'XT5', 'XT6', 'CT4', 'CT5', 'XT4']],
            ['name' => 'Buick',       'models' => ['Enclave', 'Encore', 'Envision', 'LaCrosse', 'Verano']],
            ['name' => 'Lincoln',     'models' => ['Navigator', 'Aviator', 'Corsair', 'Nautilus', 'Continental']],
            ['name' => 'Tesla',       'models' => ['Model S', 'Model 3', 'Model X', 'Model Y', 'Cybertruck']],
            ['name' => 'BYD',         'models' => ['Atto 3', 'Han', 'Tang', 'Song', 'F3', 'Seal', 'Dolphin', 'Seagull']],
            ['name' => 'GWM',         'models' => ['P-Series', 'Steed', 'H6', 'C30', 'Tank 300', 'Jolion']],
            ['name' => 'Chery',       'models' => ['Tiggo 4', 'Tiggo 7', 'Tiggo 8', 'Arrizo 5', 'QQ', 'Fulwin']],
            ['name' => 'Geely',       'models' => ['Coolray', 'Okavango', 'Emgrand', 'Atlas', 'Binray', 'Boyue']],
            ['name' => 'Haval',       'models' => ['H1', 'H2', 'H6', 'H9', 'F7', 'Jolion']],
            ['name' => 'JAC',         'models' => ['S3', 'S5', 'T8', 'T6', 'J7', 'Sei 3']],
            ['name' => 'DFSK',        'models' => ['Glory 580', 'Glory 500', 'K01H', 'C31', 'C35']],
            ['name' => 'Foton',       'models' => ['Tunland', 'View CS2', 'Toano', 'Aumark', 'Gratour']],
            ['name' => 'Hino',        'models' => ['300 Series', '500 Series', '700 Series', 'Dutro']],
            ['name' => 'Mercedes Truck', 'models' => ['Actros', 'Atego', 'Axor', 'Arocs', 'Sprinter']],
            ['name' => 'MAN',         'models' => ['TGS', 'TGX', 'TGM', 'TGL', 'TGE']],
            ['name' => 'Scania',      'models' => ['R Series', 'S Series', 'P Series', 'G Series']],
            ['name' => 'Volvo Trucks', 'models' => ['FH', 'FM', 'FMX', 'FL', 'FE']],
            ['name' => 'DAF',         'models' => ['XF', 'CF', 'LF', 'XG']],
            ['name' => 'Iveco',       'models' => ['Daily', 'Eurocargo', 'Stralis', 'S-WAY', 'Trakker']],
            ['name' => 'Ashok Leyland', 'models' => ['Boss', 'Captain', 'Dost', 'Partner', 'U-Truck']],
            ['name' => 'Tata',        'models' => ['Xenon', 'Harrier', 'Nexon', 'Safari', 'Super Ace', 'LPT 1613']],
            ['name' => 'UD Trucks',   'models' => ['Quon', 'Quester', 'Condor', 'Croner']],
        ];
    }
}

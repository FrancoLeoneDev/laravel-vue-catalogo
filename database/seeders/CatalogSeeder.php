<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Realistic hardware-store catalog: 6 categories, 40 products, and a
     * movement history per product from which the current stock is derived.
     */
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();

        foreach ($this->catalog() as $categoryData) {
            $category = Category::query()->create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
            ]);

            foreach ($categoryData['products'] as $index => $productData) {
                $product = $category->products()->create([
                    'name' => $productData[0],
                    'slug' => Str::slug($productData[0]),
                    'description' => $productData[3],
                    'price' => $productData[1],
                    'sku' => $productData[2],
                    'is_active' => true,
                    'low_stock_threshold' => $productData[4],
                    'image_path' => null,
                ]);

                $this->seedMovements($product, $user?->id, $index);
            }
        }

        // A couple of discontinued items, so the admin listing shows both states.
        Product::query()->whereIn('sku', ['HM-1007', 'JD-6006'])->update(['is_active' => false]);
    }

    /**
     * Build an auditable movement history that lands on a plausible balance.
     *
     * Every third product is driven down to (or below) its threshold so the
     * dashboard's low-stock panel has real data to show.
     */
    private function seedMovements(Product $product, ?int $userId, int $index): void
    {
        $shouldRunLow = $index % 3 === 0;
        $target = $shouldRunLow
            ? random_int(0, $product->low_stock_threshold)
            : random_int($product->low_stock_threshold + 12, $product->low_stock_threshold + 90);

        $initial = $target + random_int(25, 120);
        $balance = 0;
        $movements = [];

        $date = Carbon::now()->subMonths(6)->addDays(random_int(0, 5));

        $movements[] = [
            'type' => StockMovementType::Entrada,
            'quantity' => $initial,
            'reason' => 'Carga inicial de inventario',
            'occurred_at' => $date->copy(),
        ];
        $balance += $initial;

        // Sell down toward the target, restocking whenever it gets too thin.
        while ($balance > $target) {
            $date = $date->copy()->addDays(random_int(3, 16));

            if ($date->isFuture()) {
                break;
            }

            $remaining = $balance - $target;
            $quantity = min($remaining, random_int(2, 18));

            $movements[] = [
                'type' => StockMovementType::Salida,
                'quantity' => $quantity,
                'reason' => fake()->randomElement([
                    'Venta en mostrador',
                    'Venta mayorista',
                    'Pedido online',
                    'Rotura en depósito',
                    'Ajuste por inventario físico',
                ]),
                'occurred_at' => $date->copy(),
            ];
            $balance -= $quantity;

            if ($balance <= $target && $shouldRunLow === false && random_int(1, 3) === 1) {
                $date = $date->copy()->addDays(random_int(2, 10));

                if ($date->isFuture()) {
                    break;
                }

                $restock = random_int(20, 60);
                $movements[] = [
                    'type' => StockMovementType::Entrada,
                    'quantity' => $restock,
                    'reason' => 'Reposición de proveedor',
                    'occurred_at' => $date->copy(),
                ];
                $balance += $restock;
                $target = $balance - random_int(5, 30);
            }
        }

        StockMovement::query()->insert(array_map(fn (array $movement): array => [
            'product_id' => $product->id,
            'user_id' => $userId,
            'type' => $movement['type']->value,
            'quantity' => $movement['quantity'],
            'reason' => $movement['reason'],
            'occurred_at' => $movement['occurred_at'],
            'created_at' => $movement['occurred_at'],
            'updated_at' => $movement['occurred_at'],
        ], $movements));
    }

    /**
     * @return array<int, array{name: string, description: string, products: array<int, array{0: string, 1: float, 2: string, 3: string, 4: int}>}>
     */
    private function catalog(): array
    {
        return [
            [
                'name' => 'Herramientas Manuales',
                'description' => 'Llaves, destornilladores, martillos y todo lo que se usa sin enchufar.',
                'products' => [
                    ['Martillo carpintero 27mm mango madera', 8900.00, 'HM-1001', 'Martillo de acero forjado con mango de madera dura, cabeza de 27mm. Ideal para carpintería y trabajos generales.', 8],
                    ['Juego de destornilladores 6 piezas', 12400.00, 'HM-1002', 'Set de 6 destornilladores punta plana y Phillips con mango ergonómico antideslizante y puntas imantadas.', 10],
                    ['Llave ajustable 10" cromo vanadio', 15600.00, 'HM-1003', 'Llave inglesa de 10 pulgadas en acero cromo vanadio, con escala milimétrica grabada y mordaza de apertura amplia.', 6],
                    ['Pinza universal 8" aislada 1000V', 13750.00, 'HM-1004', 'Pinza universal con aislación certificada hasta 1000V, filo para corte de cable y mango bicomponente.', 8],
                    ['Set de llaves Allen métricas 9 piezas', 7300.00, 'HM-1005', 'Nueve llaves hexagonales de 1.5 a 10mm en acero templado, con soporte plegable.', 12],
                    ['Arco de sierra 12" con hoja bimetálica', 9850.00, 'HM-1006', 'Arco de sierra con tensor rápido, mango cerrado y hoja bimetálica de 24 dientes por pulgada incluida.', 7],
                    ['Cinta métrica 5m carcasa reforzada', 6200.00, 'HM-1007', 'Cinta de 5 metros con freno automático, clip de cinturón y carcasa engomada resistente a caídas.', 15],
                ],
            ],
            [
                'name' => 'Herramientas Eléctricas',
                'description' => 'Taladros, amoladoras y máquinas para trabajos de mayor exigencia.',
                'products' => [
                    ['Taladro percutor 13mm 750W', 89900.00, 'HE-2001', 'Taladro percutor de 750W con mandril de 13mm, velocidad variable, reversa y empuñadura lateral regulable.', 5],
                    ['Amoladora angular 4.5" 900W', 76500.00, 'HE-2002', 'Amoladora de 115mm y 900W con arranque suave, protector regulable sin herramientas y bloqueo de eje.', 5],
                    ['Atornillador inalámbrico 12V con 2 baterías', 112000.00, 'HE-2003', 'Atornillador a batería de litio 12V, torque regulable en 18 posiciones, incluye dos baterías y cargador rápido.', 4],
                    ['Sierra caladora 650W velocidad variable', 84300.00, 'HE-2004', 'Caladora de 650W con corte pendular en 3 posiciones, base inclinable a 45° y cambio rápido de hoja.', 4],
                    ['Lijadora orbital 300W disco 125mm', 68900.00, 'HE-2005', 'Lijadora orbital con sistema de aspiración de polvo, velocidad regulable y disco de velcro de 125mm.', 5],
                    ['Rotomartillo SDS-Plus 800W', 156000.00, 'HE-2006', 'Rotomartillo SDS-Plus de 800W con tres funciones: perforar, percutir y cincelar. Incluye maletín.', 3],
                    ['Soldadora inverter 200A', 198000.00, 'HE-2007', 'Soldadora inverter de 200 amperes con display digital, protección térmica y accesorios de soldadura incluidos.', 3],
                ],
            ],
            [
                'name' => 'Electricidad',
                'description' => 'Cables, llaves térmicas, tomas y materiales para instalaciones eléctricas.',
                'products' => [
                    ['Cable unipolar 2.5mm x 100m', 145000.00, 'EL-3001', 'Rollo de 100 metros de cable unipolar de 2.5mm², normalizado IRAM, aislación de PVC antillama.', 6],
                    ['Llave térmica bipolar 25A', 18700.00, 'EL-3002', 'Interruptor termomagnético bipolar de 25A, curva C, montaje en riel DIN, poder de corte 6kA.', 10],
                    ['Disyuntor diferencial 2x40A 30mA', 34500.00, 'EL-3003', 'Disyuntor diferencial bipolar de 40A con sensibilidad de 30mA para protección de personas.', 8],
                    ['Tablero embutir 12 módulos', 27900.00, 'EL-3004', 'Tablero para embutir con capacidad de 12 módulos DIN, puerta abisagrada y barra de neutro y tierra.', 7],
                    ['Toma exterior IP54 con tapa', 5400.00, 'EL-3005', 'Tomacorriente exterior con grado de protección IP54, tapa con resorte y bornes a tornillo.', 20],
                    ['Lámpara LED 12W luz fría E27', 3200.00, 'EL-3006', 'Lámpara LED de 12W equivalente a 100W incandescente, 6500K, rosca E27 y vida útil de 25.000 horas.', 30],
                    ['Rollo cinta aisladora 20m x 19mm', 1850.00, 'EL-3007', 'Cinta aisladora de PVC, 20 metros por 19mm, resistente hasta 600V y temperaturas de -10°C a 80°C.', 40],
                ],
            ],
            [
                'name' => 'Plomería',
                'description' => 'Caños, griferías y accesorios para instalaciones de agua y desagüe.',
                'products' => [
                    ['Caño PPR fusión 20mm x 4m', 8600.00, 'PL-4001', 'Barra de 4 metros de caño PPR de 20mm para agua fría y caliente, apto termofusión, presión nominal 20.', 12],
                    ['Grifería monocomando cocina mesada', 87400.00, 'PL-4002', 'Grifería monocomando para cocina con pico móvil alto, cartucho cerámico y acabado cromado.', 5],
                    ['Flexible acero inoxidable 1/2" 40cm', 4300.00, 'PL-4003', 'Conexión flexible de acero inoxidable trenzado de 40cm con tuercas de bronce y junta incluida.', 25],
                    ['Codo PVC 110mm 90 grados', 3900.00, 'PL-4004', 'Codo de PVC de 110mm a 90 grados para desagüe cloacal, unión por junta elástica.', 20],
                    ['Válvula esférica bronce 1/2"', 9700.00, 'PL-4005', 'Válvula esférica de bronce de 1/2 pulgada, paso total, manija de aluminio y cierre a un cuarto de vuelta.', 15],
                    ['Rejilla de piso acero inoxidable 15x15', 11200.00, 'PL-4006', 'Rejilla de piso de 15x15cm en acero inoxidable con marco reforzado y canasto removible.', 10],
                    ['Cinta teflón 12mm x 10m', 900.00, 'PL-4007', 'Cinta selladora de teflón para roscas, 12mm de ancho por 10 metros, densidad estándar.', 50],
                ],
            ],
            [
                'name' => 'Pinturas y Accesorios',
                'description' => 'Látex, esmaltes, rodillos y todo lo necesario para pintar.',
                'products' => [
                    ['Látex interior mate blanco 20L', 98500.00, 'PI-5001', 'Pintura látex para interiores, acabado mate, alto poder cubritivo y bajo olor. Rinde hasta 12m² por litro.', 6],
                    ['Esmalte sintético brillante 1L', 21400.00, 'PI-5002', 'Esmalte sintético de alta resistencia para madera y metal, secado rápido y terminación brillante.', 12],
                    ['Rodillo lana 22cm con mango', 7800.00, 'PI-5003', 'Rodillo de lana natural de 22cm para superficies rugosas, con mango plástico ergonómico.', 15],
                    ['Pincel cerda natural 2"', 3600.00, 'PI-5004', 'Pincel de 2 pulgadas con cerda natural seleccionada, virola de acero inoxidable y mango de madera.', 25],
                    ['Enduido plástico interior 10kg', 32900.00, 'PI-5005', 'Enduido plástico listo para usar, ideal para nivelar paredes interiores antes de pintar.', 8],
                    ['Cinta de papel para pintor 24mm x 40m', 2900.00, 'PI-5006', 'Cinta de papel enmascarar de 24mm, remoción limpia hasta 7 días, apta para bordes prolijos.', 30],
                ],
            ],
            [
                'name' => 'Jardinería',
                'description' => 'Herramientas y accesorios para el cuidado de jardines y espacios verdes.',
                'products' => [
                    ['Manguera reforzada 1/2" x 20m', 24600.00, 'JD-6001', 'Manguera de PVC reforzada con malla textil, 20 metros, resistente a torceduras y rayos UV.', 10],
                    ['Tijera de podar bypass 8"', 16800.00, 'JD-6002', 'Tijera de podar bypass con hoja de acero al carbono templado, traba de seguridad y mangos antideslizantes.', 8],
                    ['Pala de punta con cabo de madera', 19300.00, 'JD-6003', 'Pala de punta forjada en acero, cabo de madera dura de 110cm con empuñadura en Y.', 7],
                    ['Bordeadora eléctrica 1000W', 94500.00, 'JD-6004', 'Bordeadora eléctrica de 1000W con cabezal de nylon de golpe, mango regulable y protector de corte.', 4],
                    ['Carretilla 90 litros rueda neumática', 78900.00, 'JD-6005', 'Carretilla de chapa reforzada de 90 litros con rueda neumática y bastidor pintado al horno.', 4],
                    ['Regadera plástica 10 litros', 8400.00, 'JD-6006', 'Regadera de polietileno de 10 litros con flor removible y asa reforzada de doble agarre.', 12],
                ],
            ],
        ];
    }
}

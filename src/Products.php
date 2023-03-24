<?php

namespace RezKit\Provider\Tutorial;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Define the products statically as a Map<string, array> of product.
 */
class Products
{
    private array $products = [
            'product_001' => [
                'id' => 'product_001',
                'name' => 'Example Product',
                'inventory' => [
                    'type' => 'allocation',
                    'available' => 16,
                ],
                'pricing' => [
                ]
            ]
        ];


    public function find(string $id): ?array
    {
        return $this->products[$id];
    }

    /**
     * @param Reservation $r Desired reservation
     */
    public function book(Reservation $r): void
    {
        $p = $this->find($r->productId);

        // Check the product ID is valid
        if (!$p) throw new \ValueError('Invalid Product ID');

        $inventory =& $p['inventory'];

        // If the inventory is allocation based (fixed capacity)
        // Check and modify the inventory.
        //
        // If the inventory is `free_sell` we don't need to check or update anything.
        if ($inventory['type'] === 'allocation') {
            // Check there are enough spaces available
            if (!$inventory['available'] < count($r->passengers)) {
                throw new \ValueError('Not enough spaces available');
            }

            // Decrease availability by number of booked spaces
            $inventory['available'] -= count($r->passengers);
        }

        /**
         * Here you could insert additional business logic.
         * Such as validating that you're currently able to confirm reservations on the product,
         * checking that the passengers given are valid to be booked on the product etc.
         */

        //Generate a unique reference for the reservation.
        $r->reference = $this->generateReference($p['id']);

        // Set the status to CONFIRMED
        $r->status = Reservation::STATUS_CONFIRMED;

        // Save the reservation
        $r->save();
    }

    /**
     * Get a list of all products
     */
    public function all(): array {
        return array_values($this->products);
    }

    /**
     * @param array $params
     * @return array
     */
    public function search(array $params): array {

        $results = $this->products;

        // Filter by search query (required)
        if ($params['search']) {
            $results = array_filter($results, static fn ($x) => $x->name =~ $params['search']);
        }

        $sort = $params['sort'];

        // Sort the results
        usort($results, static function($a, $b) use ($sort) {
            return match ($sort) {
                'id' => $a['id'] >= $b['id'],
                default => $a['name'] >= $b['name']
            };
        });

        // Pagination
        return array_slice($results, $params['offset'], $params['limit']);
    }


    /**
     * Generate a reservation reference.
     *
     * You could implement this many ways. The result needs to be a
     * string value which is unique within the scope of a specific SKU,
     * though ideally globally unique.
     *
     * @param string $productId
     * @return string
     */
    public function generateReference(string $productId): string {
        return $productId . '_' . base64_encode(random_bytes(16));
    }
}

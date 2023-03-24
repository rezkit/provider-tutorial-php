<?php

namespace RezKit\Provider\Tutorial;

/**
 * This is a simple model for a Reservation.
 * In this example we're storing the reservations in APCu for simplicity.
 * In a real application these would usually be stored in a database table.
 */
class Reservation
{
    public const STATUS_CANCELLED =  -1;

    public const STATUS_PENDING = 0;

    public const STATUS_CONFIRMED =  1;

    /**
     * @var string Reservation Reference
     */
    public string $reference;

    /**
     * @var string ID of the product the reservation is for
     */
    public string $productId;

    /**
     * @var string ID of the credential used to make the reservation
     */
    public string $credentialId;

    /**
     * @var array Passengers who are booked via this reservation.
     */
    public array $passengers;

    /**
     * @var int Current status of the booking.
     */
    public int $status = self::STATUS_PENDING;

    public function save():void
    {
        apcu_add("reservation:$this->reference", $this);
    }

    public static function find(string $reference): ?static
    {
        /**
         * @var Reservation|false $value
         */
        $value = apcu_fetch("reservation:$reference");

        if (!$value instanceof Reservation) return null;
        return $value;
    }
}

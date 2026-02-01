<?php

class WDDP_Booking{

    protected $id;
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $phone;
    protected $address;
    protected $city;
    protected $postal_code;

    protected $booking_date_from;
    protected $booking_date_to;
    protected $booking_pick_up_time;
    protected $booking_delivery_time;

    protected $dog_data;

    protected $status;

    protected $price;

    protected $notes;

    protected $rejection_reason;

    protected $created_at;

    protected $updated_at;

    protected $order_id;

    protected $data = [];

    protected $dog_names;

    protected $change_log;

    public function __construct($id)
    {
        $this->id = $id;
        $this->load();
    }

    protected function load()
    {
        global $wpdb;

        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d LIMIT 1",
                $this->id
            ),
            ARRAY_A
        );


        if (!$row) {
            throw new \Exception("Bookning med id $this->id findes ikke.");
        }

        // Gem alt i $this->data
        $this->data = $row;

        // (Valgfrit) sæt properties direkte:
        $this->first_name = $row['first_name'];
        $this->last_name = $row['last_name'];
        $this->email = $row['email'];
        $this->phone = $row['phone'];
        $this->address = $row['address'];
        $this->city = $row['city'];
        $this->postal_code = $row['postal_code'];

        $this->booking_date_from = $row['dropoff_date'];
        $this->booking_date_to = $row['pickup_date'];
        $this->booking_delivery_time = $row['dropoff_time'];
        $this->booking_pick_up_time = $row['pickup_time'];

        $this->dog_data = $row['dog_data'];
        $this->dog_names = $row['dog_names'];
        $this->status = $row['status'];
        $this->price = $row['price'];
        $this->notes = $row['notes'];
        $this->rejection_reason = $row['rejection_reason'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
        $this->order_id = $row['order_id'];
        $this->change_log = $row['change_log'];

    }

    public function getCustomerName()
    {
        return $this->data['first_name'] . ' ' . $this->data['last_name'];
    }

    public function getDogs(): array {
        $rawDogs = WDDP_DogHelper::decode($this->dog_data);

        if (!is_array($rawDogs)) return [];

        return array_map(function ($dogData) {
            return new WDDP_BookingDog($dogData);
        }, $rawDogs);
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * @return mixed
     */
    public function getBookingDateFrom()
    {
        return $this->booking_date_from;
    }

    /**
     * @return mixed
     */
    public function getBookingDateTo()
    {
        return $this->booking_date_to;
    }

    /**
     * @return mixed
     */
    public function getBookingPickUpTime()
    {
        return $this->booking_pick_up_time;
    }

    /**
     * @return mixed
     */
    public function getBookingDeliveryTime()
    {
        return $this->booking_delivery_time;
    }

    /**
     * @return mixed
     */
    public function getDogData()
    {
        return $this->dog_data;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getDogNames()
    {
        return $this->dog_names;
    }


    /**
     * @return mixed
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @return mixed
     */
    public function getRejectionReason()
    {
        return $this->rejection_reason;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function getChangeLog()
    {
        return $this->change_log;
    }

    public function hasChangeLog(){
        return !empty($this->change_log);
    }

}


class WDDP_BookingDog
{

    protected string $name;
    protected string $breed;
    protected int $age;
    protected float $weight;
    protected string $notes;

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? '';
        $this->breed = $data['breed'] ?? '';
        $this->age = (int)($data['age'] ?? 0);
        $this->weight = (float)($data['weight'] ?? 0);
        $this->notes = $data['notes'] ?? '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBreed(): string
    {
        return $this->breed;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function hasNotes(): bool
    {
        return !empty(trim($this->notes));
    }

    public function getSummary(): string
    {
        return sprintf('%s (%s)', $this->name, $this->breed);
    }

    public function getFullDescription(): string
    {
        return sprintf('%s (%s), %d år, %.1f kg', $this->name, $this->breed, $this->age, $this->weight);
    }
}

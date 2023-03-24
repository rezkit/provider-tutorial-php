<?php

namespace RezKit\Provider\Tutorial;

/**
 * This class generates the document that tells RezKit how our API is laid out and how to interact with it.
 */
class ServiceDescription
{
    public function __construct(private string $name)
    {

    }
    public function generateDescription(): array
    {
        return [
            'name' => $this->name,
            'version' => '0.1.0',

            'operations' => [
                'get_product' => [
                    'url' => '/products/:id'
                ]
            ],

            'services' => [
                'packages' => [
                    'operations' => [
                        'package_search' =>  [
                            'url' => '/products/search'
                        ],

                        'book_package' => [
                            'url' => '/book',
                        ],

                        'cancel_booking' => [
                            'url' => '/cancel'
                        ]
                    ]
                ]
            ]
        ];
    }
}

<?php

return [
    'accounts' => [
        [
            'email' => 'arturpatura@gmail.com',
            'data' => [
                'firstname' => 'Artur',
                'lastname' => 'Patura',
                'phone' => '723310782',
                'firm_identifier' => 'netextend.pl',
                'password' => 'Pass102@',
                'password_confirmation' => 'Pass102@'
            ],
            'workers' => [
                [
                    'firstname' => 'Jan',
                    'lastname' => 'Kowalski',
                    'email' => 'jan-kowalski@gmail.com',
                    'phone' => '879546254',
                    'superuser' => false,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ],
                [
                    'firstname' => 'Grzegorz',
                    'lastname' => 'Wąs',
                    'email' => 'grzegorz-was@gmail.com',
                    'phone' => '125963544',
                    'superuser' => true,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ],
                [
                    'firstname' => 'Zbigniew',
                    'lastname' => 'Nowak',
                    'email' => 'zbigniew-nowak@gmail.com',
                    'phone' => '878555415',
                    'superuser' => false,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ],
                [
                    'firstname' => 'Jan',
                    'lastname' => 'Byk',
                    'email' => 'a.rturpatura@gmail.com',
                    'phone' => '878555415',
                    'superuser' => false,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ],
            ],
            'projects' => [
                [
                    'data' => [
                        'name' => 'First project (netextend.pl)',
                        'description' => 'Lorem ipsum dolor sit amet'    
                    ],
                    'tasks' => [
                        [
                            'name' => 'P1 First task (netextend.pl)',
                        ],
                        [
                            'name' => 'P1 Second task (netextend.pl)',
                        ],
                        [
                            'name' => 'P1 Third task (netextend.pl)',
                        ]
                    ]
                ],
                [
                    'data' => [
                        'name' => 'Second project (netextend.pl)',
                        'description' => 'Lorem ipsum dolor sit amet'    
                    ],
                    'tasks' => [
                        [
                            'name' => 'P2 First task (netextend.pl)',
                        ],
                        [
                            'name' => 'P2 Second task (netextend.pl)',
                        ],
                    ]
                ]
            ]
        ],
        [
            'email' => 'a.rturpatura@gmail.com',
            'data' => [
                'firstname' => 'Jan',
                'lastname' => 'Kowalski',
                'phone' => '723310783',
                'firm_identifier' => 'Firma Pana Jana',
                'password' => 'Pass102@',
                'password_confirmation' => 'Pass102@'
            ],
            'workers' => [
                [
                    'firstname' => 'Błażej',
                    'lastname' => 'Wąsik',
                    'email' => 'blazej-wasik@gmail.com',
                    'phone' => '879546267',
                    'superuser' => false,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ],
                [
                    'firstname' => 'Krystyna',
                    'lastname' => 'Nowak',
                    'email' => 'nowak-krystyna@gmail.com',
                    'phone' => '125962345',
                    'superuser' => true,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ],
                [
                    'firstname' => 'Artur',
                    'lastname' => 'Patura - pracownik',
                    'email' => 'arturpatura@gmail.com',
                    'phone' => '125962345',
                    'superuser' => false,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ]
            ],
            'projects' => [
                [
                    'data' => [
                        'name' => 'First project (Firma Pana Jana)',
                        'description' => 'Lorem ipsum dolor sit amet'
                    ],
                    'tasks' => [
                        [
                            'name' => 'P1 First task (Firma Pana Jana)',
                        ],
                    ]
                ],
                [
                    'data' => [
                        'name' => 'Second project (Firma Pana Jana)',
                        'description' => 'Lorem ipsum dolor sit amet'    
                    ],
                    'tasks' => [
                        [
                            'name' => 'P2 First task (Firma Pana Jana)',
                        ],
                        [
                            'name' => 'P2 Second task (Firma Pana Jana)',
                        ],
                        [
                            'name' => 'P2 Third task (Firma Pana Jana)',
                        ]
                    ]
                ]
            ]
        ],
        [
            'email' => 'ar.turpatura@gmail.com',
            'data' => [
                'firstname' => 'Mariusz',
                'lastname' => 'Bąk',
                'phone' => '723310784',
                'firm_identifier' => 'Bąk i Synowie',
                'password' => 'Pass102@',
                'password_confirmation' => 'Pass102@'
            ],
            'workers' => [
                [
                    'firstname' => 'Artur',
                    'lastname' => 'Patura - pracownik',
                    'email' => 'arturpatura@gmail.com',
                    'phone' => '125962345',
                    'superuser' => false,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ],
                [
                    'firstname' => 'Artur',
                    'lastname' => 'Patura - pracownik do sprawdzania uprawnień',
                    'email' => 'art.urpa.tura@gmail.com',
                    'phone' => '125962345',
                    'superuser' => false,
                    'password' => 'Pass102@',
                    'password_confirmation' => 'Pass102@',
                ]
            ],
            'projects' => [
                [
                    'data' => [
                        'name' => 'First project (Bąk i Synowie)',
                        'description' => 'Lorem ipsum dolor sit amet'
                    ],
                    'tasks' => [
                        [
                            'name' => 'P1 First task (Bąk i Synowie)',
                        ],
                        [
                            'name' => 'P1 Secont task (Bąk i Synowie)',
                        ],
                    ]
                ],
                [
                    'data' => [
                        'name' => 'Second project (Bąk i Synowie)',
                        'description' => 'Lorem ipsum dolor sit amet'
                    ],
                    'tasks' => [
                        [
                            'name' => 'P2 First task (Bąk i Synowie)',
                        ],
                    ]
                ]
            ]
        ],
    ]
];
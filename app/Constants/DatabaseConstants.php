<?php

/**
 * Definice konstant pro názvy sloupců a tabulek databáze
 */

define('TABLE_RESERVATIONS',                  'reservations');
define('RESERVATION_ID',                      'reservation_id');
define('RESERVATION_USER_ID',                 'reservation_user_id');
define('RESERVATION_ROOM_ID',                 'reservation_room_id');
define('RESERVATION_DATE_FROM',               'reservation_date_from');
define('RESERVATION_DATE_TO',                 'reservation_date_to');
define('RESERVATION_CONFIRMED',               'reservation_confirmed');
define('RESERVATION_CHECK_IN',                'reservation_check_in');
define('RESERVATION_CHECK_OUT',               'reservation_check_out');
define('RESERVATION_USER_NAME',               'reservation_user_name');
define('RESERVATION_USER_SURNAME',            'reservation_user_surname');
define('RESERVATION_USER_PHONE',              'reservation_user_phone');
define('RESERVATION_USER_EMAIL',              'reservation_user_email');

define('TABLE_ROOMS',                         'rooms');
define('ROOM_ID',                             'room_id');
define('ROOM_HOTEL_ID',                       'room_hotel_id');
define('ROOM_CAPACITY',                       'room_capacity');
define('ROOM_PRICE',                          'room_price');
define('ROOM_TYPE',                           'room_type');
define('ROOM_NUMBER',                         'room_number');

define('TABLE_ROOM_IMAGES',                   'room_images');
define('IMAGE_ID',                            'image_id');
define('IMAGE_ROOM_ID',                       'image_room_id');
define('IMAGE_PATH',                          'image_path');

define('TABLE_HOTELS',                        'hotel');
define('HOTEL_ID',                            'hotel_id');
define('HOTEL_NAME',                          'hotel_name');
define('HOTEL_CITY',                          'hotel_city');
define('HOTEL_ADDRESS',                       'hotel_address');
define('HOTEL_STAR_RATING',                   'hotel_star_rating');
define('HOTEL_DESCRIPTION',                   'hotel_description');
define('HOTEL_PHONE',                         'hotel_phone');
define('HOTEL_EMAIL',                         'hotel_email');
define('HOTEL_OWNER_ID',                      'hotel_owner_id');

define('TABLE_HOTEL_RECEPTIONISTS',           'hotel_receptionists');
define('HOTEL_RECEPTIONIST_ID',               'hotel_receptionist_id');

define('TABLE_HOTEL_IMAGES',                  'hotel_images');
define('IMAGE_HOTEL_ID',                      'image_hotel_id');

define('TABLE_USERS',                         'users');
define('USER_ID',                             'user_id');
define('USER_NAME',                           'user_name');
define('USER_SURNAME',                        'user_surname');
define('USER_EMAIL',                          'user_email');
define('USER_PHONE',                          'user_phone');
define('USER_LOGIN',                          'user_login');
define('USER_PASSWORD',                       'user_password');
define('USER_REGISTERED_DATE',                'user_registered_date');

define('TABLE_ROLES',                         'roles');
define('ROLE_ID',                             'role_id');
define('ROLE_NAME',                           'role_name');

define('TABLE_USER_ROLES',                    'user_roles');
define('USER_ROLE_ID',                        'user_role_id');

define('TABLE_EQUIPMENT',                     'equipment');
define('EQUIPMENT_ID',                        'equipment_id');
define('EQUIPMENT_NAME',                      'equipment_name');

define('TABLE_ROOM_EQUIPMENT',                'room_equipment');
define('ROOM_EQUIPMENT_ID',                   'room_equipment_id');
<?php

declare(strict_types=1);

/**
 * English translations — Hôtel Le Lézard Bleu & Spa
 */

return [
    // ── Navigation ────────────────────────────────────────────────────────
    'nav.home'          => 'Home',
    'nav.presentation'  => 'About Us',
    'nav.rooms'         => 'Suites & Villas',
    'nav.reservation'   => 'Book Now',
    'nav.gallery'       => 'Gallery',
    'nav.services'      => 'Services & Dining',
    'nav.conferences'   => 'Conferences',
    'nav.offers'        => 'Offers',
    'nav.contact'       => 'Contact',
    'nav.login'         => 'Log In',
    'nav.register'      => 'Sign Up',
    'nav.my_account'    => 'My Account',
    'nav.logout'        => 'Log Out',

    // ── General ───────────────────────────────────────────────────────────
    'app.name'          => 'Hôtel Le Lézard Bleu & Spa',
    'app.tagline'       => 'Luxury Hospitality on the Shores of Lake Tanganyika',
    'app.location'      => 'Bujumbura, Burundi',
    'btn.book_now'      => 'Book Your Stay',
    'btn.discover'      => 'Discover the Hotel',
    'btn.see_all'       => 'See All',
    'btn.send'          => 'Send',
    'btn.confirm'       => 'Confirm',
    'btn.cancel'        => 'Cancel',
    'btn.back'          => 'Back',
    'btn.print'         => 'Print',
    'btn.download'      => 'Download',

    // ── Booking ───────────────────────────────────────────────────────────
    'booking.checkin'       => 'Check-in Date',
    'booking.checkout'      => 'Check-out Date',
    'booking.guests'        => 'Guests',
    'booking.nights'        => 'Night(s)',
    'booking.rooms'         => 'Available Rooms',
    'booking.select_room'   => 'Select this Suite',
    'booking.selected'      => 'Selected ✓',
    'booking.per_night'     => 'per night',
    'booking.total'         => 'Total Amount',
    'booking.reference'     => 'Booking Reference',
    'booking.confirmed'     => 'Booking Confirmed!',
    'booking.cancellation'  => 'Free cancellation up to :hours hours before arrival',
    'booking.guest_name'    => 'Full Name',
    'booking.email'         => 'Email Address',
    'booking.phone'         => 'Phone Number',
    'booking.payment_method'=> 'Payment Method',

    // ── Payments ──────────────────────────────────────────────────────────
    'payment.cash_bif'      => 'Cash BIF (on arrival)',
    'payment.cash_usd'      => 'Cash USD (on arrival)',
    'payment.lumicash'      => 'Lumicash (Lumitel Mobile Money)',
    'payment.ecocash'       => 'EcoCash / PesaFlash (Econet Leo)',
    'payment.bank_local'    => 'Local Bank Transfer (Burundi)',
    'payment.visa'          => 'VISA / MasterCard',
    'payment.paypal'        => 'PayPal',
    'payment.mobile_phone'  => 'Mobile Money Number',
    'payment.bank_select'   => 'Select your bank',
    'payment.rate_used'     => 'Rate used: 1 USD = :rate BIF',
    'payment.pending'       => 'Payment Pending',
    'payment.successful'    => 'Payment Confirmed',
    'payment.failed'        => 'Payment Failed',
    'payment.expired'       => 'Payment Expired',

    // ── Services ──────────────────────────────────────────────────────────
    'services.spa'          => 'Spa & Wellness',
    'services.restaurant'   => 'Gourmet Restaurant',
    'services.breakfast'    => 'Breakfast',
    'services.transfer'     => 'Airport Transfer (Ndadaye)',
    'services.pool'         => 'Pool & Lake Tanganyika Beach',
    'services.conference'   => 'Conference Rooms',
    'services.excursion'    => 'Lake Excursions',
    'services.wedding'      => 'Weddings & Events',
    'services.per_person'   => 'per person',
    'services.per_session'  => 'per session',
    'services.per_trip'     => 'per trip',

    // ── Errors ────────────────────────────────────────────────────────────
    'error.required'        => 'This field is required.',
    'error.invalid_email'   => 'Invalid email address.',
    'error.invalid_phone'   => 'Invalid phone number.',
    'error.invalid_date'    => 'Invalid date.',
    'error.dates_order'     => 'Check-out date must be after check-in.',
    'error.room_unavailable'=> 'This room is no longer available for these dates.',
    'error.booking_failed'  => 'Booking failed. Please try again.',
    'error.payment_failed'  => 'Payment failed. Please check your details.',
    'error.session_expired' => 'Your session has expired. Please log in again.',
    'error.access_denied'   => 'Access denied.',
    'error.not_found'       => 'Page not found.',
    'error.server_error'    => 'Internal error. Please try again.',
    'error.rate_limit'      => 'Too many attempts. Please try again in :minutes minutes.',
    'error.csrf'            => 'Security token invalid. Please reload the page.',

    // ── Authentication ────────────────────────────────────────────────────
    'auth.login'            => 'Log In',
    'auth.register'         => 'Create Account',
    'auth.logout'           => 'Log Out',
    'auth.email'            => 'Email Address',
    'auth.password'         => 'Password',
    'auth.password_confirm' => 'Confirm Password',
    'auth.forgot_password'  => 'Forgot password?',
    'auth.reset_password'   => 'Reset Password',
    'auth.remember_me'      => 'Remember me',
    'auth.login_success'    => 'Successfully logged in. Welcome!',
    'auth.login_failed'     => 'Incorrect email or password.',
    'auth.account_locked'   => 'Account temporarily locked. Try again in :minutes minutes.',
    'auth.email_verify_sent'=> 'A verification link has been sent to your email.',
    'auth.email_verified'   => 'Your email address has been verified.',
    'auth.register_success' => 'Account created successfully!',
    'auth.password_changed' => 'Password successfully changed.',
    'auth.password_min'     => 'Password must be at least :min characters.',
    'auth.password_mismatch'=> 'Passwords do not match.',

    // ── Account ───────────────────────────────────────────────────────────
    'account.my_bookings'   => 'My Bookings',
    'account.my_invoices'   => 'My Invoices',
    'account.my_profile'    => 'My Profile',
    'account.my_preferences'=> 'My Preferences',
    'account.no_bookings'   => 'You have no bookings yet.',

    // ── Currency ──────────────────────────────────────────────────────────
    'currency.bif'          => 'Burundian Francs',
    'currency.usd'          => 'US Dollars',
    'currency.switch'       => 'Switch Currency',
    'currency.rate'         => 'Rate: 1 USD = :rate BIF',
];

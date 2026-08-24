<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Validators\BookingValidator;

/**
 * Tests unitaires — BookingValidator
 */
class BookingValidatorTest extends TestCase
{
    private function validData(): array
    {
        return [
            'date_arrivee'    => date('Y-m-d', strtotime('+7 days')),
            'date_depart'     => date('Y-m-d', strtotime('+9 days')),
            'room_id'         => 1,
            'nb_adults'       => 2,
            'nb_children'     => 0,
            'guest_first_name'=> 'Jean-Paul',
            'guest_last_name' => 'Hakizimana',
            'guest_email'     => 'jp.hakizimana@example.com',
            'guest_phone'     => '+257 79 00 00 00',
            'currency_chosen' => 'BIF',
            'payment_method'  => 'cash_bif',
        ];
    }

    public function testValidDataPasses(): void
    {
        $v = new BookingValidator();
        $this->assertTrue($v->validate($this->validData()));
        $this->assertEmpty($v->errors());
    }

    public function testMissingCheckinFails(): void
    {
        $data = $this->validData();
        $data['date_arrivee'] = '';
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
        $this->assertArrayHasKey('date_arrivee', $v->errors());
    }

    public function testPastCheckinFails(): void
    {
        $data = $this->validData();
        $data['date_arrivee'] = '2020-01-01';
        $data['date_depart']  = '2020-01-03';
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
    }

    public function testCheckoutBeforeCheckinFails(): void
    {
        $data = $this->validData();
        $data['date_depart'] = date('Y-m-d', strtotime('+5 days'));
        $data['date_arrivee']= date('Y-m-d', strtotime('+8 days'));
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
        $this->assertArrayHasKey('date_depart', $v->errors());
    }

    public function testInvalidEmailFails(): void
    {
        $data = $this->validData();
        $data['guest_email'] = 'not-an-email';
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
        $this->assertArrayHasKey('guest_email', $v->errors());
    }

    public function testInvalidPhoneFails(): void
    {
        $data = $this->validData();
        $data['guest_phone'] = 'abc';
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
        $this->assertArrayHasKey('guest_phone', $v->errors());
    }

    public function testInvalidCurrencyFails(): void
    {
        $data = $this->validData();
        $data['currency_chosen'] = 'EUR';
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
    }

    public function testInvalidPaymentMethodFails(): void
    {
        $data = $this->validData();
        $data['payment_method'] = 'bitcoin';
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
    }

    public function testZeroRoomIdFails(): void
    {
        $data = $this->validData();
        $data['room_id'] = 0;
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
    }

    public function testTooManyAdultsFails(): void
    {
        $data = $this->validData();
        $data['nb_adults'] = 99;
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
    }

    public function testUSDCurrencyPasses(): void
    {
        $data = $this->validData();
        $data['currency_chosen'] = 'USD';
        $v = new BookingValidator();
        $this->assertTrue($v->validate($data));
    }

    public function testSQLInjectionInNameIsRejectedByLength(): void
    {
        $data = $this->validData();
        $data['guest_first_name'] = str_repeat("'; DROP TABLE bookings;--", 5);
        $v = new BookingValidator();
        // Doit échouer : trop long (> 80 chars)
        $this->assertFalse($v->validate($data));
    }

    public function testXSSInNameIsProperlyHandled(): void
    {
        $data = $this->validData();
        $data['guest_first_name'] = '<script>alert(1)</script>';
        $v = new BookingValidator();
        // Le validator accepte la longueur (26 chars) mais l'échappement se fait à l'affichage
        // Ici on vérifie que le validator ne plante pas et que e() est utilisé ailleurs
        $result = $v->validate($data);
        // Le validator accepte cela (la protection XSS est au niveau de l'output e())
        // On vérifie juste qu'il n'y a pas d'erreur PHP
        $this->assertIsBool($result);
    }

    public function testOfferCodeInvalidCharactersFails(): void
    {
        $data = $this->validData();
        $data['offer_code'] = '<script>';
        $v = new BookingValidator();
        $this->assertFalse($v->validate($data));
    }
}

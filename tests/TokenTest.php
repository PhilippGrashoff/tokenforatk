<?php declare(strict_types=1);

namespace tokenforatk\tests;

use DateTime;
use tokenforatk\Token;
use traitsforatkdata\UserException;
use traitsforatkdata\TestCase;

class TokenTest extends TestCase
{

    protected $sqlitePersistenceModels = [
        Token::class
    ];

    public function testTokenLength()
    {
        $persistence = $this->getSqliteTestPersistence();
        $t = new Token($persistence);
        $t->save();
        self::assertEquals(
            64,
            strlen($t->get('value'))
        );

        $t = new Token($persistence, ['tokenLength' => 128]);
        $t->save();
        self::assertEquals(
            128,
            strlen($t->get('value'))
        );
    }

    public function testSetExpires()
    {
        $persistence = $this->getSqliteTestPersistence();
        $t = new Token($persistence, ['expiresAfterInMinutes' => 180]);
        $t->save();
        self::assertEquals(
            (new DateTime())->modify('+180 Minutes')->format('Ymd Hi'),
            $t->get('expires')->format('Ymd Hi')
        );
    }

    public function testExceptionLoadExpired()
    {
        $persistence = $this->getSqliteTestPersistence();
        $t = new Token($persistence);
        $t->reload_after_save = false;
        $t->set('expires', (new DateTime())->modify('-1 Minutes'));
        $t->save();

        self::expectException(UserException::class);
        $t->reload();
    }
}

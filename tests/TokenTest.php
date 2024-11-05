<?php declare(strict_types=1);

namespace PhilippR\Atk4\Token\Tests;

use Atk4\Data\Exception;
use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use DateTime;
use PhilippR\Atk4\Token\Tests\Testclasses\OtherTestModel;
use PhilippR\Atk4\Token\Tests\Testclasses\TestModel;
use PhilippR\Atk4\Token\Token;

class TokenTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new Token($this->db))->create();
        $this->createMigrator(new TestModel($this->db))->create();
        $this->createMigrator(new OtherTestModel($this->db))->create();
    }

    public function testTokenLength(): void
    {
        $token = (new Token($this->db))->createEntity();
        $token->save();
        self::assertEquals(
            64,
            strlen($token->get('token'))
        );

        $token = (new Token($this->db, ['tokenLength' => 128]))->createEntity();
        $token->save();
        self::assertEquals(
            128,
            strlen($token->get('token'))
        );
    }

    public function testSetExpires(): void
    {
        $token = (new Token($this->db, ['expiresAfterInMinutes' => 180]))->createEntity();
        $token->save();
        self::assertEquals(
            (new DateTime())->modify('+180 Minutes')->format('Ymd Hi'),
            $token->get('expires')->format('Ymd Hi')
        );
    }

    public function testExceptionLoadExpired(): void
    {
        $token = (new Token($this->db))->createEntity();
        $token->set('expires', (new DateTime())->modify('-1 Minutes'));
        $token->save();

        self::expectException(Exception::class);
        self::expectExceptionMessage('The token is expired');
        $token->reload();
    }
}

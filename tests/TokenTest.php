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
        self::expectExceptionMessage('The Token expired');
        $token->reload();
    }

    public function testLoadTokenForEntity(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, []);
        self::assertSame($testModelEntity->getId(), $t->get('model_id'));

        $t2 = Token::loadTokenForEntity($testModelEntity, $t->get('token'));
        self::assertSame($t->getId(), $t2->getId());
    }

    public function testLoadTokenForEntityExceptionEntityNotLoaded(): void
    {
        $testModel = (new TestModel($this->db))->createEntity();
        self::expectException(Exception::class);
        Token::loadTokenForEntity($testModel, '12345');
    }

    public function testLoadTokenForEntityExceptionTokenNotFound()
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        self::expectException(UserException::class);
        Token::loadTokenForEntity($testModelEntity, '12345');
    }

    public function testLoadTokenForEntityExceptionWrongEntityClass(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, '');

        $otherTestModelEntity = new OtherTestModel($this->db);
        $otherTestModelEntity->save();
        self::expectException(UserException::class);
        Token::loadTokenForEntity($otherTestModelEntity, $t->get('token'));
    }

    public function testLoadTokenForEntityExceptionWrongEntityId(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, '');

        $testModelEntity2 = (new TestModel($this->db))->createEntity();
        $testModelEntity2->save();
        self::expectException(UserException::class);
        Token::loadTokenForEntity($testModelEntity2, $t->get('token'));
    }

    public function testCreateTokenForEntity(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $token = Token::createTokenForEntity($testModelEntity);
        self::assertSame(64, strlen($token->get('token')));
        self::assertSame($testModelEntity->getId(), $token->get('model_id'));
        self::assertSame(TestModel::class, $token->get('model_class'));
    }

    public function testGetTokenForEntity(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $token = Token::createTokenForEntity($testModelEntity);
        self::assertSame(64, strlen($token->get('token')));
        self::assertSame($testModelEntity->getId(), $token->get('model_id'));
        self::assertSame(TestModel::class, $token->get('model_class'));
    }
}

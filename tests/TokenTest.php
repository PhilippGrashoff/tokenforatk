<?php declare(strict_types=1);

namespace tokenforatk\tests;

use Atk4\Data\Exception;
use DateTime;
use PHPUnit\Util\Test;
use tokenforatk\tests\testclasses\OtherTestModel;
use tokenforatk\tests\testclasses\TestModel;
use tokenforatk\Token;
use traitsforatkdata\UserException;
use traitsforatkdata\TestCase;

class TokenTest extends TestCase
{

    protected $sqlitePersistenceModels = [
        Token::class,
        TestModel::class,
        OtherTestModel::class
    ];

    public function testTokenLength(): void
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

    public function testSetExpires(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $t = new Token($persistence, ['expiresAfterInMinutes' => 180]);
        $t->save();
        self::assertEquals(
            (new DateTime())->modify('+180 Minutes')->format('Ymd Hi'),
            $t->get('expires')->format('Ymd Hi')
        );
    }

    public function testExceptionLoadExpired(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $t = new Token($persistence);
        $t->reload_after_save = false;
        $t->set('expires', (new DateTime())->modify('-1 Minutes'));
        $t->save();

        self::expectException(UserException::class);
        $t->reload();
    }

    public function testLoadTokenForEntity(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testModelEntity = new TestModel($persistence);
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, '');
        self::assertSame($testModelEntity->getId(), $t->get('model_id'));

        $t2 = Token::loadTokenForEntity($testModelEntity, $t->get('value'));
        self::assertSame($t->getId(), $t2->getId());
    }

    public function testLoadTokenForEntityExceptionEntityNotLoaded()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testModel = new TestModel($persistence);
        self::expectException(Exception::class);
        Token::loadTokenForEntity($testModel, '12345');
    }

    public function testLoadTokenForEntityExceptionTokenNotFound()
    {
        $persistence = $this->getSqliteTestPersistence();
        $testModelEntity = new TestModel($persistence);
        $testModelEntity->save();
        self::expectException(UserException::class);
        Token::loadTokenForEntity($testModelEntity, '12345');
    }

    public function testLoadTokenForEntityExceptionWrongEntityClass(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testModelEntity = new TestModel($persistence);
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, '');

        $otherTestModelEntity = new OtherTestModel($persistence);
        $otherTestModelEntity->save();
        self::expectException(UserException::class);
        Token::loadTokenForEntity($otherTestModelEntity, $t->get('value'));
    }

    public function testLoadTokenForEntityExceptionWrongEntityId(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testModelEntity = new TestModel($persistence);
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, '');

        $testModelEntity2 = new TestModel($persistence);
        $testModelEntity2->save();
        self::expectException(UserException::class);
        Token::loadTokenForEntity($testModelEntity2, $t->get('value'));
    }

    public function testCreateTokenForEntity(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testModelEntity = new TestModel($persistence);
        $testModelEntity->save();
        $token = Token::createTokenForEntity($testModelEntity);
        self::assertSame(64, strlen($token->get('value')));
        self::assertSame($testModelEntity->getId(), $token->get('model_id'));
        self::assertSame(TestModel::class, $token->get('model_class'));
    }

    public function testGetTokenForEntity(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $testModelEntity = new TestModel($persistence);
        $testModelEntity->save();
        $token = Token::createTokenForEntity($testModelEntity);
        self::assertSame(64, strlen($token->get('value')));
        self::assertSame($testModelEntity->getId(), $token->get('model_id'));
        self::assertSame(TestModel::class, $token->get('model_class'));
    }

    public function testGetTokenString(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $token = new Token($persistence);
        $token->save();
        self::assertSame(64, strlen($token->getTokenString()));
        self::assertSame($token->get('value'), $token->getTokenString());
    }

    public function testGetTokenStringExceptionThisNotLoaded(): void
    {
        $persistence = $this->getSqliteTestPersistence();
        $token = new Token($persistence);
        self::expectException(Exception::class);
        $token->getTokenString();
    }
}

<?php declare(strict_types=1);

namespace PhilippR\Atk4\Token\Tests;

use Atk4\Data\Exception;
use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use PhilippR\Atk4\Token\Tests\Testclasses\OtherTestModel;
use PhilippR\Atk4\Token\Tests\Testclasses\TestModel;
use PhilippR\Atk4\Token\Token;
use PhilippR\Atk4\Token\TokenController;

class TokenControllerTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new Token($this->db))->create();
        $this->createMigrator(new TestModel($this->db))->create();
        $this->createMigrator(new OtherTestModel($this->db))->create();
    }

    public function testLoadTokenForEntity(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, []);
        self::assertSame($testModelEntity->getId(), $t->get('model_id'));

        $t2 = TokenController::loadTokenForEntity($testModelEntity, $t->get('token'));
        self::assertSame($t->getId(), $t2->getId());
    }

    public function testLoadTokenForEntityExceptionEntityNotLoaded(): void
    {
        $testModel = (new TestModel($this->db))->createEntity();
        self::expectException(Exception::class);
        self::expectExceptionMessage('Expected loaded entity');
        TokenController::loadTokenForEntity($testModel, '12345');
    }

    public function testLoadTokenForEntityExceptionTokenNotFound(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        self::expectException(Exception::class);
        self::expectExceptionMessage('The token for this entity could not be found');
        TokenController::loadTokenForEntity($testModelEntity, '12345');
    }

    public function testLoadTokenForEntityExceptionWrongEntityClass(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, []);

        $otherTestModelEntity = (new OtherTestModel($this->db))->createEntity();
        $otherTestModelEntity->save();
        self::expectException(Exception::class);
        self::expectExceptionMessage('The token for this entity could not be found');
        TokenController::loadTokenForEntity($otherTestModelEntity, $t->get('token'));
    }

    public function testLoadTokenForEntityExceptionWrongEntityId(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $t = $testModelEntity->addSecondaryModelRecord(Token::class, []);

        $testModelEntity2 = (new TestModel($this->db))->createEntity();
        $testModelEntity2->save();
        self::expectException(Exception::class);
        self::expectExceptionMessage('The token for this entity could not be found');
        TokenController::loadTokenForEntity($testModelEntity2, $t->get('token'));
    }

    public function testCreateTokenForEntity(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $token = TokenController::createTokenForEntity($testModelEntity);
        self::assertSame(64, strlen($token->get('token')));
        self::assertSame($testModelEntity->getId(), $token->get('model_id'));
        self::assertSame(TestModel::class, $token->get('model_class'));
    }

    public function testGetTokenForEntity(): void
    {
        $testModelEntity = (new TestModel($this->db))->createEntity();
        $testModelEntity->save();
        $token = TokenController::createTokenForEntity($testModelEntity);
        self::assertSame(64, strlen($token->get('token')));
        self::assertSame($testModelEntity->getId(), $token->get('model_id'));
        self::assertSame(TestModel::class, $token->get('model_class'));
    }

    public function testAssertValidToken(): void
    {
        $token = (new Token($this->db))->createEntity()->save();
        $loadedToken = TokenController::assertValidToken($token->get('token'), $this->db);
        self::assertSame(
            $token->getId(),
            $loadedToken->getId()
        );
    }

    public function testAssertInvalidTokenException(): void
    {
        self::expectExceptionMessage('The token could not be found.');
        TokenController::assertValidToken('SomeNonExistantToken', $this->db);
    }
}

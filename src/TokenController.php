<?php declare(strict_types=1);

namespace PhilippR\Atk4\Token;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use Atk4\Data\Persistence;

class TokenController
{

    protected static $tokenClass = Token::class;

    /**
     * Tries to load a Token for a given Entity. Checks if token is meant for this Entity before returning
     *
     * @param Model $entity
     * @param string $token
     * @return Token
     * @throws Exception
     */
    public static function loadTokenForEntity(Model $entity, string $token): Token
    {
        $entity->assertIsLoaded();
        $token = (new self::$tokenClass($entity->getModel()->getPersistence()))->tryLoadBy('token', $token);
        if (
            $token === null
            || $token->get('model_class') !== get_class($entity)
            || $token->get('model_id') != $entity->getId()
        ) {
            throw new TokenException(
                'The token for this entity could not be found.'
            );
        }

        return $token;
    }

    /**
     * Create a token for any given entity. Sets model_class and model_id of the token to the according entity values.
     *
     * @param Model $entity
     * @return Token
     * @throws Exception
     * @throws \Atk4\Core\Exception
     */
    public static function createTokenForEntity(Model $entity): Token
    {
        $entity->assertIsEntity();
        $token = (new self::$tokenClass($entity->getModel()->getPersistence()))->createEntity();
        $token->setParentEntity($entity);
        $token->save();

        return $token;
    }

    /**
     * Exceptions are thrown in case token is not found or outdated
     *
     * @param string $token
     * @param Persistence $persistence
     * @return Token
     */
    public static function assertValidToken(string $token, Persistence $persistence): Token
    {
        $token = (new self::$tokenClass($persistence))->tryLoadBy('token', $token);
        if ($token === null) {
            throw new TokenException('The token could not be found.', 401);
        }

        return $token;
    }
}